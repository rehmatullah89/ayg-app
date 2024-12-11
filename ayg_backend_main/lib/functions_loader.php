<?php

use Parse\ParseClient;
use Parse\ParseQuery;
use Parse\ParseObject;
use Parse\ParseUser;
use Parse\ParseFile;
use Parse\ParseGeoPoint;

$s3_client = getS3ClientObject();

function prepareAndPostToParse(
    $apiID = "",
    $apiKey = "",
    $className,
    $fileArray,
    $objectKeyType,
    $objectKeys,
    $createUniqueId = "N",
    $duplicateLookupKeyArray = array(),
    $imagesIndexesWithPaths = array(),
    $referenceLookup = array(),
    $insertOnlyMode = false,
    $updateOnlyIfDifferent = [],
    $forceAdding = false,
    $throwException = false
) {

    $debug = false;
    if (array_key_first($fileArray) == 28) {
        $debug = true;
    }

    $duplicateLookupKeyName = "";
    $duplicateLookupKeyValue = "";

    $insertedRowCount = $updatedRowCount = $failed = 0;
    $i = 0;
    $total = count_like_php5($fileArray);


    // Iterate through the data file
    foreach ($fileArray as $object) {

        $i++;

        // It's pre-delimited / already an array now
        $objectForParse = array();
        $unsetKeys = array();

        $duplicateLookupKeyValueConcatenated = "";
        $filesOnS3 = [];
        $notBooleanKeys = [];

        foreach ($object as $key => $value) {

            // Pull the key name from Key array
            $keyName = $objectKeys[$key];

            // Check if this index is required
            if (!in_array($keyName, array_keys($objectKeyType))) {
                continue;
            }

            if ($objectKeyType[$keyName] == "NB") {

                $notBooleanKeys[] = $keyName;
            }

            // If Image, get it the file and upload to Parse and get Link
            if (in_array($keyName, array_keys($imagesIndexesWithPaths))) {

                if (!empty(trimFull($value))) {

                    // S3 upload
                    $filesOnS3[$keyName] = trimFull($value);
                } else {

                    s3logMenuLoader(printLogTime() . "---- Image ($key): No image provided" . "\r\n");
                }
            } // Find values that are to be skipped; treat regular here but unset later before Parse Array can be created
            else {
                if ($objectKeyType[$keyName] == "X") {

                    $objectForParse[$keyName] = trimFull($value);
                    $unsetKeys[] = $keyName;
                } // Find if the value needs to be converted into a subarray
                else {
                    if ($objectKeyType[$keyName] == "Y") {

                        $subValueArray = explode(";", $value);
                        $subValueArrayTrimmed = array();

                        foreach ($subValueArray as $subValue) {

                            $subValueArrayTrimmed[] = trimFull($subValue);
                        }

                        $objectForParse[$keyName] = $subValueArrayTrimmed;
                    } // Find if the value is a GeoPoint
                    else {
                        if ($objectKeyType[$keyName] == "G") {

                            if (empty(trimFull($value))) {

                                $objectForParse[$keyName] = "";
                            } else {
                                list($lat, $long) = explode(";", trimFull($value));

                                s3logMenuLoader(printLogTime() . "$lat, $long" . "\r\n");

                                $objectForParse[$keyName] = new ParseGeoPoint(round($lat, 5), round($long, 5));
                            }
                        } // Find if the value is an Integer
                        else {
                            if ($objectKeyType[$keyName] == "I") {

                                $objectForParse[$keyName] = intval(trimFull($value));
                            } // Find if the value is a Float
                            else {
                                if ($objectKeyType[$keyName] == "F") {

                                    $objectForParse[$keyName] = floatval($value);
                                } // Else store trimmed value
                                else {

                                    $objectForParse[$keyName] = trimFull($value);
                                    // s3logMenuLoader(printLogTime() . $value . " - " . $objectForParse[$keyName] . "");
                                }
                            }
                        }
                    }
                }
            }

            // Check if this key is to be used for the duplicate key check process
            if (in_array($keyName, $duplicateLookupKeyArray)) {
                $duplicateLookupKeyValueConcatenated .= $objectForParse[$keyName];
            }
        }


        $duplicateCheck = false;

        // Add Unique Id
        if ($createUniqueId == "Y") {

            $objectForParse["uniqueId"] = md5($duplicateLookupKeyValueConcatenated);
            $duplicateLookupKeyName = "uniqueId";
            $duplicateLookupKeyValue = $objectForParse["uniqueId"];
            $duplicateCheck = true;
        } else {

            // Set this to the 0th index; if uniqueId is not used, then there should be only one value in the array
            // If uniqueId = Y, then keyName gets updated later
            if (count_like_php5($duplicateLookupKeyArray) > 0) {

                $duplicateLookupKeyValue = [];
                foreach ($duplicateLookupKeyArray as $duplicateLookupKeyName) {

                    $duplicateCheck = true;
                    // $duplicateLookupKeyName = $duplicateLookupKeyArray[0];

                    // Check if the unique check column is a column listed in lookupVals, which means we need to get the object to compare instead of base value
                    if (isset($referenceLookup[$duplicateLookupKeyName])) {

                        $referenceObjectResults = getLookupValueFromRefClass($referenceLookup[$duplicateLookupKeyName],
                            $objectForParse);

                        if (count_like_php5($referenceObjectResults) == 0) {

                            if ($throwException){
                                throw new Exception("UniqueId check = lookup value for object failed!");
                            }

                            s3logMenuLoader(printLogTime() . "UniqueId check = lookup value for object failed!", true);
                            exit;
                        }

                        $duplicateLookupKeyValue[$duplicateLookupKeyName] = $referenceObjectResults[0];
                    } else {

                        $duplicateLookupKeyValue[$duplicateLookupKeyName] = $object[array_search($duplicateLookupKeyName,
                            $objectKeys)];
                    }
                }
            } else {
                if ($insertOnlyMode == true) {

                    // No unique id needed
                } else {

                    die("No UniqueId column found!");
                }
            }
        }


        // A file needs to be uploaded
        if (count_like_php5($filesOnS3) > 0) {

            foreach ($filesOnS3 as $keyName => $originalFileName) {

                $fileNameOnS3 = $originalFileName;

                // If useUniqueIdName is set for images
                if (isset($imagesIndexesWithPaths[$keyName]['useUniqueIdInName'])
                    && strcasecmp($imagesIndexesWithPaths[$keyName]['useUniqueIdInName'], 'Y') == 0
                ) {

                    if (isset($imagesIndexesWithPaths[$keyName]['useUniqueIdInNameColumn'])
                        && isset($objectForParse[$imagesIndexesWithPaths[$keyName]['useUniqueIdInNameColumn']])
                    ) {

                        $fileNameOnS3 = $objectForParse[$imagesIndexesWithPaths[$keyName]['useUniqueIdInNameColumn']] . '_' . $fileNameOnS3;
                    } else {
                        if (isset($objectForParse["uniqueId"])) {

                            $fileNameOnS3 = $objectForParse["uniqueId"] . '_' . $fileNameOnS3;
                        } else {

                            if ($throwException){
                                throw new Exception("No uniqueId column found to name the image!");
                            }

                            s3logMenuLoader(printLogTime() . 'No uniqueId column found to name the image!', true);
                            exit;
                        }
                    }
                } // Use a different column's value in name
                else {
                    if (isset($imagesIndexesWithPaths[$keyName]['useThisColumnValueInName'])
                        && !empty($imagesIndexesWithPaths[$keyName]['useThisColumnValueInName'])
                    ) {

                        $columnNameToUseForS3 = $imagesIndexesWithPaths[$keyName]['useThisColumnValueInName'];

                        if (isset($objectForParse[$columnNameToUseForS3])) {

                            $fileNameOnS3 = $objectForParse[$columnNameToUseForS3] . '_' . $fileNameOnS3;
                        } else {

                            if ($throwException){
                                throw new Exception("No column found (' . $columnNameToUseForS3 . ') to name the image!");
                            }

                            s3logMenuLoader(printLogTime() . 'No column found (' . $columnNameToUseForS3 . ') to name the image!',
                                true);
                            exit;
                        }
                    }
                }

                // Is the source file on S3
                if (isset($imagesIndexesWithPaths[$keyName]['sourceImageIsOnS3'])
                    && $imagesIndexesWithPaths[$keyName]['sourceImageIsOnS3'] == true
                ) {

                    $s3_client = getS3ClientObject();

                    $filename = basename(parse_url($imagesIndexesWithPaths[$keyName]['sourceImageS3Path'],
                        PHP_URL_PATH));

                    // Download file from S3 to local location
                    $originalFileName = time() . "_" . $filename;
                    $imagesIndexesWithPaths[$keyName]['imagePath'] = rtrim(sys_get_temp_dir());

                    $fp = fopen($imagesIndexesWithPaths[$keyName]['imagePath'] . "/" . $originalFileName, 'w');
                    fwrite($fp, file_get_contents(S3GetPrivateFile($s3_client, $GLOBALS['env_S3BucketName'],
                        $imagesIndexesWithPaths[$keyName]['sourceImageS3Path'], 2)));
                    fclose($fp);
                }

                list($originalFileName, $thumbFileName) = checkImageSizeAndResize($imagesIndexesWithPaths[$keyName]['imagePath'] . '/',
                    $originalFileName, $imagesIndexesWithPaths[$keyName]['maxWidth'],
                    $imagesIndexesWithPaths[$keyName]['maxHeight'],
                    $imagesIndexesWithPaths[$keyName]['createThumbnail']);

                if (!empty($originalFileName)) {

                    $keyWithFolderPath = $imagesIndexesWithPaths[$keyName]['S3KeyPath'] . '/' . $fileNameOnS3;
                    $localFileNameWithPath = $imagesIndexesWithPaths[$keyName]['imagePath'] . '/' . $originalFileName;

                    if (!file_exists($localFileNameWithPath)){
                        $fileName = explode('/',$localFileNameWithPath);
                        $fileName = $fileName[count($fileName)-1];
                        throw new Exception('File '.$fileName.' not found!');
                    }

                    $s3_client = getS3ClientObject();
                    $url = S3UploadFileWithPath($s3_client, $GLOBALS['env_S3BucketName'], $keyWithFolderPath,
                        $localFileNameWithPath, true);

                    if (is_array($url)) {

                        json_error($url["error_code"], "Image save failed",
                            $url["error_message_log"] . " Image save failed", 1);
                    }
                }

                if (!empty($thumbFileName)) {

                    //$keyWithFolderPath = $imagesIndexesWithPaths[$keyName]['S3KeyPath'] . '/' . 'thumb_' . $fileNameOnS3;
                    $keyWithFolderPath = $imagesIndexesWithPaths[$keyName]['S3KeyPath'] . '/' . addThumbPrefix($fileNameOnS3);
                    $localFileNameWithPath = $imagesIndexesWithPaths[$keyName]['imagePath'] . '/' . $thumbFileName;

                    $url_thumb = S3UploadFileWithPath($s3_client, $GLOBALS['env_S3BucketName'], $keyWithFolderPath,
                        $localFileNameWithPath, true);

                    if (is_array($url_thumb)) {

                        json_error($url_thumb["error_code"], "Thumb Image save failed",
                            $url_thumb["error_message_log"] . " Image save failed", 1);
                    }
                }

                // Delete the local copy we created
                if (isset($imagesIndexesWithPaths[$keyName]['sourceImageIsOnS3'])
                    && $imagesIndexesWithPaths[$keyName]['sourceImageIsOnS3'] == true
                ) {

                    unlink($imagesIndexesWithPaths[$keyName]['imagePath'] . "/" . $originalFileName);
                }

                /*
                // Parse File upload
                $csvImageBinary = file_get_contents($imagesIndexesWithPaths[$keyName] . '/' . trimFull($value));
                $objParseImageObject = ParseFile::createFromData($csvImageBinary, "image.png");

                try {

                    $objParseImageObject->save();

                } catch (ParseException $ex) { }

                $url = cleanURL($objParseImageObject->getURL());
                */

                // Find the last occurrence of the / to find the name of file without the URL
                $objectForParse[$keyName] = extractFilenameFromS3URL($url);

                s3logMenuLoader(printLogTime() . "---- Image file URL generated: $url" . "\r\n");
            }
        }
        if ($debug) {
            var_dump('debug 3');
        }


        // Lookup reference columns
        if (count_like_php5($referenceLookup) > 0) {

            foreach ($referenceLookup as $lookupKeyName => $arrayWithReferences) {

                $referenceObjectResults = getLookupValueFromRefClass($arrayWithReferences, $objectForParse);

                // If lookup value is required but none was found, error and exit
                if (count_like_php5($referenceObjectResults) == 0 && $arrayWithReferences["isRequired"]) {


                    if ($throwException){
                        throw new Exception(
                            json_encode([$arrayWithReferences, $objectForParse]).

                            $lookupKeyName . " - is required but lookup failed!");
                    }

                    s3logMenuLoader(printLogTime() . $lookupKeyName . " - is required but lookup failed!", true);
                    exit;
                }

                // If column name provided in whenColumnValuePresentIsRequired has a value then isRequired = true is assumed
                if (count_like_php5($referenceObjectResults) == 0
                    && !empty($arrayWithReferences["whenColumnValuePresentIsRequired"])
                    // Ensure the value is not empty
                    && !empty($object[array_search($arrayWithReferences["whenColumnValuePresentIsRequired"],
                        $objectKeys)])
                ) {

                    if ($throwException){
                        throw new Exception($lookupKeyName . " value was provided but lookup failed!");
                    }

                    s3logMenuLoader(printLogTime() . $lookupKeyName . " value was provided but lookup failed!", true);
                    exit;
                }

                // If value is found set, else set to blank
                unset($objectForParse[$lookupKeyName]);

                if (count_like_php5($referenceObjectResults) > 0) {

                    // if the lookupKeyName is defined as an Array
                    if (isset($objectKeyType[$lookupKeyName])
                        && ($objectKeyType[$lookupKeyName] == "Y" || $objectKeyType[$lookupKeyName] == "A")
                    ) {

                        foreach ($referenceObjectResults as $referenceObjectResultsOne) {

                            $objectForParse[$lookupKeyName][] = $referenceObjectResultsOne;
                        }
                    } // Else simple non array value
                    else {

                        $objectForParse[$lookupKeyName] = $referenceObjectResults[0];
                    }
                } else {

                    if (isset($objectKeyType[$lookupKeyName])
                        && ($objectKeyType[$lookupKeyName] == "Y" || $objectKeyType[$lookupKeyName] == "A")
                    ) {

                        $objectForParse[$lookupKeyName] = array();
                    } else {

                        $objectForParse[$lookupKeyName] = null;
                    }
                }
            }
        }


        // Unset all values with markings of X
        foreach ($unsetKeys as $unsetKeyName) {

            unset($objectForParse[$unsetKeyName]);
        }

        // The final prepped data to be pushed to Parse
        s3logMenuLoader(printLogTime() . json_encode($objectForParse) . "\r\n");

        // Only do duplicate check if we need a uniqueId
        $objParseQueryDuplicate = [];
        // JMD


        if ($debug) {
            var_dump('debug 5');
        }


        if ($duplicateCheck == true) {

            if (is_array($duplicateLookupKeyValue)
                && $createUniqueId != "Y"
            ) {

                if (is_object($duplicateLookupKeyValue)) {

                    s3logMenuLoader(printLogTime() . "Unique check: $duplicateLookupKeyName = " . "\r\n");
                    s3logMenuLoader(printLogTime() . $duplicateLookupKeyValue->getObjectId() . "\r\n");
                } else {

                    s3logMenuLoader(printLogTime() . "Unique check: " . http_build_query($duplicateLookupKeyValue) . "\r\n");
                }
            } else {

                s3logMenuLoader(printLogTime() . "Unique check: $duplicateLookupKeyName = " . "\r\n");
                s3logMenuLoader(printLogTime() . $duplicateLookupKeyValue . "\r\n");
            }

            //////////////////////////
            // Check if the lookup key already exists
            $objParseQuery = new ParseQuery($className);
            if (is_array($duplicateLookupKeyValue)) {

                foreach ($duplicateLookupKeyValue as $uniqueCheckKey => $uniqueCheckValue) {

                    $objParseQuery->equalTo($uniqueCheckKey, $uniqueCheckValue);
                }
            } else {

                $objParseQuery->equalTo($duplicateLookupKeyName, $duplicateLookupKeyValue);
            }

            try {

                $objParseQueryDuplicate = $objParseQuery->first();
            } catch (ParseException $ex) {

                if ($throwException){
                    throw new Exception('Unique Lookup Failed!');
                }
                s3logMenuLoader(printLogTime() . 'Unique Lookup Failed!' . "\r\n", true);
                exit;
                // JMD
            }
            //////////////////////////
        }


        if (count_like_php5($objParseQueryDuplicate) > 0 && !$forceAdding) {

            $insertRow = false;
            $updateRow = true;
            // JMD
            // Update rows only if required to

            $dbRowHash = $fileRowHash = [];
            $dbRowHashGenerate = $fileRowHashGenerate = "";
            if (count_like_php5($updateOnlyIfDifferent) > 0) {

                $dbRowHash = "";
                foreach ($updateOnlyIfDifferent as $column) {

                    // DB Hash
                    $dbRowHash[$column] = "";
                    if ($objParseQueryDuplicate->has($column)
                        && !empty_zero_allowed($objParseQueryDuplicate->get($column))
                    ) {

                        if (is_bool($objParseQueryDuplicate->get($column))) {

                            $dbRowHash[$column] = $objParseQueryDuplicate->get($column) == true ? "Y" : "N";
                        } else {
                            if (is_array($objParseQueryDuplicate->get($column))) {

                                $dbRowHash[$column] = implode(";", $objParseQueryDuplicate->get($column));
                            } else {
                                if (is_object($objParseQueryDuplicate->get($column))) {

                                    $dbRowHash[$column] = $objParseQueryDuplicate->get($column)->getObjectId();
                                } else {

                                    $dbRowHash[$column] = $objParseQueryDuplicate->get($column);
                                }
                            }
                        }
                    }

                    // JMD
                    $fileRowHash[$column] = "";
                    if (isset($objectForParse[$column])) {

                        if (is_bool($objectForParse[$column])) {

                            $fileRowHash[$column] = ($objectForParse[$column] == true) ? "Y" : "N";
                        } else {
                            if (is_array($objectForParse[$column])) {

                                $fileRowHash[$column] = implode(";", $objectForParse[$column]);
                            } else {
                                if (is_object($objectForParse[$column])) {

                                    $fileRowHash[$column] = $objectForParse[$column]->getObjectId();
                                } else {

                                    $fileRowHash[$column] = $objectForParse[$column];
                                }
                            }
                        }
                    }
                }

                list($dbRowHashGenerate, $fileRowHashGenerate) = generateRowHashForComparison($dbRowHash, $fileRowHash);

                if (strcasecmp($dbRowHashGenerate, $fileRowHashGenerate) == 0) {

                    $updateRow = false;
                }
            }

            if ($updateRow == true) {

                $updatedRowCount++;
                // Exists, so delete record so new can be inserted

                if (!empty($dbRowHashGenerate)) {

                    s3logMenuLoader(printLogTime() . "-- Updating record (Hashes are different: " . $dbRowHashGenerate . "  vs " . $fileRowHashGenerate . ")" . "<br />\n");
                } else {

                    s3logMenuLoader(printLogTime() . "-- Updating record" . "<br />\n");
                }

                // Use existing object
                $objParseObject = new ParseObject($className, $objParseQueryDuplicate->getObjectId());

                // Delete any existing S3 files, so they can be reuploaded
                deleteExistingFileOnS3($filesOnS3, $objectForParse, $objParseQueryDuplicate, $imagesIndexesWithPaths);
            } else {

                s3logMenuLoader(printLogTime() . "-- Skipping Update record as hashes match" . "<br />");

                s3logMenuLoader(printLogTime() . "---------------------------------------------------------" . "\r\n");
                s3logMenuLoader(printLogTime() . "Row $i / $total" . "\r\n");
                s3logMenuLoader(printLogTime() . "---------------------------------------------------------" . "\r\n");
            }
        } else {

            $insertedRowCount++;

            $insertRow = true;
            $updateRow = false;
            s3logMenuLoader(printLogTime() . "-- Inserting record" . "\r\n");

            // New Object
            $objParseObject = new ParseObject($className);
        }


        // JMD
        if (!$insertRow && !$updateRow) {

            continue;
        }


        //////////////////////////
        // Add to Parse DB
        foreach ($objectForParse as $preparedKey => $preparedValue) {

            // If Array
            if (is_array($preparedValue)) {

                $objParseObject->setArray($preparedKey, $preparedValue);
            } else {

                // If Boolean, change Y, N flags to Boolean values
                if (gettype($preparedValue) == 'string' && strcasecmp($preparedValue,
                        "Y") == 0 && !in_array($preparedKey, $notBooleanKeys)
                ) {

                    // s3logMenuLoader(printLogTime() . $preparedKey . ' - *' . gettype($preparedValue) . ' - ' . $preparedValue . "\r\n");
                    $objParseObject->set($preparedKey, true);
                } else {
                    if (gettype($preparedValue) == 'string' && strcasecmp($preparedValue,
                            "N") == 0 && !in_array($preparedKey, $notBooleanKeys)
                    ) {

                        // s3logMenuLoader(printLogTime() . $preparedKey . ' - *' . gettype($preparedValue) . ' - ' . $preparedValue . "\r\n");

                        $objParseObject->set($preparedKey, false);
                    } else {

                        // s3logMenuLoader(printLogTime() . $preparedKey . ' - ' . gettype($preparedValue) . "\r\n");

                        if (gettype($preparedValue) == 'double') {

                            $objParseObject->set($preparedKey, (float)$preparedValue);
                        } else {
                            if (gettype($preparedValue) == 'integer') {

                                $objParseObject->set($preparedKey, (int)$preparedValue);
                            } else {

                                $objParseObject->set($preparedKey, $preparedValue);
                            }
                        }
                    }
                }
            }
        }
        try {


            $objParseObject->save();
        } catch (ParseException $ex) {
            s3logMenuLoader(printLogTime() . 'Insert Failed!' . "\r\n");
            $failed++;
        }
        //////////////////////////

        unset($objParseObject);

        s3logMenuLoader(printLogTime() . "---------------------------------------------------------" . "\r\n");
        s3logMenuLoader(printLogTime() . "Row $i / $total" . "\r\n");
        s3logMenuLoader(printLogTime() . "---------------------------------------------------------" . "\r\n");

        // usleep(500000);

        // flush();
        // @ob_flush();
    }

    s3logMenuLoader(printLogTime() . "Total Records = " . $i . ", Inserted = " . ($insertedRowCount) . ", Updated = " . ($updatedRowCount) . ", Failed = " . $failed . "\r\n",
        true);


    return [
        'total' => $i,
        'inserted' => $insertedRowCount,
        'updated' => $updatedRowCount,
        'failed' => $failed,
    ];
}

function generateRowHashForComparison($dbColumns, $fileColumns)
{

    $dbHash = $fileHash = "";

    foreach ($dbColumns as $key => $value) {

        $dbHash .= $key . "_" . $value . "---";
        $fileHash .= $key . "_" . $fileColumns[$key] . "---";
    }

    // print_r($dbColumns);
    // print_r($fileColumns);
    // echo("DHash: " . md5($dbHash));
    // echo("Hash: " . md5($fileHash));
    // exit;

    return [md5($dbHash), md5($fileHash)];
}

function checkImageSizeAndResize($filepath, $filename, $maxWidth, $maxHeight, $createThumbnail)
{

    // If no max dimensions provided, return current filename
    if (empty($maxWidth)
        || empty($maxHeight)
    ) {

        return [$filename, ''];
    }

    $image = new Imagick();

    $image->readImage($filepath . $filename);

    $originalWidth = $image->getImageWidth();
    $originalHeight = $image->getImageHeight();
    $ratio = $originalWidth / $originalHeight;

    $targetWidth = $targetHeight = '';

    // Initialize
    $resizedFilename = $filename;
    $thumbFilename = '';

    // If width is greater and the width is bigger than max width
    if ($ratio >= 1
        && $originalWidth > $maxWidth
    ) {

        $targetWidth = $maxWidth;
        $targetHeight = round($targetWidth / $ratio);
    } // If height is greater and the height is bigger than max height
    else {
        if ($ratio < 1
            && $originalHeight > $maxHeight
        ) {

            $targetHeight = $maxHeight;
            $targetWidth = round($targetHeight * $ratio);
        }
    }

    // Resize (i.e. a target width or heigth was calculated)
    if (!empty($targetWidth)) {

        //$resizedFilename = 'resized_' . $filename;
        $resizedFilename = addResizedPrefix($filename);
        $image->resizeImage($targetWidth, $targetHeight, Imagick::FILTER_CATROM, 1);
        $image->writeImage($filepath . $resizedFilename);
        $image->clear();
        $image->destroy();

        s3logMenuLoader(printLogTime() . "Reized image generated: " . $filepath . $resizedFilename . "\r\n");
    }

    // Thumbnail
    if ($createThumbnail == true) {

        if ($ratio >= 1) {

            $thumbWidth = 250;
            $thumbHeight = round($thumbWidth / $ratio);
        } else {

            $thumbHeight = 250;
            $thumbWidth = round($thumbHeight * $ratio);
        }


        //$thumbFilename = 'thumb_' . $filename;
        $thumbFilename = addThumbPrefix($filename);



        $image->readImage($filepath . $filename);
        $image->resizeImage($thumbWidth, $thumbHeight, Imagick::FILTER_CATROM, 1);
        $image->writeImage($filepath . $thumbFilename);
        $image->clear();
        $image->destroy();

        s3logMenuLoader(printLogTime() . "Thumb image generated: " . $filepath . $thumbFilename . "\r\n");
    }

    return [$resizedFilename, $thumbFilename];
}

function addThumbPrefix($fileName)
{
    $fileName = explode('/', $fileName);
    $fileName[count($fileName) - 1] = 'thumb_' . $fileName[count($fileName) - 1];

    return implode('/',$fileName);
}

function addResizedPrefix($fileName)
{
    $fileName = explode('/', $fileName);
    $fileName[count($fileName) - 1] = 'resized_' . $fileName[count($fileName) - 1];

    return implode('/',$fileName);
}

// JMD
function deleteExistingFileOnS3($filesOnS3, $objectForParse, $objParseQueryDuplicate, $imagesIndexesWithPaths)
{

    if (count_like_php5($filesOnS3) > 0) {

        s3logMenuLoader(printLogTime() . "-- Deleting existing files on S3..." . "\r\n");

        foreach (array_keys($filesOnS3) as $keyName) {

            $newFileName = $objectForParse[$keyName];
            $existingFileName = $objParseQueryDuplicate->get($keyName);

            if (strcasecmp($newFileName, $existingFileName) == 0) {

                s3logMenuLoader(printLogTime() . "-- Skipped deletion for $existingFileName = $newFileName (same as new name)" . "\r\n");
            } else {

                $keyWithFolderPath = $imagesIndexesWithPaths[$keyName]['S3KeyPath'] . '/' . $existingFileName;

                $s3_client = getS3ClientObject();

                $flag = S3DeleteFile($s3_client, $GLOBALS['env_S3BucketName'], $keyWithFolderPath);

                if (is_array($flag)) {

                    json_error($flag["error_code"],
                        "Image deletion failed" . " - " . $flag["error_message_log"] . " Image save failed", "", 1);
                }

                s3logMenuLoader(printLogTime() . "done." . "\r\n");
            }
        }
    }
}

function getLookupValueFromRefClass($arrayWithReferences, $objectForParse)
{

    $referenceQuery = new ParseQuery($arrayWithReferences["className"]);

    // Construct the condition
    foreach ($arrayWithReferences["lookupCols"] as $matchColumn => $fromColumn) {

        if (isset($arrayWithReferences["lookupColsType"][$matchColumn])
            && ($arrayWithReferences["lookupColsType"][$matchColumn] == "I")
        ) {

            $referenceQuery->equalTo($matchColumn, intval($objectForParse[$fromColumn]));
        }
        // if the fromColumn is defined as an Array
        // if(isset($objectKeyType[$fromColumn])
        // 	&& ($objectKeyType[$fromColumn] == "Y" || $objectKeyType[$fromColumn] == "A")) {
        else {
            if (isset($arrayWithReferences["lookupColsType"][$matchColumn])
                && ($arrayWithReferences["lookupColsType"][$matchColumn] == "Y" || $arrayWithReferences["lookupColsType"][$matchColumn] == "A")
            ) {

                // It is mentioned that the source column is an array, but it wasn't read as such, e.g. set as X
                // Then convert it to array
                // if(!is_array($objectForParse[$fromColumn])) {

                // 	$objectForParse[$fromColumn] = explode(";", $objectForParse[$fromColumn]);
                // }

                $lookupArrayTemp = array();
                foreach ($objectForParse[$fromColumn] as $subValue) {

                    $lookupArrayTemp[] = trimFull($subValue);
                }

                $referenceQuery->containedIn($matchColumn, $lookupArrayTemp);
            } // if a non-array
            else {

                // if lookup value is provided
                if (preg_match("/^__LKPVAL__/si", $matchColumn)) {

                    $referenceQuery->equalTo(str_replace("__LKPVAL__", "", $matchColumn), $fromColumn);
                } else {

                    if (is_array($objectForParse[$fromColumn])) {

                        $referenceQuery->equalTo($matchColumn, $objectForParse[$fromColumn][0]);
                    } else {

                        $referenceQuery->equalTo($matchColumn, $objectForParse[$fromColumn]);
                    }
                }
            }
        }
    }


    try {

        $referenceObjectResults = $referenceQuery->find(true);
    } catch (Exception $e) {

        s3logMenuLoader(printLogTime() . $e->getMessage(), true);
        exit;
    }

    return $referenceObjectResults;
}

function verifyNewValues(
    $fileArray,
    $className,
    $classColumnName,
    $keyName,
    $keyForValue,
    $haltIfMissingValuesFound = false
) {

    global $objectKeyIsArray;

    s3logMenuLoader(printLogTime() . "-- Verifying new values for: " . $keyName . "\r\n");

    // Build unique values in the file
    $uniqueValues = array();
    foreach ($fileArray as $i => $row) {


        $valuesToCheckArrayTemp = array();

        // if an array
        if ($objectKeyIsArray[$keyName] == "Y") {

            $subValueArray = explode(";", $row[$keyForValue]);
            $subValueArrayTrimmed = array();

            foreach ($subValueArray as $subValue) {

                $valuesToCheckArrayTemp[] = trimFull($subValue);
            }
        } // Create one element array
        else {

            $valuesToCheckArrayTemp[] = trim($row[$keyForValue]);
        }

        // Run through the values to check array
        foreach ($valuesToCheckArrayTemp as $valuesToCheck) {

            if (!in_array($valuesToCheck, $uniqueValues) && !empty(trim($valuesToCheck))) {

                $uniqueValues[] = trim($valuesToCheck);
            }
        }
    }



    $missingValues = array();
    foreach ($uniqueValues as $uniqueValueOne) {

        if ($objectKeyIsArray[$keyName] == "I") {

            $uniqueValueOne = intval($uniqueValueOne);
        }

        $objParseQuery = new ParseQuery($className);
        $objParseQuery->equalTo($classColumnName, $uniqueValueOne);

        $objParseQueryResults = $objParseQuery->find();

        if (count_like_php5($objParseQueryResults) == 0) {

            if (!in_array($uniqueValueOne, $missingValues)) {

                $missingValues[] = $uniqueValueOne;
            }
        }
    }

    if (count_like_php5($missingValues) > 0) {

        s3logMenuLoader(printLogTime() . "Missing values found for: " . $className . "\r\n");
        s3logMenuLoader(printLogTime() . implode(',', $missingValues));

        if ($haltIfMissingValuesFound == true) {

            s3logMenuLoader(printLogTime() . "Error: " . "\r\n", true);
            exit;
        }
    }

    return $missingValues;
}

function getFileURL($filename)
{

    // Get the current file via its URL
    try {

        $imageContents = @getpage($filename);
    } catch (Exception $e) {

        die($e->getMessage());
    }

    if (empty($imageContents)) {

        die('getpage failed...');
    }

    // Extract its extension
    $imageExt = pathinfo($filename, PATHINFO_EXTENSION);

    // Create new file object using Parse for the To Server
    $imageNew = ParseFile::createFromData($imageContents, "logo." . $imageExt);

    // Save
    $imageNew->save();

    // Return URL
    return cleanURL($imageNew->getURL());
}

function getFileURLWithoutRecreate($filename, $sourceParseAppId, $sourceParseURL, $targetParseAppId, $targetParseURL)
{

    $filename = str_replace($sourceParseAppId, $targetParseAppId, $filename);
    $filename = str_replace($sourceParseURL, $targetParseURL, $filename);

    return $filename;
}

function setParseSettings()
{

    global $env_ParseApplicationId, $env_ParseRestAPIKey, $env_ParseMasterKey, $env_ParseServerURL;

    $env_ParseServerURL = getenv('env_ParseServerURL');
    $env_ParseApplicationId = getenv('env_ParseApplicationId');
    $env_ParseRestAPIKey = getenv('env_ParseRestAPIKey');
    $env_ParseMasterKey = getenv('env_ParseMasterKey');

    if (URLEndsWith($env_ParseServerURL, "/parse")) {

        ParseClient::setServerURL($env_ParseServerURL);
    } else {

        ParseClient::setServerURL($env_ParseServerURL, '/parse');
    }

    ParseClient::initialize($env_ParseApplicationId, $env_ParseRestAPIKey, $env_ParseMasterKey);
}

function getParseSettings($location)
{

    include('../configvars_' . $location . '.php');
}

// JMD
function URLEndsWith($haystack, $needle)
{

    $length = strlen($needle);
    if ($length == 0) {
        return true;
    }

    return (substr($haystack, -$length) === $needle);
}

function utf8_encode_custom(&$item, $key)
{

    if (strcasecmp(gettype($item), 'string') == 0) {

        // $item = utf8_encode($item);
    }
}

function processRetailerItemTimesData($fileArray, $objectKeys, $objectKeyIsArrayCustom)
{

    $timeRangeArray = [
        "restrictOrderTimes" => ["restrictOrderTimeInSecsStart", "restrictOrderTimeInSecsEnd"],
        "prepRestrictTimesGroup1" => ["prepRestrictTimeInSecsStartGroup1", "prepRestrictTimeInSecsEndGroup1"],
        "prepRestrictTimesGroup2" => ["prepRestrictTimeInSecsStartGroup2", "prepRestrictTimeInSecsEndGroup2"],
        "prepRestrictTimesGroup3" => ["prepRestrictTimeInSecsStartGroup3", "prepRestrictTimeInSecsEndGroup3"]
    ];

    return processTimeCoverage($fileArray, $objectKeys, $objectKeyIsArrayCustom, $timeRangeArray);
}

function processTimeCoverage($fileArray, $objectKeys, $objectKeyIsArrayCustom, $timeRangeArray)
{

    foreach ($timeRangeArray as $keyToProcess => $keysToReplaceWith) {

        // Add new keys to object array
        $newIndex = max(array_keys($objectKeys)) + 1;
        $objectKeys[$newIndex] = $keysToReplaceWith[0];
        $objectKeys[$newIndex + 1] = $keysToReplaceWith[1];

        // Add new keys to the process array with datatypes
        $objectKeyIsArrayCustom[$keysToReplaceWith[0]] = "I";
        $objectKeyIsArrayCustom[$keysToReplaceWith[1]] = "I";

        // Find key location for the keyToProcess
        $keyToProcessIndex = array_search($keyToProcess, $objectKeys);

        foreach ($fileArray as $rowNumber => $row) {
            if ($row[$keyToProcessIndex] == '-1'){
                $timeInSecsStart = -1;
                $timeInSecsEnd = -1;
            }else{
                list($timeInSecsStart, $timeInSecsEnd) = parseTimeRange($row[$keyToProcessIndex], $keyToProcess,
                    $rowNumber);
            }
            $fileArray[$rowNumber][$newIndex] = $timeInSecsStart;
            $fileArray[$rowNumber][$newIndex + 1] = $timeInSecsEnd;
        }
    }

    return [$fileArray, $objectKeys, $objectKeyIsArrayCustom];
}

function parseTimeRange($timeText, $columnName, $lineNumber, $airportIataCode = "")
{

    $timeInSecsStart = 0;
    $timeInSecsEnd = 0;

    if (strcasecmp($timeText, '0') == 0
        || strcasecmp($timeText, '-1') == 0
        || empty($timeText)
    ) {

        $timeInSecsStart = $timeInSecsEnd = intval($timeText);
    } else {

        $timeRange = explode(' - ', $timeText);

        if (count_like_php5($timeRange) != 2) {

            die('Invalid time range - ' . $timeText);
        }

        $timeMidnight = strtotime('May 1, 2017 midnight');
        if (strtotime('midnight') === false) {

            die('Invalid time midnight - ' . $timeText . ' - ' . $columnName . ' - ' . $lineNumber);
        }

        $timeStart = strtotime('May 1, 2017 ' . $timeRange[0]);
        if (strtotime($timeRange[0]) === false) {

            die('Invalid time start - ' . $timeText . ' - ' . $columnName . ' - ' . $lineNumber);
        }

        $timeEnd = strtotime('May 1, 2017 ' . $timeRange[1]);
        if (strtotime($timeRange[1]) === false) {

            die('Invalid time end - ' . $timeText . ' - ' . $columnName . ' - ' . $lineNumber);
        }

        $timeInSecsStart = $timeStart - $timeMidnight;
        $timeInSecsEnd = $timeEnd - $timeMidnight;

        if ($timeInSecsEnd < $timeInSecsStart && $timeInSecsEnd > 0) {

            die('Time range more than 24 hrs long - ' . $timeText . ' - ' . $columnName . ' - ' . $lineNumber . ' - ' . $timeInSecsEnd . ' - ' . $timeInSecsStart);
        }
    }

    return [intval($timeInSecsStart), intval($timeInSecsEnd)];
}

function processDeliveryCoverageItemTimesData($fileArray, $objectKeys, $objectKeyIsArrayCustom)
{

    $timeRangeArray = ["coverageTime" => ["secsSinceMidnightStart", "secsSinceMidnightEnd"]];

    return processTimeCoverage($fileArray, $objectKeys, $objectKeyIsArrayCustom, $timeRangeArray);
}

function setConfMetaUpdate($timestamp = "")
{

    if (empty($timestamp)) {

        $timestamp = time();
    }

    $configUpdate = new ParseQuery("Config");
    $configUpdate->equalTo("configName", "lastMetadataUpdateNoLogoutReq");
    $results = $configUpdate->first();

    $configUpdate = new ParseObject("Config", $results->getObjectId());
    $configUpdate->set("configValue", strval($timestamp));
    $configUpdate->save();

    s3logMenuLoader(printLogTime() . "Config updated!");
}

?>
