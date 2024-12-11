<?php



// Just Terminal + Concourse as the key
$fileArrayGateMap = [
    "WMI" => [
        'AA1' => '../airport_specific/WMI/data/WMI-GateMap-A1.csv',
    ],

    "EWR" => [
        'AA1' => '../airport_specific/EWR/data/EWR-GateMap-A1.csv',
        'AA2' => '../airport_specific/EWR/data/EWR-GateMap-A2.csv',
        'AA3' => '../airport_specific/EWR/data/EWR-GateMap-A3.csv',
        'APre-Security' => '../airport_specific/EWR/data/EWR-GateMap-APreSecurity.csv',

        'BB1' => '../airport_specific/EWR/data/EWR-GateMap-B1.csv',
        'BB2' => '../airport_specific/EWR/data/EWR-GateMap-B2.csv',
        'BB3' => '../airport_specific/EWR/data/EWR-GateMap-B3.csv',
        'BPre-Security' => '../airport_specific/EWR/data/EWR-GateMap-BPreSecurity.csv',

        'CPre-Security' => '../airport_specific/EWR/data/EWR-GateMap-CPreSecurity.csv',

        'P3P3' => '../airport_specific/EWR/data/EWR-GateMap-P3.csv',
        'P4P4' => '../airport_specific/EWR/data/EWR-GateMap-P4.csv',
    ],
];

/*
$fileArrayGateMapDirections = [
    "WMI" => [
        '1A' => '../airport_specific/WMI/data/GateCords.csv',
    ],

    "EWR" => [
        '1A' => '../airport_specific/EWR/data/EWR-GateMap-A1.csv',
    ],
];
*/


// index - terminal concorse
$fileArrayGateMapDirections = [
    "WMI" => [
        'AA1' => '../airport_specific/WMI/data/WMI-GateMap-A1.csv',
    ],

    "EWR" => [
        'AA1' => '../airport_specific/EWR/data/EWR-GateMap-A1.csv',
        'AA2' => '../airport_specific/EWR/data/EWR-GateMap-A2.csv',
        'AA3' => '../airport_specific/EWR/data/EWR-GateMap-A3.csv',
        'APre-Security' => '../airport_specific/EWR/data/EWR-GateMap-APreSecurity.csv',

        'BB1' => '../airport_specific/EWR/data/EWR-GateMap-B1.csv',
        'BB2' => '../airport_specific/EWR/data/EWR-GateMap-B2.csv',
        'BB3' => '../airport_specific/EWR/data/EWR-GateMap-B3.csv',
        'BPre-Security' => '../airport_specific/EWR/data/EWR-GateMap-BPreSecurity.csv',

        'CPre-Security' => '../airport_specific/EWR/data/EWR-GateMap-CPreSecurity.csv',

        'P3P3' => '../airport_specific/EWR/data/EWR-GateMap-P3.csv',
        'P4P4' => '../airport_specific/EWR/data/EWR-GateMap-P4.csv',
    ],
];

/*
$fileArrayGateMapDirections = array(
    "BWI" => array(
                    '1A' => '<path>\BWI\data\GateMapDirections - 1A.csv',
                    '1B' => '<path>\BWI\data\GateMapDirections - 1B.csv',
                    '1C' => '<path>\BWI\data\GateMapDirections - 1C.csv',
                    '1D' => '<path>\BWI\data\GateMapDirections - 1D.csv',
                    '1E' => '<path>\BWI\data\GateMapDirections - 1E.csv',
                    '1Pre-security' => '<path>\BWI\data\GateMapDirections - 1Pre-security.csv',
                    '1Off Airport' => '<path>\BWI\data\GateMapDirections - 1OffAirport.csv',
                ),
);
*/

// @todo - we can do it without cords (empty cells)
$fileArrayGateCords = [
    "WMI" => '../airport_specific/WMI/data/WMI-GateCords.csv',
    "EWR" => '../airport_specific/EWR/data/EWR-GateCords.csv',
];

// Terminal Mapping
$fileArrayT2TMap = [
    "WMI" => '../airport_specific/WMI/data/WMI-TerminalConnection.csv',
    "EWR" => '../airport_specific/EWR/data/EWR-TerminalConnection-new-old-numbers.csv',
];

foreach ($fileArrayGateMap as $airportIataCode => $gateMapFiles) {

    foreach ($gateMapFiles as $gate => $fileName) {

        $jsonGateMap[$airportIataCode][$gate] = convertGateMapToArray(array_map('str_getcsv', file($fileName)),
            array_map('str_getcsv', file($fileArrayGateMapDirections[$airportIataCode][$gate])));
    }

    $jsonGateCords[$airportIataCode] = getGateCords(array_map('str_getcsv',
        file($fileArrayGateCords[$airportIataCode])));

    $jsonT2TMap[$airportIataCode] = convertT2TMapToArray(array_map('str_getcsv',
        file($fileArrayT2TMap[$airportIataCode])));
}


// Write to Config file
$fp = fopen('./../../lib/gatemaps.inc.php', 'w');
fwrite($fp, '<' . '?php');
fwrite($fp, "\n\n\t" . '$' . 'jsonGateMap = ' . "json_decode('" . json_encode($jsonGateMap) . "', true);");
fwrite($fp, "\n\n\t" . '$' . 'jsonT2TMap = ' . "json_decode('" . json_encode($jsonT2TMap) . "', true);" . "\n\n");
fwrite($fp, "\n\n\t" . '$' . 'jsonGateCords = ' . "json_decode('" . json_encode($jsonGateCords) . "', true);" . "\n\n");
fwrite($fp, '?' . '>');
fclose($fp);

echo("generated!");

function getGateCords($fileArray)
{

    $gateCords = array();

    // Skip the Header row and create key arrays
    // $objectKeys = array_map('trim', explode(",", array_shift($fileArray)));
    $objectKeys = array_map('trim', array_shift($fileArray));

    foreach ($fileArray as $valueString) {

        // $valueArray = explode(",",$valueString);
        $valueArray = $valueString;

        $valueArray[0] = trim($valueArray[0]);
        $valueArray[1] = trim($valueArray[1]);
        $valueArray[2] = trim($valueArray[2]);

        $gateCords[$valueArray[0] . $valueArray[1] . $valueArray[2]]['x'] = trim($valueArray[3]);
        $gateCords[$valueArray[0] . $valueArray[1] . $valueArray[2]]['y'] = trim($valueArray[4]);
    }

    return $gateCords;
}

function convertT2TMapToArray($fileArray)
{

    $t2tMap = array();

    // Skip the Header row and create key arrays
    // $objectKeys = array_map('trim', explode(",", array_shift($fileArray)));
    $objectKeys = array_map('trim', array_shift($fileArray));


    foreach ($fileArray as $valueString) {

        // $valueArray = explode(",",$valueString);
        $valueArray = $valueString;

        // From Terminal
        $valueArray[0] = trim($valueArray[0]);

        // From Concourse
        $valueArray[1] = trim($valueArray[1]);

        // To Terminal
        $valueArray[2] = trim($valueArray[2]);

        // To Concourse
        $valueArray[3] = trim($valueArray[3]);

        // Skip first 4 columns
        for ($i = 4; $i < count($valueArray); $i++) {

            $valueArray[$i] = trim($valueArray[$i]);

            if (empty($valueArray[$i])) {

                $valueArray[$i] = "";
            }

            $t2tMap[$valueArray[0] . $valueArray[1] . '-' . $valueArray[2] . $valueArray[3]][$objectKeys[$i]] = $valueArray[$i];
        }
    }

    return $t2tMap;
}

function convertGateMapToArray($fileArrayGateMap, $fileArrayGateMapDirections)
{

    // Skip the Header row and create key arrays
    // $objectKeys = array_map('trim', explode(",", array_shift($fileArrayGateMap)));
    $objectKeys = array_map('trim', array_shift($fileArrayGateMap));

    // $objectKeysBak = array_map('trim', explode(",", array_shift($fileArrayGateMapDirections)));
    $objectKeysBak = array_map('trim', array_shift($fileArrayGateMapDirections));

    // gate to gate mappings
    $sequenceArray = array();

    // Only distance
    $distanceArray = array();

    // Only directional paths
    $directionsArray = array();

    // Both distance and directional as per Dijkstra graph requirement
    $directionsWithDistanceArray = array();

    foreach ($fileArrayGateMap as $j => $valueArray) {

        // $valueArray = explode(",",$valueString);
        $valueArray[0] = trim($valueArray[0]);

        // $directionValueArray = explode(",",$fileArrayGateMapDirections[$j]);
        $directionValueArray = $fileArrayGateMapDirections[$j];
        $directionValueArray[0] = trim($directionValueArray[0]);


        for ($i = 1; $i < count($valueArray); $i++) {

            $valueArray[$i] = trim($valueArray[$i]);

            $valueArrayKey0 = $valueArray[0];

            $valueDistance = $valueArray[$i];
            $valueDirectionCue = "";

            // If it has a ; that means there is a Direction Cue
            if (strpos($valueDistance, ";")) {

                list($valueDistance, $valueDirectionCue) = explode(";", $valueArray[$i]);
            }

            $objectKeysArrayKeyi = $objectKeys[$i];

            $directionValueArray[$i] = trim($directionValueArray[$i]);

            // Directions Array
            if (!empty($directionValueArray[$i])) {

                // Add its distance to the list
                $directionsArray["text"]["$valueArrayKey0" . '-' . "$objectKeysArrayKeyi"] = trim($directionValueArray[$i]);
                //$directionsArray["text"]["$objectKeysArrayKeyi" . '-' . "$valueArrayKey0"] = trim($directionValueArray[$i]);
                $directionsArray["cue"]["$valueArrayKey0" . '-' . "$objectKeysArrayKeyi"] = trim($valueDirectionCue);
            }

            if (!empty($valueArray[$i])) {

                // Add Gate number to the list
                if (!isset($sequenceArray["$valueArrayKey0"]) || !in_array($objectKeysArrayKeyi,
                        $sequenceArray["$valueArrayKey0"])
                ) {

                    $sequenceArray["$valueArrayKey0"][] = $objectKeysArrayKeyi;
                }

                if (!isset($sequenceArray["$objectKeysArrayKeyi"]) || !in_array($valueArrayKey0,
                        $sequenceArray["$objectKeysArrayKeyi"])
                ) {
                    $sequenceArray["$objectKeysArrayKeyi"][] = $valueArrayKey0;
                }

                // Add its distance to the list
                $directionsWithDistanceArray["$valueArrayKey0"]["$objectKeysArrayKeyi"] = trim($valueDistance);
                $distanceArray["$valueArrayKey0" . '-' . "$objectKeysArrayKeyi"] = trim($valueDistance);
                $distanceArray["$objectKeysArrayKeyi" . '-' . "$valueArrayKey0"] = trim($valueDistance);
            }
        }
    }

    return array(
        "map" => $sequenceArray,
        "distance" => $distanceArray,
        "directions" => $directionsArray,
        "directionWDistance" => $directionsWithDistanceArray
    );
}

?>
