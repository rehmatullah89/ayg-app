<?php

use App\Consumer\Entities\Order;
use Parse\ParseClient;
use Parse\ParseQuery;
use Parse\ParseObject;
use Parse\ParseUser;
use Parse\ParseFile;

function wasUserShownFullfillmentTimeRange($userDevice)
{

    if (empty($userDevice)) {

        return false;
    }

    if ($userDevice->get('isIos')) {

        $configMinAppForFullfillmentEstimateRange = getConfigValue("minAppForFullfillmentEstimateRangeiOS");
    } else {

        $configMinAppForFullfillmentEstimateRange = getConfigValue("minAppForFullfillmentEstimateRangeAndroid");
    }

    $configMinAppForFullfillmentEstimateRange = intval(str_replace('.', '', $configMinAppForFullfillmentEstimateRange));
    $deviceAppVersion = intval(str_replace('.', '', $userDevice->get('appVersion')));

    if ($configMinAppForFullfillmentEstimateRange > $deviceAppVersion) {

        return false;
    }

    return true;
}

function pullMenuForCompare($retailerUniqueId)
{

    $menuInDB = [];
    $items = parseExecuteQuery([
        "uniqueRetailerId" => $retailerUniqueId,
        "isActive" => true,
        "__DNE__inactiveTimestamp" => true
    ], "RetailerItems", "", "", ["taxCategory"], 10000, true);
    foreach ($items as $item) {

        $itemId = empty($item->get("itemId")) ? $item->get("uniqueId") : $item->get("itemId");

        $menuInDB[$itemId] = [
            "itemId" => $itemId,
            "itemCategoryName" => $item->get("itemCategoryName"),
            "itemSecondCategoryName" => $item->get("itemSecondCategoryName"),
            "itemThirdCategoryName" => $item->get("itemThirdCategoryName"),
            "itemPOSName" => $item->get("itemPOSName"),
            "isActive" => ($item->get("isActive") == true ? "Y" : "N"),
            "itemDisplayDescription" => $item->get("itemDisplayDescription"),
            "itemPrice" => $item->get("itemPrice"),
            "uniqueId" => $item->get("uniqueId"),
            "uniqueRetailerId" => $item->get("uniqueRetailerId"),
            "itemDisplaySequence" => $item->get("itemDisplaySequence"),
            "itemTags" => $item->get("itemTags"),
            "itemDisplayName" => $item->get("itemDisplayName"),
            "allowedThruSecurity" => ($item->get("allowedThruSecurity") == true ? "Y" : "N"),
            "priceLevelId" => $item->get("priceLevelId"),
            "taxCategory" => ($item->has("taxCategory") ? $item->get("taxCategory")->get("categoryId") : ""),
            "itemImageURL" => $item->get("itemImageURL")
        ];

        // JMD
        $modifiers = parseExecuteQuery([
            "uniqueRetailerItemId" => $item->get("uniqueId"),
            "isActive" => true,
            "__DNE__inactiveTimestamp" => true
        ], "RetailerItemModifiers", "", "", [], 10000, true);

        foreach ($modifiers as $modifier) {

            $modifierId = empty($modifier->get("modifierId")) ? $modifier->get("uniqueId") : $modifier->get("modifierId");

            $menuInDB[$itemId]["__modifiers"][$modifierId] = [
                "modifierId" => $modifier->get('modifierId'),
                "modifierPOSName" => $modifier->get('modifierPOSName'),
                "uniqueId" => $modifier->get('uniqueId'),
                "modifierDisplaySequence" => $modifier->get('modifierDisplaySequence'),
                "minQuantity" => $modifier->get('minQuantity'),
                "maxQuantity" => $modifier->get('maxQuantity'),
                "isRequired" => ($modifier->get("isRequired") == true ? "Y" : "N"),
                "uniqueRetailerItemId" => $modifier->get('uniqueRetailerItemId'),
                "isActive" => ($modifier->get("isActive") == true ? "Y" : "N"),
                "modifierDisplayName" => $modifier->get('modifierDisplayName')
            ];

            $options = parseExecuteQuery([
                "uniqueRetailerItemModifierId" => $modifier->get("uniqueId"),
                "isActive" => true,
                "__DNE__inactiveTimestamp" => true
            ], "RetailerItemModifierOptions", "", "", [], 10000, true);

            $alreadyUsedIdsCounter = [];
            foreach ($options as $option) {

                $optionId = empty($option->get("optionId")) ? $option->get("uniqueId") : $option->get("optionId");

                $add = '';
                if (isset($alreadyUsedIdsCounter[$optionId])) {
                    $add = '_' . $alreadyUsedIdsCounter[$optionId];
                }

                $menuInDB[$itemId]["__modifiers"][$modifierId]["__options"][$optionId . $add] = [
                    "optionId" => $option->get('optionId'),
                    "optionPOSName" => $option->get('optionPOSName'),
                    "optionDisplayDescription" => $option->get('optionDisplayDescription'),
                    "uniqueId" => $option->get('uniqueId'),
                    "uniqueRetailerItemModifierId" => $option->get('uniqueRetailerItemModifierId'),
                    "pricePerUnit" => $option->get('pricePerUnit'),
                    "optionDisplayName" => $option->get('optionDisplayName'),
                    "optionDisplaySequence" => intval($option->get('optionDisplaySequence')),
                    "priceLevelId" => $option->get('priceLevelId'),
                    "isActive" => ($option->get("isActive") == true ? "Y" : "N"),
                ];

                if (isset($menuInDB[$itemId]["__modifiers"][$modifierId]["__options"][$optionId])) {
                    $alreadyUsedIdsCounter[$optionId]++;
                }else{
                    $alreadyUsedIdsCounter[$optionId]=1;
                }
            }
        }

        $itemTimes = parseExecuteQuery([
            "uniqueRetailerItemId" => $item->get("uniqueId"),
            "isActive" => true,
            "__DNE__inactiveTimestamp" => true
        ], "RetailerItemProperties", "", "",
            ["prepTimeCategoryGroup1", "prepTimeCategoryGroup2", "prepTimeCategoryGroup3"], 10000, true);

        foreach ($itemTimes as $itemTime) {

            $itemTimeId = $itemTime->get("dayOfWeek");

            /* @todo check if this is possible, changed place
             * $menuInDB[$itemId]["__itemTimes"][$itemTimeId] = [
             */

            if ($itemTime->get('restrictOrderTimeInSecsStart') == -1 && $itemTime->get('restrictOrderTimeInSecsEnd') == -1) {
                $restrictOrderTimes = "-1";
            } else {
                $restrictOrderTimes = parseTimeRangeBackToTime($itemTime->get('restrictOrderTimeInSecsStart')) . ' - ' . parseTimeRangeBackToTime($itemTime->get('restrictOrderTimeInSecsEnd'));
            }
            $menuInDB[$itemId]["__itemTimes"][] = [
                "uniqueRetailerItemId" => $itemTime->get('uniqueRetailerItemId'),
                "dayOfWeek" => $itemTimeId,
                "restrictOrderTimes" => $restrictOrderTimes,

                "prepRestrictTimesGroup1" => (!empty($itemTime->get('prepRestrictTimeInSecsStartGroup1')) ? parseTimeRangeBackToTime($itemTime->get('prepRestrictTimeInSecsStartGroup1')) . ' - ' . parseTimeRangeBackToTime($itemTime->get('prepRestrictTimeInSecsEndGroup1')) : ""),
                "prepTimeCategoryIdGroup1" => ($itemTime->has('prepTimeCategoryGroup1') ? $itemTime->get('prepTimeCategoryGroup1')->get("categoryId") : ""),

                "prepRestrictTimesGroup2" => (!empty($itemTime->get('prepRestrictTimeInSecsStartGroup2')) ? parseTimeRangeBackToTime($itemTime->get('prepRestrictTimeInSecsStartGroup2')) . ' - ' . parseTimeRangeBackToTime($itemTime->get('prepRestrictTimeInSecsEndGroup2')) : ""),
                "prepTimeCategoryIdGroup2" => ($itemTime->has('prepTimeCategoryGroup2') ? $itemTime->get('prepTimeCategoryGroup2')->get("categoryId") : ""),

                "prepRestrictTimesGroup3" => (!empty($itemTime->get('prepRestrictTimeInSecsStartGroup3')) ? parseTimeRangeBackToTime($itemTime->get('prepRestrictTimeInSecsStartGroup3')) . ' - ' . parseTimeRangeBackToTime($itemTime->get('prepRestrictTimeInSecsEndGroup3')) : ""),
                "prepTimeCategoryIdGroup3" => ($itemTime->has('prepTimeCategoryGroup3') ? $itemTime->get('prepTimeCategoryGroup3')->get("categoryId") : ""),

                "isActive" => ($itemTime->get("isActive") == true ? "Y" : "N"),
            ];
        }
    }

    return $menuInDB;
}

function parseTimeRangeBackToTime($seconds)
{

    $timestamp = strtotime("May 1 2017 midnight");
    return date("g:i A", $timestamp + $seconds);
}

function compareMenusForLoadingDeactiveItem($combinedMenu, $itemId, $itemArray, $inactiveTimestamp)
{

    $combinedMenu[$itemId]["isActive"] = "N";
    $combinedMenu[$itemId]["inactiveTimestamp"] = $inactiveTimestamp;

    if (isset($itemArray["__itemTimes"])) {

        foreach ($itemArray["__itemTimes"] as $dayOfWeek => $itemTimesArray) {

            $combinedMenu = compareMenusForLoadingDeactiveItemTime($combinedMenu, $itemId, $dayOfWeek, $itemTimesArray,
                $inactiveTimestamp);
        }
    }

    if (isset($itemArray["__modifiers"])) {

        foreach ($itemArray["__modifiers"] as $modifierId => $modifierArray) {

            $combinedMenu = compareMenusForLoadingDeactiveModifier($combinedMenu, $itemId, $modifierId, $modifierArray,
                $inactiveTimestamp);
        }
    }

    return $combinedMenu;
}

function compareMenusForLoadingDeactiveItemTime($combinedMenu, $itemId, $dayOfWeek, $itemTimes, $inactiveTimestamp)
{

    $combinedMenu[$itemId]["__itemTimes"][$dayOfWeek]["isActive"] = "N";
    $combinedMenu[$itemId]["__itemTimes"][$dayOfWeek]["inactiveTimestamp"] = $inactiveTimestamp;

    return $combinedMenu;
}

function compareMenusForLoadingDeactiveModifier($combinedMenu, $itemId, $modifierId, $array, $inactiveTimestamp)
{

    $combinedMenu[$itemId]["__modifiers"][$modifierId]["isActive"] = "N";
    $combinedMenu[$itemId]["__modifiers"][$modifierId]["inactiveTimestamp"] = $inactiveTimestamp;

    if (isset($array["__options"])) {

        foreach ($array["__options"] as $optionId => $optionsArray) {

            $combinedMenu = compareMenusForLoadingDeactiveModifierOption($combinedMenu, $itemId, $modifierId, $optionId,
                $optionsArray, $inactiveTimestamp);
        }
    }

    return $combinedMenu;
}

// JMD
function compareMenusForLoadingDeactiveModifierOption(
    $combinedMenu,
    $itemId,
    $modifierId,
    $optionId,
    $options,
    $inactiveTimestamp
) {

    $combinedMenu[$itemId]["__modifiers"][$modifierId]["__options"][$optionId]["isActive"] = "N";
    $combinedMenu[$itemId]["__modifiers"][$modifierId]["__options"][$optionId]["inactiveTimestamp"] = $inactiveTimestamp;

    return $combinedMenu;
}

function isApprovedItemFrom3rdParty($uniqueId, $uniqueRetailerId, $objectParseQueryRetailerItems3rdPartyApprovals)
{

    // $results = parseExecuteQuery(["uniqueId" => $uniqueId, "uniqueRetailerId" => $uniqueRetailerId, "approved" => true], "RetailerItems3rdPartyApprovals");

    $isApproved = false;
    foreach ($objectParseQueryRetailerItems3rdPartyApprovals as $object) {

        if (strcasecmp($object->get('uniqueId'), $uniqueId) == 0
            && strcasecmp($object->get('uniqueRetailerId'), $uniqueRetailerId) == 0
        ) {

            if ($object->get('approved') == true) {

                $isApproved = true;
            }

            break;
        }
    }

    // JMD
    // if(count_like_php5($results) == 0) {

    //     return false;
    // }

    // return true;

    return $isApproved;
}

// JMD
function compareMenusForLoading(
    $newMenu,
    $oldMenu,
    $uniqueRetailerId,
    $partner,
    $inactiveTimestamp,
    $retailerItems3rdPartyApprovals,
    $objectParseQueryRetailerItems3rdPartyApprovals
) {

    $retailerInfo = getRetailerInfo($uniqueRetailerId);

    $deletedItems = [];
    $deletedModifiers = [];
    $deletedOptions = [];
    $deletedItemTimes = [];

    $addedItems = [];
    $addedModifiers = [];
    $addedOptions = [];
    $addedItemTimes = [];

    $updatedItems = [];
    $updatedModifiers = [];
    $updatedOptions = [];
    $updatedItemTimes = [];

    $itemsTo86 = [];
    $itemsToUn86 = [];

    // JMD
    $combinedMenu = $oldMenu;
    $oldVersionedMenu = [];

    $objectKeyFromRetailerItems3rdPartyApprovals = array(
        "item" => array(
            "itemSecondCategoryName",
            "itemThirdCategoryName",
            "itemDisplayName",
            "itemDisplayDescription",
            "itemImageURL",
            "itemDisplaySequence",
            "itemTags",
            "taxCategory",
            "allowedThruSecurity"
        ),
    );

    // Update new menu with RetailerItems3rdParty data
    if (!empty($retailerItems3rdPartyApprovals)) {
        foreach ($newMenu as $itemId => $itemArray) {

            foreach ($objectKeyFromRetailerItems3rdPartyApprovals["item"] as $keyToUseCustomValue) {

                $uniqueItemId = $itemArray["uniqueId"];

                if ($retailerItems3rdPartyApprovals[$uniqueItemId]->has($keyToUseCustomValue)
                    && !empty($retailerItems3rdPartyApprovals[$uniqueItemId]->get($keyToUseCustomValue))
                ) {

                    $newMenu[$itemId][$keyToUseCustomValue] = $retailerItems3rdPartyApprovals[$uniqueItemId]->get($keyToUseCustomValue);
                }
            }
        }
    }

    // Items deleted
    // Y earlier but now N => delete it
    // N earlier and now N => ignore it
    // N earlier and now Y => insert (already there)
    foreach ($oldMenu as $itemId => $itemArray) {

        if (!in_array($itemId, array_keys($newMenu))
            || (isset($newMenu[$itemId]) && $newMenu[$itemId]["isActive"] == "N")
            || !isApprovedItemFrom3rdParty($itemArray["uniqueId"], $itemArray["uniqueRetailerId"],
                $objectParseQueryRetailerItems3rdPartyApprovals)
            // This should not needed since oldMenu will exclude it
            || $itemArray["isActive"] == "N"
        ) {

            // s3logMenuLoader(printLogTime() . "Deleted Item: " . $itemId . "\r\n");
            $deletedItems[$itemId] = true;

            // Delete all itemTimes, modifiers and options
            $combinedMenu = compareMenusForLoadingDeactiveItem($combinedMenu, $itemId, $oldMenu[$itemId],
                $inactiveTimestamp);
        } else {

            // Item Times deleted
            if (isset($itemArray["__itemTimes"])) {
                foreach ($itemArray["__itemTimes"] as $dayOfWeek => $itemTimesArray) {

                    if (!in_array($dayOfWeek, array_keys($newMenu[$itemId]["__itemTimes"]))) {

                        // s3logMenuLoader(printLogTime() . "Deleted Item Time: " . $dayOfWeek . "\r\n");
                        $deletedItemTimes[$itemId][$dayOfWeek] = true;

                        $combinedMenu = compareMenusForLoadingDeactiveItemTime($combinedMenu, $itemId, $dayOfWeek,
                            $oldMenu[$itemId]["__itemTimes"][$dayOfWeek], $inactiveTimestamp);
                    }
                }
            }

            // Modifiers deleted
            if (isset($itemArray["__modifiers"])) {
                foreach ($itemArray["__modifiers"] as $modifierId => $modifierArray) {

                    if (
                        (isset($newMenu[$itemId]["__modifiers"]) && !in_array($modifierId,
                                array_keys($newMenu[$itemId]["__modifiers"])))
                        || (isset($newMenu[$itemId]["__modifiers"][$modifierId]) && $newMenu[$itemId]["__modifiers"][$modifierId]["isActive"] == "N")
                        || $modifierArray["isActive"] == "N"
                    ) {

                        // s3logMenuLoader(printLogTime() . "Deleted Modifier: " . $modifierId . "\r\n");
                        $deletedModifiers[$itemId][$modifierId] = true;

                        // JMD
                        $combinedMenu = compareMenusForLoadingDeactiveModifier($combinedMenu, $itemId, $modifierId,
                            $oldMenu[$itemId]["__modifiers"][$modifierId], $inactiveTimestamp);
                        // $combinedMenu[$itemId]["__modifiers"][$modifierId]["isActive"] = "N";
                        // $combinedMenu[$itemId]["__modifiers"][$modifierId]["inactiveTimestamp"] = $inactiveTimestamp;

                        // // Delete Options for this Modifier
                        // if(isset($modifierArray["__options"]))
                        // $combinedMenu = compareMenusForLoadingDeactiveModifierOption($combinedMenu, $modifierArray["__options"], $itemId, $modifierId, $inactiveTimestamp);
                    } else {

                        // Options deleted
                        if (isset($modifierArray["__options"])) {
                            foreach ($modifierArray["__options"] as $optionId => $optionArray) {

                                if (
                                    // Option is not found in the new menu
                                    (isset($newMenu[$itemId]["__modifiers"][$modifierId]["__options"]) && !in_array($optionId,
                                            array_keys($newMenu[$itemId]["__modifiers"][$modifierId]["__options"])))
                                    ||
                                    // Option is found in new menu, but is set to isActive = N
                                    (
                                        isset($newMenu[$itemId]["__modifiers"][$modifierId]["__options"][$optionId])
                                        && strcasecmp($newMenu[$itemId]["__modifiers"][$modifierId]["__options"][$optionId]["isActive"],
                                            "N") == 0
                                    )
                                    // Option is found in the old menu, but is st to isActive = N
                                    || strcasecmp($optionArray["isActive"], "N") == 0
                                ) {

                                    // s3logMenuLoader(printLogTime() . "Deleted Option: " . $optionId . "\r\n");
                                    $deletedOptions[$itemId][$modifierId][$optionId] = true;

                                    $combinedMenu = compareMenusForLoadingDeactiveModifierOption($combinedMenu, $itemId,
                                        $modifierId, $optionId,
                                        $oldMenu[$itemId]["__modifiers"][$modifierId]["__options"][$optionId],
                                        $inactiveTimestamp);
                                    // $combinedMenu[$itemId]["__modifiers"][$modifierId]["__options"][$optionId]["isActive"] = "N";
                                    // $combinedMenu[$itemId]["__modifiers"][$modifierId]["__options"][$optionId]["inactiveTimestamp"] = $inactiveTimestamp;
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    $retailerInfoForDisplay = getRetailerInfoForDisplay($retailerInfo);
    $airportIataCode = $retailerInfo["airportIataCode"];
    // JMD
    $retailerDirectoryName = getRetailerS3MenuDirectoryName($retailerInfo);

    // New Items added
    foreach ($newMenu as $itemId => $itemArray) {

        if (strcasecmp($itemArray["86item"], "Y") == 0) {

            $itemDetail = [
                "retailerName" => $retailerInfo["retailerName"],
                "location" => $retailerInfo["location"]["locationDisplayName"],
                "itemName" => !empty($itemArray["itemDisplayName"]) ? $itemArray["itemDisplayName"] : $itemArray["itemPOSName"],
                "uniqueRetailerItemId" => $itemArray["uniqueId"]
            ];

            $itemsTo86[$itemId] = $itemDetail;
        } else {
            if (isItem86isedFortheDay($itemArray["uniqueId"])) {

                $itemDetail = [
                    "retailerName" => $retailerInfo["retailerName"],
                    "location" => $retailerInfo["location"]["locationDisplayName"],
                    "itemName" => !empty($itemArray["itemDisplayName"]) ? $itemArray["itemDisplayName"] : $itemArray["itemPOSName"],
                    "uniqueRetailerItemId" => $itemArray["uniqueId"]
                ];

                $itemsToUn86[$itemId] = $itemDetail;
            }
        }

        // Check if this item was previously added but now deactivated?
        // If so, skip it
        if (!isApprovedItemFrom3rdParty($itemArray["uniqueId"], $itemArray["uniqueRetailerId"],
            $objectParseQueryRetailerItems3rdPartyApprovals)
        ) {

            continue;
        }

        // Store them but don't add them till manually approved
        if (!in_array($itemId, array_keys($oldMenu))) {

            // s3logMenuLoader(printLogTime() . "New Item: " . $itemId . "\r\n");
            // Item was N and is now N too
            if ((!isset($oldMenu[$itemId]) || $oldMenu[$itemId]["isActive"] == "N")
                && $itemArray["isActive"] == "N"
            ) {

            } else {

                $addedItems[$itemId] = true;
            }

            // Check if this item was previously added but now deactivated?
            // If so, directly use it
            if (isApprovedItemFrom3rdParty($itemArray["uniqueId"], $itemArray["uniqueRetailerId"],
                $objectParseQueryRetailerItems3rdPartyApprovals)) {

                // Process like an update but find the old deactivated row
                // If no deactivated row, then just add instead
            }
        } // Let's identify and add any new modifiers, options and times for this item
        else {

            if (isset($itemArray["__itemTimes"])) {
                foreach ($itemArray["__itemTimes"] as $dayOfWeek => $itemTimesArray) {

                    if (!isset($oldMenu[$itemId]["__itemTimes"])) {

                        $oldMenu[$itemId]["__itemTimes"] = [];
                    }

                    if (!in_array($dayOfWeek, array_keys($oldMenu[$itemId]["__itemTimes"]))) {

                        // s3logMenuLoader(printLogTime() . "New Item Times: " . $dayOfWeek . "\r\n");
                        $addedItemTimes[$itemId][$dayOfWeek] = true;
                        $combinedMenu[$itemId]["__itemTimes"][$dayOfWeek] = $itemTimesArray;
                    }
                }
            }

            if (isset($itemArray["__modifiers"])) {
                foreach ($itemArray["__modifiers"] as $modifierId => $modifierArray) {

                    if (!isset($oldMenu[$itemId]["__modifiers"])) {

                        $oldMenu[$itemId]["__modifiers"] = [];
                    }

                    if (!in_array($modifierId, array_keys($oldMenu[$itemId]["__modifiers"]))) {

                        // s3logMenuLoader(printLogTime() . "New Modifier: " . $modifierId . "\r\n");
                        // Modifier was N and is now N too
                        if ((!isset($oldMenu[$itemId]["__modifiers"][$modifierId]) || $oldMenu[$itemId]["__modifiers"][$modifierId]["isActive"] == "N")
                            && $modifierArray["isActive"] == "N"
                        ) {

                        } else {

                            // $addedItems[$itemId] = true;
                            $addedModifiers[$itemId][$modifierId] = true;
                        }

                        $combinedMenu[$itemId]["__modifiers"][$modifierId] = $modifierArray;
                    } // If Modifier was there, then let's check for Options
                    else {

                        if (isset($modifierArray["__options"])) {
                            foreach ($modifierArray["__options"] as $optionId => $optionArray) {

                                if (!isset($oldMenu[$itemId]["__modifiers"][$modifierId]["__options"])) {

                                    $oldMenu[$itemId]["__modifiers"][$modifierId]["__options"] = [];
                                }

                                if (!in_array($optionId,
                                    array_keys($oldMenu[$itemId]["__modifiers"][$modifierId]["__options"]))
                                ) {

                                    // s3logMenuLoader(printLogTime() . "New Option: " . $optionId . "\r\n");

                                    if ((!isset($oldMenu[$itemId]["__modifiers"][$modifierId]["__options"][$optionId]) || $oldMenu[$itemId]["__modifiers"][$modifierId]["__options"][$optionId]["isActive"] == "N")
                                        && $optionArray["isActive"] == "N"
                                    ) {

                                    } else {

                                        // $addedItems[$itemId] = true;
                                        $addedOptions[$itemId]["__modifiers"][$modifierId]["__options"][$optionId] = true;
                                    }

                                    $combinedMenu[$itemId]["__modifiers"][$modifierId]["__options"][$optionId] = $optionArray;
                                }
                            }
                        }
                    }
                }
            }

            // s3logMenuLoader(printLogTime() . "Existing Item: " . $itemId . "\r\n");
        }
    }

    // JMD
    // Items updated
    // Force new version if these fields are updated
    foreach ($newMenu as $itemId => $itemArray) {

        $newVersionNeededForItem = false;
        if (isset($combinedMenu[$itemId])
            && !in_array($itemId, array_keys($deletedItems))
        ) {

            // Item needs a new version, that is the main columns were updated
            $newVersionNeededForItem = isNewVersionNeeded($newMenu[$itemId], $combinedMenu[$itemId],
                $GLOBALS['__menuLoaderConfig'][$partner]['forceNewVersionForItem']);

            // Item columns compare
            if ($newVersionNeededForItem == true
                || haveColumnsBeenUpdated($newMenu[$itemId], $combinedMenu[$itemId],
                    $GLOBALS['__menuLoaderConfig'][$partner]['columnsToUpdateForItem'])
            ) {

                $updatedItems[$itemId] = true;

                // list($combinedMenu[$itemId], $oldVersionedMenu[$itemId]) = updateItemInMenu($newMenu[$itemId], $combinedMenu[$itemId], $GLOBALS['__menuLoaderConfig'][$partner]['columnsToUpdateForItem'], $newVersionNeededForItem, $inactiveTimestamp);

                if ($newVersionNeededForItem == false) {

                    unset($oldVersionedMenu[$itemId]);
                }
            } // If item not updated, let's check if times, modifiers or options were
            else {

                // Item Times updated
                if (isset($itemArray["__itemTimes"])) {
                    foreach ($itemArray["__itemTimes"] as $dayOfWeek => $itemTimesArray) {

                        // Make sure these itemTimes was not in the deleted list
                        if (isset($combinedMenu[$itemId]["__itemTimes"][$dayOfWeek])
                            && (!isset($deletedItemTimes[$itemId]) || !in_array($dayOfWeek,
                                    array_keys($deletedItemTimes[$itemId])))
                        ) {

                            if (haveColumnsBeenUpdated($newMenu[$itemId]["__itemTimes"][$dayOfWeek],
                                $combinedMenu[$itemId]["__itemTimes"][$dayOfWeek],
                                $GLOBALS['__menuLoaderConfig'][$partner]['columnsToUpdateForItemTimes'])) {

                                $updatedItemTimes[$itemId][$dayOfWeek] = true;

                                // Not removed; Item Times are in place update; new version NOT created
                                // $oldVersionedMenu[$itemId]["__itemTimes"][$dayOfWeek]

                                $combinedMenu[$itemId]["__itemTimes"][$dayOfWeek] = updateItemTimeInMenu($newMenu[$itemId]["__itemTimes"][$dayOfWeek],
                                    $combinedMenu[$itemId]["__itemTimes"][$dayOfWeek],
                                    $GLOBALS['__menuLoaderConfig'][$partner]['columnsToUpdateForItemTimes'], false,
                                    $inactiveTimestamp);
                            }
                        }
                    }
                }

                // Item Modifiers updated
                if (isset($itemArray["__modifiers"])) {
                    foreach ($itemArray["__modifiers"] as $modifierId => $modifierArray) {

                        $newVersionNeededForModifier = false;

                        // Make sure these modifiers were not in the deleted list
                        if (isset($combinedMenu[$itemId]["__modifiers"][$modifierId])
                            && (!isset($deletedModifiers[$itemId]) || !in_array($modifierId,
                                    array_keys($deletedModifiers[$itemId])))
                        ) {

                            // Modifier a new version, that is the main columns were updated
                            $newVersionNeededForModifier = isNewVersionNeeded($newMenu[$itemId]["__modifiers"][$modifierId],
                                $combinedMenu[$itemId]["__modifiers"][$modifierId],
                                $GLOBALS['__menuLoaderConfig'][$partner]['forceNewVersionForModifier']);

                            if ($newVersionNeededForModifier == true
                                || haveColumnsBeenUpdated($newMenu[$itemId]["__modifiers"][$modifierId],
                                    $combinedMenu[$itemId]["__modifiers"][$modifierId],
                                    $GLOBALS['__menuLoaderConfig'][$partner]['columnsToUpdateForModifiers'])
                            ) {

                                if ($newVersionNeededForModifier == true) {

                                    $temp = compareMenusForLoadingDeactiveModifier($combinedMenu, $itemId, $modifierId,
                                        $combinedMenu[$itemId]["__modifiers"][$modifierId], $inactiveTimestamp);

                                    $oldVersionedMenu[$itemId]["__modifiers"][$modifierId] = $temp[$itemId]["__modifiers"][$modifierId];
                                }

                                $updatedModifiers[$itemId][$modifierId] = true;

                                $combinedMenu[$itemId]["__modifiers"][$modifierId] = updateModifierInMenu($newMenu[$itemId]["__modifiers"][$modifierId],
                                    $combinedMenu[$itemId]["__modifiers"][$modifierId],
                                    $GLOBALS['__menuLoaderConfig'][$partner]['columnsToUpdateForModifiers'],
                                    $newVersionNeededForModifier, $inactiveTimestamp);
                            } // Update any Option level changes
                            else {

                                // Item Modifier Options updated
                                if (isset($modifierArray["__options"])) {
                                    foreach ($modifierArray["__options"] as $optionId => $optionArray) {

                                        $newVersionNeededForOption = false;

                                        // Make sure these options were not in the deleted list
                                        if (isset($combinedMenu[$itemId]["__modifiers"][$modifierId]["__options"][$optionId])
                                            && (!isset($deletedModifierOptions[$itemId]) || !in_array($optionId,
                                                    array_keys($deletedModifierOptions[$itemId])))
                                        ) {

                                            // Option a new version, that is the main columns were updated
                                            $newVersionNeededForOption = isNewVersionNeeded($newMenu[$itemId]["__modifiers"][$modifierId]["__options"][$optionId],
                                                $combinedMenu[$itemId]["__modifiers"][$modifierId]["__options"][$optionId],
                                                $GLOBALS['__menuLoaderConfig'][$partner]['forceNewVersionForModifierOption']);

                                            if ($newVersionNeededForOption == true
                                                || haveColumnsBeenUpdated($newMenu[$itemId]["__modifiers"][$modifierId]["__options"][$optionId],
                                                    $combinedMenu[$itemId]["__modifiers"][$modifierId]["__options"][$optionId],
                                                    $GLOBALS['__menuLoaderConfig'][$partner]['columnsToUpdateForModifierOptions'])
                                            ) {

                                                if ($newVersionNeededForOption == true) {

                                                    $temp = compareMenusForLoadingDeactiveModifierOption($combinedMenu,
                                                        $itemId, $modifierId, $optionId,
                                                        $combinedMenu[$itemId]["__modifiers"][$modifierId]["__options"][$optionId],
                                                        $inactiveTimestamp);

                                                    $oldVersionedMenu[$itemId]["__modifiers"][$modifierId]["__options"][$optionId] = $temp[$itemId]["__modifiers"][$modifierId]["__options"][$optionId];
                                                }

                                                $updatedOptions[$itemId][$modifierId][$optionId] = true;

                                                $combinedMenu[$itemId]["__modifiers"][$modifierId]["__options"][$optionId] = updateModifierOptionInMenu($newMenu[$itemId]["__modifiers"][$modifierId]["__options"][$optionId],
                                                    $combinedMenu[$itemId]["__modifiers"][$modifierId]["__options"][$optionId],
                                                    $GLOBALS['__menuLoaderConfig'][$partner]['columnsToUpdateForModifierOptions'],
                                                    $newVersionNeededForModifier, $inactiveTimestamp);
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    // Delete Items - Mark these as N
    $itemsToDelete = array_unique(
        array_merge(
            array_keys($deletedItems) +
            // JMD
            array_keys($deletedModifiers) +
            array_keys($deletedOptions) +
            array_keys($deletedItemTimes)
        )
    );

    // Mark these as N
    // Then check 3rd party table:
    // If approved, insert with 3rd party data
    $itemsToUpdate = array_unique(
        array_merge(
            array_keys($updatedItems) +
            array_keys($updatedModifiers) +
            array_keys($updatedOptions) +
            array_keys($updatedItemTimes)
        )
    );

    // Then check 3rd party table:
    // If approved, insert with 3rd party data
    $itemsToInsert = array_unique(
        array_merge(
            array_keys($addedItems),
            array_keys($addedModifiers),
            array_keys($addedOptions),
            array_keys($addedItemTimes),

            // These items need to be added again since we are deleting them
            array_keys($deletedModifiers),
            array_keys($deletedOptions),
            array_keys($deletedItemTimes)
        )
    );

    foreach ($itemsToInsert as $itemCounter => $itemId) {

        if (!isset($newMenu[$itemId]["uniqueId"])) {

            continue;
        }

        $uniqueItemId = $newMenu[$itemId]["uniqueId"];
        if (isset($retailerItems3rdPartyApprovals[$uniqueItemId])
            && $retailerItems3rdPartyApprovals[$uniqueItemId]->get("approved") == false
        ) {

            unset($itemsToInsert[$itemCounter]);
        }
    }

    foreach ($itemsTo86 as $itemId => $itemDetails) {

        if (!isset($newMenu[$itemId]["uniqueId"])) {

            continue;
        }

        $uniqueItemId = $newMenu[$itemId]["uniqueId"];
        if (isset($retailerItems3rdPartyApprovals[$uniqueItemId])
            && $retailerItems3rdPartyApprovals[$uniqueItemId]->get("approved") == false
        ) {

            unset($itemsTo86[$itemId]);
        }

        if (isItem86isedFortheDay($itemDetail["uniqueRetailerItemId"])) {

            unset($itemsTo86[$itemId]);
            continue;
        }
    }

    foreach ($itemsToUn86 as $itemId => $itemDetails) {

        if (!isset($newMenu[$itemId]["uniqueId"])) {

            continue;
        }

        $uniqueItemId = $newMenu[$itemId]["uniqueId"];

        if (isset($retailerItems3rdPartyApprovals[$uniqueItemId])
            && $retailerItems3rdPartyApprovals[$uniqueItemId]->get("approved") == false
        ) {

            unset($itemsToUn86[$itemId]);
            continue;
        }

        if (!isItem86isedFortheDay($itemDetail["uniqueRetailerItemId"])) {

            unset($itemsToUn86[$itemId]);
            continue;
        }
    }

    return [$itemsTo86, $itemsToUn86, $itemsToInsert, $itemsToUpdate, $itemsToDelete, $newMenu];
}

function menuLoader86Items($itemsTo86, $retailerInfo)
{

    $item86ishMessages = [];
    $count = 0;
    foreach ($itemsTo86 as $itemDetail) {

        if (isItem86isedFortheDay($itemDetail["uniqueRetailerItemId"])) {

            continue;
        }

        $count++;

        s3logMenuLoader(printLogTime() . "-- " . $itemDetail["itemName"] . " - " . $itemDetail["uniqueRetailerItemId"] . "\r\n");
        setItem86isedFortheDay($itemDetail["uniqueRetailerItemId"], $itemDetail);//#
        $item86ishMessages = array_merge($item86ishMessages, [
            "Unique Id " . $count => $itemDetail["uniqueRetailerItemId"],
            "Item Name " . $count => $itemDetail["itemName"]
        ]);
    }

    if (count_like_php5($item86ishMessages) > 0) {

        notifyOnSlackMenuUpdates($retailerInfo["uniqueId"], "Item 86isd (Automated)", ["Number of items" => $count]);
    }

    return $count;
}

function menuLoaderUn86Items($itemsToUn86, $retailerInfo)
{

    $itemUn86ishMessages = [];
    $count = 0;
    foreach ($itemsToUn86 as $itemDetail) {

        if (!isItem86isedFortheDay($itemDetail["uniqueRetailerItemId"])) {

            continue;
        }

        $count++;

        s3logMenuLoader(printLogTime() . "-- " . $itemDetail["itemName"] . " - " . $itemDetail["uniqueRetailerItemId"] . "\r\n");
        delItem86isedFortheDay($itemDetail["uniqueRetailerItemId"]);//#
        $itemUn86ishMessages = array_merge($itemUn86ishMessages, [
            "Unique Id " . $count => $itemDetail["uniqueRetailerItemId"],
            "Item Name " . $count => $itemDetail["itemName"]
        ]);
    }

    if (count_like_php5($itemUn86ishMessages) > 0) {

        notifyOnSlackMenuUpdates($retailerInfo["uniqueId"], "Item Un 86isd (Automated)", ["Number of items" => $count]);
    }

    return $count;
}

function updateMenuUpdateItems(
    $retailerUniqueId,
    $itemsToUpdate,
    $menu,
    $partner,
    $inactiveTimestamp,
    $airportIataCode,
    $retailerDirectoryName,
    $retailerInfo,
    $isRetailerClosed,
    $retailerItems3rdPartyApprovals,
    $objectParseQueryRetailerItems3rdPartyApprovals,
    $imagePath = ''
) {

    s3logMenuLoader(printLogTime() . "----------------------------------" . "\r\n");
    s3logMenuLoader(printLogTime() . "Updating Items (" . count_like_php5($itemsToUpdate) . ")" . "\r\n");
    s3logMenuLoader(printLogTime() . "----------------------------------" . "\r\n");

    s3logMenuLoader(printLogTime() . "----------------------------------" . "\r\n");
    s3logMenuLoader(printLogTime() . "Stage 1 - Delete existing items" . "\r\n");
    s3logMenuLoader(printLogTime() . "----------------------------------" . "\r\n");

    updateMenuDeleteItems($retailerUniqueId, $itemsToUpdate, $inactiveTimestamp);


    s3logMenuLoader(printLogTime() . "----------------------------------" . "\r\n");
    s3logMenuLoader(printLogTime() . "Stage 2 - Insert new items" . "\r\n");
    s3logMenuLoader(printLogTime() . "----------------------------------" . "\r\n");

    foreach ($itemsToUpdate as $itemCounter => $itemId) {

        if (isset($retailerItems3rdPartyApprovals[$itemId])
            && $retailerItems3rdPartyApprovals[$itemId]->get("approved") == false
        ) {

            s3logMenuLoader(printLogTime() . "Skipping - " . $itemId . ", not approved" . "\r\n");
            unset($itemsToUpdate[$itemCounter]);
        }
    }

    return updateMenuInsertItems($retailerUniqueId, $itemsToUpdate, $menu, $partner, $airportIataCode,
        $retailerDirectoryName, $retailerInfo, $isRetailerClosed, $retailerItems3rdPartyApprovals,
        $objectParseQueryRetailerItems3rdPartyApprovals, $imagePath);
}

function updateMenuInsertItems(
    $retailerUniqueId,
    $itemsToInsert,
    $menu,
    $partner,
    $airportIataCode,
    $retailerDirectoryName,
    $retailerInfo,
    $isRetailerClosed,
    $retailerItems3rdPartyApprovals,
    $objectParseQueryRetailerItems3rdPartyApprovals,
    $imagePath = ''
) {

    $objectKeyIsArrayCustom = array(
        "item" => array(
            "itemCategoryName" => "N",
            "itemSecondCategoryName" => "N",
            "itemThirdCategoryName" => "N",
            "itemPOSName" => "N",
            "itemDisplayName" => "N",
            "itemDisplayDescription" => "N",
            "itemId" => "N",
            "itemPrice" => "I",
            "priceLevelId" => "N",
            "isActive" => "N",
            "uniqueRetailerId" => "N",
            "uniqueId" => "N",
            "itemImageURL" => "N",
            "itemDisplaySequence" => "I",
            "itemTags" => "N",
            "taxCategory" => "N",
            "allowedThruSecurity" => "N"
        ),
        "modifier" => array(
            "modifierPOSName" => "N",
            "modifierDisplayName" => "N",
            "modifierDisplayDescription" => "N",
            "modifierDisplaySequence" => "I",
            "modifierId" => "N",
            "maxQuantity" => "I",
            "minQuantity" => "I",
            "isRequired" => "N",
            "isActive" => "N",
            "uniqueRetailerItemId" => "N",
            "uniqueId" => "N"
        ),
        "option" => array(
            "optionPOSName" => "N",
            "optionDisplayName" => "N",
            "optionDisplayDescription" => "N",
            "optionDisplaySequence" => "I",
            "optionId" => "N",
            "pricePerUnit" => "I",
            "priceLevelId" => "N",
            "isActive" => "N",
            "uniqueRetailerItemModifierId" => "N",
            "uniqueId" => "N"
        ),
        "time" => array(
            "uniqueRetailerItemId" => "N",
            "dayOfWeek" => "I",
            "restrictOrderTimes" => "X",
            "prepRestrictTimesGroup1" => "X",
            "prepTimeCategoryIdGroup1" => "X",
            "prepRestrictTimesGroup2" => "X",
            "prepTimeCategoryIdGroup2" => "X",
            "prepRestrictTimesGroup3" => "X",
            "prepTimeCategoryIdGroup3" => "X",
            "isActive" => "N"
        ),
    );

    $objectKeyFromRetailerItems3rdPartyApprovals = array(
        "item" => array(
            "itemSecondCategoryName",
            "itemThirdCategoryName",
            "itemDisplayName",
            "itemDisplayDescription",
            "itemImageURL",
            "itemDisplaySequence",
            "itemTags",
            "taxCategory",
            "allowedThruSecurity"
        ),
    );

    $referenceLookup = array(
        "item" => array(
            "taxCategory" => array(
                "className" => "RetailerItemTaxCategory",
                "isRequired" => false,
                "lookupCols" => array(
                    // Column in ClassName => Column in File
                    "categoryId" => "taxCategory",
                ),
            ),
        ),
        "modifier" => array(),
        "option" => array(),
        "time" => array(
            "prepTimeCategoryGroup1" => array(
                "className" => "RetailerItemPrepTimeCategory",
                "isRequired" => false,
                "lookupCols" => array(
                    // Column in ClassName => Column in File
                    "categoryId" => "prepTimeCategoryIdGroup1",
                )
            ),
            "prepTimeCategoryGroup2" => array(
                "className" => "RetailerItemPrepTimeCategory",
                "isRequired" => false,
                "lookupCols" => array(
                    // Column in ClassName => Column in File
                    "categoryId" => "prepTimeCategoryIdGroup1",
                )
            ),
            "prepTimeCategoryGroup3" => array(
                "className" => "RetailerItemPrepTimeCategory",
                "isRequired" => false,
                "lookupCols" => array(
                    // Column in ClassName => Column in File
                    "categoryId" => "prepTimeCategoryIdGroup1",
                )
            ),
        )
    );

    $imagesIndexesWithPaths = array(
        "item" => array(
            /*
            "itemImageURL" => [
                "S3KeyPath" => getS3KeyPath_ImagesRetailerItem($airportIataCode),
                "useUniqueIdInName" => "Y",
                "maxWidth" => 1080,
                "maxHeight" => 1920,
                "createThumbnail" => true,
                "imagePath" => $imagesPath
            ]
            */

            "itemImageURL" => [
                "S3KeyPath" => getS3KeyPath_ImagesRetailerItem($airportIataCode),
                "useUniqueIdInName" => "Y",
                "maxWidth" => 1080,
                "maxHeight" => 1920,
                "createThumbnail" => true,
                "imagePath" => $imagePath,
                "sourceImageS3Path" => "",
                "sourceImageIsOnS3" => false
            ]

        ),
        "modifier" => array(),
        "option" => array(),
        "time" => array()
    );

    foreach ($objectKeyIsArrayCustom as $key => $objectKeyList) {

        $objectKeys[$key] = array_keys($objectKeyList);
    }

    // JMD
    s3logMenuLoader(printLogTime() . "----------------------------------" . "\r\n");
    s3logMenuLoader(printLogTime() . "Inserting Items (" . count_like_php5($itemsToInsert) . ")" . "\r\n");
    s3logMenuLoader(printLogTime() . "----------------------------------" . "\r\n");
    $totalItems = count_like_php5($itemsToInsert);

    // JMD
    $itemCounter = 0;
    foreach ($itemsToInsert as $itemId) {

        $itemCounter++;
        s3logMenuLoader(printLogTime() . "- Item: " . $itemCounter . " of " . $totalItems . " - " . $itemId . "..." . "\r\n");

        $itemRowFromMenu = $menu[$itemId];
        $uniqueItemId = $menu[$itemId]["uniqueId"];

        // Fetch Item information from 3rd Party table
        // $retailerItems3rdPartyApprovals = parseExecuteQuery(["uniqueId" => $itemRowFromMenu["uniqueId"]], "RetailerItems3rdPartyApprovals", "", "", [], 1);

        // if(count_like_php5($retailerItems3rdPartyApprovals) == 0) {

        //     s3logMenuLoader(printLogTime() . "- Item: " . $itemId . "...skipped (retailerItems3rdPartyApprovals not found)" . "\r\n");
        //     continue;
        // }

        // // Is item approved to be loaded?
        // if($retailerItems3rdPartyApprovals->get("approved") == false) {

        //     s3logMenuLoader(printLogTime() . "- Item: " . $itemId . "...skipped (not approved)" . "\r\n");
        //     continue;
        // }

        // Is item approved to be loaded?
        if (isset($retailerItems3rdPartyApprovals[$uniqueItemId])
            && $retailerItems3rdPartyApprovals[$uniqueItemId]->get("approved") == false
        ) {

            s3logMenuLoader(printLogTime() . "- Item: " . $itemId . "...skipped (not approved)" . "\r\n");
            continue;
        }

        // Is item approved to be loaded?
        if (!isset($menu[$itemId])) {

            s3logMenuLoader(printLogTime() . "- Item: " . $itemId . "...not in new menu" . "\r\n");
            continue;
        }

        // @todo check here closed
        // managed by higher level (MenuUpdateService)
        /*
        if($isRetailerClosed == false
            && !isRetailerCloseEarlyForNewOrders($retailerInfo["uniqueId"])) {

            $retailerInfoForDisplay = getRetailerInfoForDisplay($retailerInfo);

            s3logMenuLoader(printLogTime() . "----------------------------------" . "\r\n");
            s3logMenuLoader(printLogTime() . "Closing Retailer (" . $retailerInfoForDisplay . ")" . "\r\n");
            s3logMenuLoader(printLogTime() . "----------------------------------" . "\r\n");

            $isRetailerClosed = true;

             setRetailerClosedEarlyTimerMessage($retailerInfo["uniqueId"], getTabletOpenCloseLevelFromSystem());//#
        }
        */

        $itemRow = [];

        // Prepare Item Row
        foreach ($objectKeys["item"] as $key => $keyToLookup) {

            if (isset($itemRowFromMenu[$keyToLookup])) {

                $itemRow[$key] = $itemRowFromMenu[$keyToLookup];
            } else {

                $itemRow[$key] = "";
            }
        }

        // Get rest of the keys if available from RetailerItems3rdPartyApprovals
        /*
        foreach($objectKeyFromRetailerItems3rdPartyApprovals["item"] as $keyToUseCustomValue) {

            $keyToStoreIn = array_search($keyToUseCustomValue, $objectKeys["item"]);

            if($retailerItems3rdPartyApprovals[$uniqueItemId]->has($keyToUseCustomValue)
                && !empty($retailerItems3rdPartyApprovals[$uniqueItemId]->get($keyToUseCustomValue))) {

                $itemRow[$keyToStoreIn] = $retailerItems3rdPartyApprovals[$uniqueItemId]->get($keyToUseCustomValue);
            }
        }
        */
        //////////////////////////////////////////////

        // Add filename
        if (!empty($itemRow[array_search("itemImageURL", $objectKeys["item"])])) {

            $imagesIndexesWithPaths["item"]["itemImageURL"]["sourceImageS3Path"] = getS3KeyPath_RetailerMenuImagesPreLoad($airportIataCode,
                    $partner, $retailerDirectoryName) . '/' . $itemRow[array_search("itemImageURL",
                    $objectKeys["item"])];

        } else {

            $imagesIndexesWithPaths["item"]["itemImageURL"]["sourceImageS3Path"] = "";
        }

        prepareAndPostToParse("", "", "RetailerItems", [$itemRow], $objectKeyIsArrayCustom["item"], $objectKeys["item"],
            "", [], $imagesIndexesWithPaths["item"], $referenceLookup["item"], true);//#

        // Prepare Modifier Row
        if (isset($itemRowFromMenu["__modifiers"])) {

            $modifierRowsFromMenu = $itemRowFromMenu["__modifiers"];
            foreach ($modifierRowsFromMenu as $i => $modifierRowFromMenu) {

                $preparedModifierRows = [];
                s3logMenuLoader(printLogTime() . "-- Modifier: " . $modifierRowFromMenu["modifierId"] . "..." . "\r\n");
                foreach ($objectKeys["modifier"] as $key => $keyToLookup) {

                    if (isset($modifierRowFromMenu[$keyToLookup])) {

                        $preparedModifierRows[$i][$key] = $modifierRowFromMenu[$keyToLookup];
                    } else {

                        $preparedModifierRows[$i][$key] = "";
                    }
                }

                if (count_like_php5($preparedModifierRows) > 0) {

                    prepareAndPostToParse("", "", "RetailerItemModifiers", $preparedModifierRows,
                        $objectKeyIsArrayCustom["modifier"], $objectKeys["modifier"], "", [],
                        $imagesIndexesWithPaths["modifier"], $referenceLookup["modifier"], true);//#
                }

                // Prepare Option Row
                if (isset($modifierRowFromMenu["__options"])) {

                    $preparedOptionRows = [];
                    $optionRowsFromMenu = $modifierRowFromMenu["__options"];
                    foreach ($optionRowsFromMenu as $j => $optionRowFromMenu) {

                        s3logMenuLoader(printLogTime() . "--- Option: " . $optionRowFromMenu["optionId"] . "..." . "\r\n");


                        foreach ($objectKeys["option"] as $key => $keyToLookup) {

                            if (isset($optionRowFromMenu[$keyToLookup])) {

                                $preparedOptionRows[$j][$key] = $optionRowFromMenu[$keyToLookup];
                            } else {

                                $preparedOptionRows[$j][$key] = "";
                            }
                        }
                    }


                    if (count_like_php5($preparedOptionRows) > 0) {


                        prepareAndPostToParse("", "", "RetailerItemModifierOptions", $preparedOptionRows,
                            $objectKeyIsArrayCustom["option"], $objectKeys["option"], "", [],
                            $imagesIndexesWithPaths["option"], $referenceLookup["option"], true, [], true);//#
                    }


                }
            }
        }

        // Prepare Time Row
        if (isset($itemRowFromMenu["__itemTimes"])) {

            $itemTimesRowsFromMenu = $itemRowFromMenu["__itemTimes"];
            foreach ($itemTimesRowsFromMenu as $i => $itemTimeRowFromMenu) {

                $preparedItemTimesRows = [];
                s3logMenuLoader(printLogTime() . "-- Item Time: " . $itemTimeRowFromMenu["dayOfWeek"] . "..." . "\r\n");
                foreach ($objectKeys["time"] as $key => $keyToLookup) {

                    if (isset($itemTimeRowFromMenu[$keyToLookup])) {

                        $preparedItemTimesRows[$i][$key] = $itemTimeRowFromMenu[$keyToLookup];
                    } else {

                        $preparedItemTimesRows[$i][$key] = "";
                    }
                }

                if (count_like_php5($preparedItemTimesRows) > 0) {

                    list($preparedItemTimesRows, $objectKeys["time"], $updatedObjectKeyIsArrayCustom) = processRetailerItemTimesData($preparedItemTimesRows,
                        $objectKeys["time"], $objectKeyIsArrayCustom["time"]);

                    $objectKeyIsArrayCustom["time"] = $updatedObjectKeyIsArrayCustom;

                    prepareAndPostToParse("", "", "RetailerItemProperties", $preparedItemTimesRows,
                        $objectKeyIsArrayCustom["time"], $objectKeys["time"], "", [], $imagesIndexesWithPaths["time"],
                        $referenceLookup["time"], true);//#
                }
            }
        }
    }

    s3logMenuLoader(printLogTime() . "----------------------------------" . "\r\n");
    s3logMenuLoader(printLogTime() . "Inserting Complete" . "\r\n");
    s3logMenuLoader(printLogTime() . "----------------------------------" . "\r\n");

    return $isRetailerClosed;
    // JMD
}

function updateMenuDeleteItems($retailerUniqueId, $itemsToDelete, $inactiveTimestamp)
{

    // JMD
    // JMD
    s3logMenuLoader(printLogTime() . "----------------------------------" . "\r\n");
    s3logMenuLoader(printLogTime() . "Deleting Items (" . count_like_php5($itemsToDelete) . ")" . "\r\n");
    s3logMenuLoader(printLogTime() . "----------------------------------" . "\r\n");

    foreach ($itemsToDelete as $itemId) {

        s3logMenuLoader(printLogTime() . "- Item: " . $itemId . "..." . "\r\n");

        $retailerItems = new ParseQuery("RetailerItems");
        $retailerItems->equalTo("uniqueRetailerId", $retailerUniqueId);
        $retailerItems->equalTo("itemId", $itemId);
        $retailerItems->equalTo("isActive", true);
        $items = $retailerItems->find();

        if (count_like_php5($items) == 0) {

            s3logMenuLoader(printLogTime() . "- Item not found in DB...skipping" . "\r\n");
            continue;
        }

        foreach ($items as $item) {

            s3logMenuLoader(printLogTime() . "- Processing Item: " . $item->getObjectId() . " - " . $item->get('uniqueId') . "..." . "\r\n");

            $retailerItemModifiers = new ParseQuery("RetailerItemModifiers");
            $retailerItemModifiers->equalTo("uniqueRetailerItemId", $item->get('uniqueId'));
            $retailerItemModifiers->equalTo("isActive", true);
            $retailerItemModifiers->limit(1000);
            $modifiers = $retailerItemModifiers->find();

            foreach ($modifiers as $modifier) {

                s3logMenuLoader(printLogTime() . "-- Processing Modifier: " . $modifier->getObjectId() . " - " . $modifier->get('uniqueId') . "..." . "\r\n");
                $retailerItemModifierOptions = new ParseQuery("RetailerItemModifierOptions");
                $retailerItemModifierOptions->equalTo("uniqueRetailerItemModifierId", $modifier->get('uniqueId'));
                $retailerItemModifierOptions->equalTo("isActive", true);
                $retailerItemModifierOptions->limit(10000);
                $options = $retailerItemModifierOptions->find();

                foreach ($options as $option) {

                    s3logMenuLoader(printLogTime() . "--- Deleting Option: " . $option->getObjectId() . " - " . $option->get('uniqueId') . "..." . "\r\n");

                    $option->set("isActive", false);
                    $option->set("inactiveTimestamp", $inactiveTimestamp);
                    $option->save();//#

                    s3logMenuLoader(printLogTime() . "--- deleted." . "\r\n");
                }

                s3logMenuLoader(printLogTime() . "-- Deleting Modifier: " . $modifier->getObjectId() . " - " . $modifier->get('uniqueId') . "..." . "\r\n");

                $modifier->set("isActive", false);
                $modifier->set("inactiveTimestamp", $inactiveTimestamp);
                $modifier->save();//#
                s3logMenuLoader(printLogTime() . "-- deleted." . "\r\n");
            }

            $retailerItemProperties = new ParseQuery("RetailerItemProperties");
            $retailerItemProperties->equalTo("uniqueRetailerItemId", $item->get('uniqueId'));
            $retailerItemProperties->equalTo("isActive", true);
            $retailerItemProperties->limit(10000);
            $itemProperties = $retailerItemProperties->find();

            foreach ($itemProperties as $property) {

                s3logMenuLoader(printLogTime() . "-- Deleting Item Time: " . $property->getObjectId() . " - " . $property->get('uniqueId') . "..." . "\r\n");
                $property->set("isActive", false);
                $property->set("inactiveTimestamp", $inactiveTimestamp);
                $property->save();
                s3logMenuLoader(printLogTime() . "-- deleted." . "\r\n");
            }

            s3logMenuLoader(printLogTime() . "- Deleting Item: " . $item->getObjectId() . " - " . $item->get('uniqueId') . "..." . "\r\n");
            $item->set("isActive", false);
            $item->set("inactiveTimestamp", $inactiveTimestamp);
            $item->save();//#
            s3logMenuLoader(printLogTime() . "- deleted." . "\r\n");
        }
    }

    s3logMenuLoader(printLogTime() . "----------------------------------" . "\r\n");
    s3logMenuLoader(printLogTime() . "Deletion Complete" . "\r\n");
    s3logMenuLoader(printLogTime() . "----------------------------------" . "\r\n");
}

// JMD
// JMD
// JMD
function updateItemInMenu($newItem, $oldItem, $columnsToUpdate, $createNewVersion, $inactiveTimestamp)
{
    // JMD

    $oldVersionedItem = "";
    $updatedItem = $oldItem;
    $itemId = $oldItem["itemId"];

    if ($createNewVersion == true) {

        $oldVersionedItem[$itemId] = $oldItem;
        $oldVersionedItem = compareMenusForLoadingDeactiveItem($oldVersionedItem, $itemId, $oldItem,
            $inactiveTimestamp);
    }

    $results = parseExecuteQuery(["uniqueId" => $newItem["uniqueId"]], "RetailerItems3rdPartyApprovals", "", "", [], 1);

    foreach ($columnsToUpdate as $columnName) {

        // Columns to get from RetailerItems3rdPartyApprovals
        if (!empty($results) && $results->has($columnName) && !empty($results->get($columnName))) {

            $updatedItem[$columnName] = $results->get($columnName);
        } // For rest of the columns use from newItem
        else {

            $updatedItem[$columnName] = $newItem[$columnName];
        }
    }

    // Item Times
    if (isset($newItem["__itemTimes"])) {

        $updatedItem["__itemTimes"] = $newItem["__itemTimes"];
    } else {

        unset($updatedItem["__itemTimes"]);
    }

    // Modifiers
    if (isset($newItem["__modifiers"])) {

        $updatedItem["__modifiers"] = $newItem["__modifiers"];
    } else {

        unset($updatedItem["__modifiers"]);
    }

    return [$updatedItem, $oldVersionedItem];
}

function updateItemTimeInMenu($newItemTime, $oldItemTime, $columnsToUpdate, $createNewVersion, $inactiveTimestamp)
{

    $oldVersionedItemTime = $oldItemTime;
    // JMD
    $updatedItemTime = [];

    foreach ($columnsToUpdate as $columnName) {

        $updatedItemTime[$columnName] = $newItemTime[$columnName];
    }

    return $updatedItemTime;
}

function updateModifierInMenu($newModifier, $oldModifier, $columnsToUpdate, $createNewVersion, $inactiveTimestamp)
{

    // JMD
    $updatedModifier = $oldModifier;
    // $modifierId = $oldModifier["modifierId"];

    foreach ($columnsToUpdate as $columnName) {

        $updatedModifier[$columnName] = $newModifier[$columnName];
    }

    // Options
    if (isset($newModifier["__options"])) {

        $updatedModifier["__options"] = $newModifier["__options"];
    } else {

        unset($updatedModifier["__options"]);
    }

    return $updatedModifier;
}

function updateModifierOptionInMenu($newOption, $oldOption, $columnsToUpdate, $createNewVersion, $inactiveTimestamp)
{

    // JMD
    $updatedOption = $oldOption;
    // $optionId = $oldOption["optionId"];

    foreach ($columnsToUpdate as $columnName) {

        $updatedOption[$columnName] = $newOption[$columnName];
    }

    return $updatedOption;
}

function haveColumnsBeenUpdated($new, $old, $columnsToUseForHash)
{

    return isNewVersionNeeded($new, $old, $columnsToUseForHash);
}

function isNewVersionNeeded($new, $old, $columnsToUseForHash)
{

    $newVersion = false;

    $newMenuHash = generateRowHash($new, $columnsToUseForHash);
    $oldMenuHash = generateRowHash($old, $columnsToUseForHash);

    // Item needs a new version
    if (strcasecmp($newMenuHash, $oldMenuHash) != 0) {

        $newVersion = true;
    }

    return $newVersion;
}

function generateRowHash($array, $keys)
{

    $preHashString = "";
    foreach ($keys as $key) {

        if (isset($array[$key])) {

            $preHashString .= trim($array[$key]) . "_";
        }
    }

    return md5($preHashString);
}

function generateRowPreHash($array, $keys)
{

    $preHashString = "";
    foreach ($keys as $key) {

        if (isset($array[$key])) {

            $preHashString .= trim($array[$key]) . "_";
        }
    }

    return ($preHashString);
}

function prepareHMSHostItemArray($items)
{

    $cartFormatted = [];
    foreach ($items as $i => $item) {

        $itemObj = $item[0];
        $itemQuantity = $item[1];
        $options = $item[2];

        $extItemId = $itemObj->get("itemId");
        $cartFormatted["cart"]["items"][$i]["id"] = $extItemId;
        $cartFormatted["cart"]["items"][$i]["price"] = dollar_format_float_with_decimals($itemObj->get("itemPrice"));
        $cartFormatted["cart"]["items"][$i]["Quantity"] = $itemQuantity;
        $cartFormatted["cart"]["items"][$i]["freetext"] = "";

        if (!empty($options)) {

            foreach ($options as $o => $option) {

                $optionQuantity = $option["quantity"];
                $optionPrice = $option["price"];

                $cartFormatted["cart"]["items"][$i]["Modifiers"][$o]["Id"] = $option["optionId"];
                $cartFormatted["cart"]["items"][$i]["Modifiers"][$o]["Quantity"] = $optionQuantity;
                $cartFormatted["cart"]["items"][$i]["Modifiers"][$o]["Price"] = dollar_format_float_with_decimals($optionPrice);
                $cartFormatted["cart"]["items"][$i]["Modifiers"][$o]["FreeText"] = "";
            }
        }
    }

    return $cartFormatted;
}

function getPartnerTaxes($dualPartnerConfig, $cartFormatted, $orderObject, $retailerItem)
{

    //////////////////////////////////////////////////////////////////////////////////////////////////////
    // Verify all items are valid before adding to cart
    //////////////////////////////////////////////////////////////////////////////////////////////////////
    $totalTax = 0;
    if (strcasecmp($dualPartnerConfig->get('partner'), 'hmshost') == 0) {

        $uniqueRetailerItemId = $retailerItem->get('uniqueId');

        try {

            $hmshost = new HMSHost($dualPartnerConfig->get('airportId'), $dualPartnerConfig->get('retailerId'),
                $orderObject->get('retailer')->get('uniqueId'), 'order');
        } catch (Exception $ex) {

            json_error("AS_895", "We are sorry, but the retailer is currently not accepting orders.",
                "Failed to connect to HMSHost for cart totals, Order Id: " . $orderObject->get('orderSequenceId'), 1);
        }

        // Pull taxes for the item
        $allow = true;
        try {

            $errorMsg = "";
            list($flag, $itemPrices, $invalidItems, $totalTax, $totalAmountDue, $subtotalDue) = $hmshost->confirm_items_are_valid($orderObject->get('orderSequenceId'),
                $cartFormatted);

            if ($flag == false) {

                // Invalid Items were found
                $allow = false;
                $errorMsg = "Invalid items found";
            } else {

                // Match prices
                if (count_like_php5($itemPrices) > 0) {

                    foreach ($cartFormatted["cart"]["items"] as $item) {

                        $extItemId = $item["id"];
                        if (!isset($itemPrices[$extItemId])) {

                            $errorMsg = "Item: " . $extItemId . " price not found";
                            $allow = false;
                            break;
                        } else {
                            if ($itemPrices[$extItemId] != $item["price"]) {

                                $errorMsg = "Item: " . $extItemId . " price mismatch. Expected = " . $item["price"] . " - Found = " . $itemPrices[$extItemId];
                                $allow = false;
                                break;
                            }
                        }
                    }
                } else {

                    $totalAmount = 0;
                    foreach ($cartFormatted["cart"]["items"] as $item) {

                        $totalAmount += $item["price"] * $item["Quantity"] * 100;

                        if (isset($item["Modifiers"]) && count_like_php5($item["Modifiers"]) > 0) {
                            foreach ($item["Modifiers"] as $options) {

                                $totalAmount += $options["Price"] * $options["Quantity"] * 100;
                            }
                        }
                    }

                    if (strcasecmp($totalAmount, (($totalAmountDue) - ($totalTax))) != 0) {

                        $errorMsg = "Total price mismatch. Expected = " . ($totalAmount) . " - Found = " . (($totalAmountDue) - ($totalTax));
                        $allow = false;
                    }
                }
            }

            // JMD
            if ($allow == false) {

                // 86 the item so no other user can use it
                $retailer = $orderObject->get('retailer');

                $itemDetails = [
                    "retailerName" => $retailer->get("retailerName"),
                    "location" => $retailer->get("location")->get("locationDisplayName"),
                    "itemName" => !empty($retailerItem->get("itemDisplayName")) ? $retailerItem->get("itemDisplayName") : $retailerItem->get("itemPOSName"),
                    "uniqueRetailerItemId" => $uniqueRetailerItemId
                ];

                setItem86isedFortheDay($uniqueRetailerItemId, $itemDetails);
                notifyOnSlackMenuUpdates($retailer->get("uniqueId"), "Item 86isd",
                    ["Unique Id" => $itemDetails["uniqueRetailerItemId"], "Item Name" => $itemDetails["itemName"]]);

                json_error("AS_896", "We are sorry, this item is currently not available.",
                    "HMSHost - Item add to cart failed, Order Id: " . $orderObject->get('orderSequenceId') . " - Item Id " . $uniqueRetailerItemId . " - Error = " . $errorMsg . ", InvalidItems = " . json_encode($invalidItems),
                    1);
            }
        } catch (Exception $ex) {

            json_error("AS_896",
                "We are sorry, but the retailer is currently not accepting orders. Please try again later.",
                "Failed to get totals from HMSHost during add to cart, Order Id: " . $orderObject->get('orderSequenceId') . " - " . $ex->getMessage(),
                1);
        }
    }

    return $totalTax;
}

function getPartnerUnavailableItems($dualPartnerConfig, $orderSummaryItemList, $orderObject)
{

    //////////////////////////////////////////////////////////////////////////////////////////////////////
    // Verify all items are valid before adding to cart
    //////////////////////////////////////////////////////////////////////////////////////////////////////
    $totalTax = 0;
    if (strcasecmp($dualPartnerConfig->get('partner'), 'hmshost') == 0) {

        try {

            $hmshost = new HMSHost($dualPartnerConfig->get('airportId'), $dualPartnerConfig->get('retailerId'),
                $orderObject->get('retailer')->get('uniqueId'), 'order');
        } catch (Exception $ex) {

            json_error("AS_895", "We are sorry, but the retailer is currently not accepting orders.",
                "Failed to connect to HMSHost for cart totals, Order Id: " . $orderObject->get('orderSequenceId'), 1);
        }

        // Pull taxes for the item
        $allow = true;
        try {

            $errorMsg = "";
            $cartFormatted = $hmshost->format_cart($orderSummaryItemList, "", "", 1);
            list($flag, $itemPrices, $invalidItems, $totalTax, $totalAmountDue, $subtotalDue) = $hmshost->confirm_items_are_valid($orderObject->get('orderSequenceId'),
                $cartFormatted);

            if ($flag == false) {

                // Invalid Items were found
                $allow = false;
                $errorMsg = "Invalid items found";
            } else {

                // Match prices
                if (count_like_php5($itemPrices) > 0) {

                    foreach ($cartFormatted["cart"]["items"] as $item) {

                        $extItemId = $item["id"];
                        if (!isset($itemPrices[$extItemId])) {

                            $errorMsg = "Item " . $extItemId . " price not found";
                            $allow = false;
                            break;
                        } else {
                            if ($itemPrices[$extItemId] != $item["price"]) {

                                $errorMsg = "Item " . $extItemId . " price mismatch. Expected = " . $item["price"] . " - Found = " . $itemPrices[$extItemId];
                                $allow = false;
                                break;
                            }
                        }
                    }
                } else {

                    $totalAmount = 0;
                    foreach ($cartFormatted["cart"]["items"] as $item) {

                        $totalAmount += $item["price"] * $item["Quantity"] * 100;

                        if (isset($item["Modifiers"]) && count_like_php5($item["Modifiers"]) > 0) {
                            foreach ($item["Modifiers"] as $options) {

                                $totalAmount += $options["Price"] * $options["Quantity"] * 100;
                            }
                        }
                    }

                    if (strcasecmp($totalAmount, ($totalAmountDue - $totalTax)) != 0) {

                        $errorMsg = "Total price mismatch. Expected = " . $totalAmount . " - Found = " . ($totalAmountDue - $totalTax);
                        $allow = false;
                    }
                }
            }

            $returnList = [];
            if ($allow == false) {

                // 86 the item so no other user can use it
                $retailer = $orderObject->get('retailer');

                $uniqueRetailerItemIds = "";
                foreach ($invalidItems as $itemId => $invalidItem) {

                    $uniqueRetailerItemIds .= $itemId . ",";

                    $found = false;
                    foreach ($orderSummaryItemList["items"] as $cartItem) {

                        if ($cartItem["extItemId"] == $itemId) {

                            $uniqueRetailerItemId = $cartItem["itemId"];
                            $itemName = $cartItem["itemName"];
                            $found = true;
                            break;
                        }
                    }

                    if ($found == true) {

                        $itemDetails = [
                            "retailerName" => $retailer->get("retailerName"),
                            "location" => $retailer->get("location")->get("locationDisplayName"),
                            "itemName" => $itemName,
                            "uniqueRetailerItemId" => $uniqueRetailerItemId
                        ];

                        setItem86isedFortheDay($uniqueRetailerItemId, $itemDetails);

                        $returnList[] = $itemDetails["itemName"];
                    }
                }

                json_error("AS_896", "We are sorry, this item is currently not available.",
                    "HMSHost - Item add to cart failed, Order Id: " . $orderObject->get('orderSequenceId') . " - Item Ids " . $uniqueRetailerItemIds . " - Error = " . $errorMsg . ", InvalidItems = " . json_encode($invalidItems),
                    1);
            }
        } catch (Exception $ex) {

            json_error("AS_896",
                "We are sorry, but the retailer is currently not accepting orders. Please try again later.",
                "Failed to get totals from HMSHost during add to cart, Order Id: " . $orderObject->get('orderSequenceId') . " - " . $ex->getMessage(),
                1);
        }
    }

    return $returnList;
}

function canRetailerOpenAfterClosedEarly($uniqueId, $openLevel)
{

    // Stage 1 - In Process of being closed
    $retailerCloseEarlyForNewOrders = getRetailerCloseEarlyForNewOrders($uniqueId);

    // Stage 2 - Closed
    $retailerOpenAfterClosedEarly = getRetailerOpenAfterClosedEarly($uniqueId);

    // Retailer is already open
    if (empty($retailerOpenAfterClosedEarly)
        && empty($retailerCloseEarlyForNewOrders)
    ) {

        return true;
    }

    // Use the array depending on which stage of closing the tablet is in
    $arrayToUse = $retailerOpenAfterClosedEarly;
    if (!is_array($retailerOpenAfterClosedEarly)) {

        $arrayToUse = $retailerCloseEarlyForNewOrders;
    }

    list($timestamp, $openLevelRecorded) = $arrayToUse;

    // Requested source is lower than recorded level
    if ($openLevelRecorded <= $openLevel) {

        return true;
    } else {

        return false;
    }
}

function getTabletOpenCloseLevelFromTablet()
{

    return array_search("TABLET", $GLOBALS['tabletOpenCloseLevels']);
}

function getTabletOpenCloseLevelFromDashboard()
{

    return array_search("DASHBOARD", $GLOBALS['tabletOpenCloseLevels']);
    return $GLOBALS['tabletOpenCloseLevels']["DASHBOARD"]["code"];
}

function getTabletOpenCloseLevelFromSystem()
{

    return array_search("SYSTEM", $GLOBALS['tabletOpenCloseLevels']);
}

function getRetailerS3MenuDirectoryName($retailerInfo)
{

    return preg_replace('/[^A-Za-z0-9\_]/', '', str_replace(' ', '_',
        $retailerInfo["retailerName"] . " " . $retailerInfo["location"]["locationDisplayName"] . "  " . $retailerInfo["uniqueId"]));
}

function getRetailerInfoForDisplay($retailerInfo)
{

    return $retailerInfo["airportIataCode"] . " - " . $retailerInfo["retailerName"] . " - " . $retailerInfo["location"]["locationDisplayName"];
}

function s3logMenuLoader($log, $forcePush = false)
{

    echo $log . PHP_EOL;
    return true;

    $GLOBALS['menu_loader_S3_log_backlog'] .= $log;
    $GLOBALS['menu_loader_S3_log_backlog_counter']++;

    if ($forcePush) {

        $GLOBALS['menu_loader_S3_log_backlog_counter'] = 101;
    }

    if ($GLOBALS['menu_loader_S3_log_backlog_counter'] > 100) {

        if (defined("WORKER_MENU_LOADER")) {

            try {

                if (strcasecmp($GLOBALS['env_InHerokuRun'], "Y") != 0) {

                    //echo(nl2br($GLOBALS['menu_loader_S3_log_backlog']));
                    //flush();@ob_flush();
                }

                set_error_handler("warning_handler_menu_loader", E_WARNING);
                logMenuLoaderAction($GLOBALS['menu_loader_S3_log_backlog'], getS3KeyPath_RetailerMenuLoaderLog());
                restore_error_handler();

                $GLOBALS['menu_loader_S3_log_backlog'] = "";
                $GLOBALS['menu_loader_S3_log_backlog_counter'] = 0;
            } catch (Exception $ex) {

                json_error("AS_10002", "", "Menu loader action S3 log failed " . $ex->getMessage(), 1, 1);

                $error_row = printLogTime() . "S3 Log write failed, but handled." . "\r\n";
                $error_row .= printLogTime() . $ex->getMessage() . "\r\n";

                if (strcasecmp($GLOBALS['env_InHerokuRun'], "Y") != 0) {

                    //echo(nl2br($error_row));
                    //flush();@ob_flush();
                }

                $GLOBALS['menu_loader_S3_log_backlog'] .= $error_row;
                usleep(100);
            }
        } else {

            if (strcasecmp($GLOBALS['env_InHerokuRun'], "Y") != 0) {

                //echo(nl2br($GLOBALS['menu_loader_S3_log_backlog']));
                //flush();@ob_flush();

                $GLOBALS['menu_loader_S3_log_backlog'] = "";
                $GLOBALS['menu_loader_S3_log_backlog_counter'] = 0;
            }
        }
    }
    // else {

    //     echo(nl2br($GLOBALS['menu_loader_S3_log_backlog_counter']));
    //     flush();@ob_flush();
    // }
}

function logMenuLoaderAction($log, $S3Path)
{

    if (strcasecmp($GLOBALS['env_InHerokuRun'], "Y") != 0) {

        return;
    }

    $filenameWithS3Path = $S3Path . 'loader-' . date('Y-m-d') . '.log';

    $s3_client = getS3ClientObject(false, 'stream');
    $s3_client->registerStreamWrapper();

    $stream = @fopen('s3://' . $filenameWithS3Path, 'a');
    @fwrite($stream, $log);
    @fclose($stream);
}

function menuLoaderWriteNewCategoriesToFile($categories, $S3Path)
{

    // Write to S3
    // JMD
    $s3_client = getS3ClientObject();

    $fileNameWithPathCategories = 'missing_categories.csv';

    $csvContent = "categoryName,sequence" . "\r\n";
    foreach ($categories as $category) {

        $csvContent .= '"' . $category . '"' . "," . "\r\n";
    }

    // Categories file
    S3UploadFileWithContents($s3_client, $GLOBALS['env_S3BucketName'], $S3Path . $fileNameWithPathCategories,
        $csvContent, false);
}
