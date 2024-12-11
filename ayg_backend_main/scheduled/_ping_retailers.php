<?php

require_once 'dirpath.php';
require_once $dirpath . 'lib/initiate.inc.php';
require_once $dirpath . 'lib/errorhandlers_scheduled.php';

use App\Tablet\Helpers\QueueMessageHelper;
use App\Tablet\Services\QueueServiceFactory;
use Parse\ParseClient;
use Parse\ParseQuery;
use Parse\ParseObject;
use Parse\ParseUser;

use Parse\ParseFile;
use Httpful\Request;

function fetchMobilockAllInfo($page)
{

    // Fetch Mobilock details for all devices
    return \Httpful\Request::get($GLOBALS['env_MobiLockAPIAllInfoURL'] . '?page=' . $page)
        ->addHeader('Authorization', 'Token ' . $GLOBALS['env_MobiLock_APIKey'])
        ->send();
}

function execute_ping_retailers($checkBattery = false)
{
    $queueService = QueueServiceFactory::createMidPriorityAsynch();

    // error_log("::ORDER_WORKER:: Inside Ping Retailers...");

    // Fetch all device data from Mobilock
    $currentPage = 1;
    $pagesNeeded = $currentPage; // default value

    $allDevices = [];
    $devicesFormatted = [];

    echo 'start mobilock data';
    while ($pagesNeeded >= $currentPage) {

        // Fetch page
        echo 'getting mobilock data';
        $response = fetchMobilockAllInfo($currentPage);
        echo 'mobilock data is there';


        if (!isset($response->body->devices)) {

            break;
        }

        // Fetch device data
        $allDevices = array_merge($response->body->devices, $allDevices);

        // Prepare for next page
        $pagesNeeded = $response->body->total_pages;
        $currentPage++;
    }

    // Proces all device data
    foreach ($allDevices as $info) {

        $devicesFormatted[$info->device->id] = [
            "name" => $info->device->name,
            "battery_status" => $info->device->battery_status,
            "battery_charging" => $info->device->battery_charging,
            "location" => ["lat" => $info->device->location->lat, "lng" => $info->device->location->lng],
            "licence_expires_at" => $info->device->licence_expires_at,
            "lastSeen" => strtotime($info->device->last_connected_at), // awaiting right value
            "isLocked" => $info->device->locked // default value for now
        ];
    }

    // Set cache
    setMobilockDeviceDataCache($devicesFormatted);
    // Find POS Config settings
    $objectParseQueryPOSConfig = parseExecuteQuery(array("continousPingCheck" => true), "RetailerPOSConfig", "", "",
        array("retailer", "retailer.location", "dualPartnerConfig"));

    $locationPingChecked = [];
    $lastSlackPing = 0;

    // Ping the Retailer
    foreach ($objectParseQueryPOSConfig as $obj) {
        if ($obj->get('retailer') == null || empty($obj->get('retailer'))) {
            continue;
        }

var_dump($obj->get('retailer')->get('airportIataCode'));

        $locationId = $obj->get('locationId');
        $printerId = $obj->get('printerId');
        $tabletId = $obj->get('tabletId');
        $tabletMobilockId = $obj->get('tabletMobilockId');
        $dualPartnerConfig = $obj->get('dualPartnerConfig');



        // Skip...
        // If retailer isActive = false
        // Or both hasDelivery and hasPickup = false
        if ($obj->get('retailer')->get('isActive') == false ||
            ($obj->get('retailer')->get('hasDelivery') == false && $obj->get('retailer')->get('hasPickup') == false)
        ) {
            continue;
        }
        // Is retailer closed
        list($isClosed, $errorMsg) = isRetailerClosed($obj->get('retailer'), 0, 0);


        if ($isClosed == 1) {

            continue;
        }

        $extPartnerRetailer = false;
        $dualConfig = false;
        $dualPartnerConfigId = "";
        if (!empty($printerId)) {

            $pingType = "Printer";
            $tabletAppRetailer = false;
            $tabletRetailer = false;
        } else {
            if (!empty($locationId)) {

                $pingType = "POS";
                $tabletAppRetailer = false;
                $tabletRetailer = false;
                $extPartnerRetailer = true;
            } else {
                if (!empty($tabletId)) {

                    $pingType = "Tablet Slack";
                    $tabletAppRetailer = false;
                    $tabletRetailer = true;
                } else {

                    // Check if Dual Partner Tablet & POS integration
                    if (!empty($dualPartnerConfig)
                        && $dualPartnerConfig->get('tabletIntegrated') == true
                    ) {

                        $dualPartnerConfigId = $dualPartnerConfig->getObjectId();
                        $pingType = "Tablet-DualConfig";
                        $tabletAppRetailer = true;
                        $tabletRetailer = true;
                        $extPartnerRetailer = true;
                    } else {
                        if (!empty($dualPartnerConfig)) {

                            $dualPartnerConfigId = $dualPartnerConfig->getObjectId();
                            $pingType = "Ext-Partner-only";
                            $tabletAppRetailer = false;
                            $tabletRetailer = false;
                            $extPartnerRetailer = true;
                        } else {

                            $pingType = "Tablet App";
                            $tabletAppRetailer = true;
                            $tabletRetailer = true;
                            $extPartnerRetailer = false;
                        }
                    }
                }
            }
        }

        $locationIdKey = $locationId . '-' . $printerId . '-' . $tabletId . '-' . $dualPartnerConfigId;

        $ping = 0;
        $isPartner = false;
        // If a common locationId was used for another retailer earlier and checked, then skip
        if (in_array($locationIdKey, array_keys($locationPingChecked))
            && $tabletRetailer == false
        ) {

            $ping = $locationPingChecked[$locationIdKey]["ping"];
            $errorMsg = $locationPingChecked[$locationIdKey]["errorMsg"];
        } else {
            var_dump('jumped into new loop fro ' . $obj->get('retailer')->get('airportIataCode'));
            $partners = parseExecuteQuery(array("retailer" => $obj->get('retailer')), "RetailerPartners", "", "");

            if (count_like_php5($partners) > 0) {
                $ping = 1;
                $errorMsg = "";
                $isPartner = true;
            } else {


                // echo('Executing ping for...' . $locationIdKey . '<br />');

                // External Partner
                // Call its relevant endpoint
                $errorMsg = "";
                if ($extPartnerRetailer == true) {

                    list($ping, $errorMsg) = pingRetailerWithAPI($locationId, $printerId, $tabletId,
                        $dualPartnerConfig);

                    // Dual Config retailer
                    if ($tabletAppRetailer == true) {

                        $dualConfig = true;
                        if (isRetailerDualConfigPingActive($obj->get('retailer'))) {

                            // $ping = 1; // Should be already set to 1 if POS is up, but leave as 1, else if it was 0 then don't change it
                        } else {

                            $errorMsg .= "Tablet is down.";
                            $ping = 0;
                        }
                    }
                }
                // Tablet App Retailer
                // Just check its last ping sent
                else {
                    if ($tabletAppRetailer == true) {

                        if (isRetailerPingActive($obj->get('retailer'))) {

                            $ping = 1;
                        } else {

                            $errorMsg .= "Tablet is down.";
                            $ping = 0;
                        }
                    }
                    // Else
                    // Ping the retailer to check if they are online
                    else {

                        // Sleep for 2 seconds to avoid Slack's flood detection
                        if ($lastSlackPing > (time() - 2)) {

                            sleep(round(time() - $lastSlackPing));
                        }

                        list($ping, $errorMsg) = pingRetailerWithAPI($locationId, $printerId, $tabletId);
                        $lastSlackPing = time();
                    }
                }

            }

            $locationPingChecked[$locationIdKey]["ping"] = $ping;
            $locationPingChecked[$locationIdKey]["errorMsg"] = $errorMsg;
        }


        // If online, save lastSuccessfulPingTimestamp
        if ($ping == 1) {
            // Update in Redis and DB but not for Tablet app retailers
            if ($tabletAppRetailer == false || $dualConfig == true || $isPartner) {

                // Set in Redis cache
                $time = time();
                setRetailerPingTimestamp($obj->get('retailer')->get('uniqueId'), $time);

                // send logs
                $logRetailerPingMessage = QueueMessageHelper::getLogRetailerPingMessage($obj->get('retailer')->get('uniqueId'),
                    $time);
                $queueService->sendMessage($logRetailerPingMessage, 0);

                $obj->set('lastSuccessfulPingTimestamp', strval($time));
                $obj->save();
            }
        } // Post on Slack
        else {

            $errorMsg = empty($errorMsg) ? "No specific message provided" : $errorMsg;

            $downForMins = round((time() - getRetailerPingTimestamp($obj->get('retailer')->get('uniqueId'))) / 60);

            // if($downForMins >= $GLOBALS['env_PingRetailerReportIntervalInSecs']) {

            // Check if Retailer exists
            $retailerName = $obj->get('retailer')->get("retailerName");
            $retailerLocation = $obj->get('retailer')->get('location')->get('locationDisplayName');
            $airportIataCode = $obj->get('retailer')->get("airportIataCode");

            $downForText = formatSecondsIntoHumanIntervals($downForMins * 60);


            // Ping retailer Slack push (below) takes care of this message
            // json_error("AS_1009", "", "Retailed Ping failed! ($errorMsg) objectId=(" . $obj->get('retailer')->get('uniqueId') . " - $retailerName)", 1, 1);

            // Slack it
            //$slack = new SlackMessage($GLOBALS['env_SlackWH_posPingFail'], 'env_SlackWH_posPingFail');
            $slack = createPosPingFailSlackChannelSlackMessageByAirportIataCode($obj->get('retailer')->get('location')->get('airportIataCode'));
            $slack->setText($retailerName . " - " . $retailerLocation . " (@" . $airportIataCode . ")");

            $attachment = $slack->addAttachment();
            $attachment->addField("ENV:", $GLOBALS['env_EnvironmentDisplayCode'], false);
            $attachment->addField("Type:", $pingType, true);

            $attachment->addField("Down for:", $downForText, false);
            $attachment->addField("Error:", $errorMsg, false);

            try {

                $slack->send();
            } catch (Exception $ex) {

                // throw new Exception($ex->getMessage());
                json_error("AS_1054", "",
                    "Slack post failed informing Ping failure! Post Array=" . json_encode($attachment->getAttachment()) . " -- " . $ex->getMessage(),
                    1, 1);
            }
            // }
        }

        // If Tablet retailer, then check its battery level < 25% and not charging
        // Only perform these tasks if ping was successful
        if ($tabletRetailer == true
            && !empty($tabletMobilockId)
            && $ping == 1
            && $checkBattery == true
        ) {

            $previouslyChecked = false;

            $battery_status = -1;
            $battery_charging = false;
            $responseEncoded = "";

            if (isset($devicesFormatted[$tabletMobilockId])) {

                $battery_status = $devicesFormatted[$tabletMobilockId]["battery_status"];
                $battery_charging = $devicesFormatted[$tabletMobilockId]["battery_charging"];
                $responseEncoded = serialize($devicesFormatted[$tabletMobilockId]);
            }

            if ($battery_status == -1) {

                //throw new Exception(json_encode(json_error_return_array("AS_1054", "", "Battery Check failed for retailer=" . $obj->get('retailer')->get("uniqueId") . ', response = ' . $responseEncoded, 2)));

                if ($previouslyChecked == false) {

                    json_error("AS_1054", "",
                        "Battery Check failed for retailer=" . $obj->get('retailer')->get("uniqueId") . ', response = ' . $responseEncoded,
                        2, 1);
                }
            } else {
                if ($battery_status < 50 && $battery_charging == false) {

                    $retailerName = $obj->get('retailer')->get("retailerName");
                    $airportIataCode = $obj->get('retailer')->get("airportIataCode");

                    // Slack it
                    $slack = createPosPingFailSlackChannelSlackMessageByAirportIataCode($obj->get('retailer')->get('location')->get('airportIataCode'));
                    //$slack = new SlackMessage($GLOBALS['env_SlackWH_posPingFail'], 'env_SlackWH_posPingFail');
                    $slack->setText($retailerName . " (@" . $airportIataCode . ")");

                    $attachment = $slack->addAttachment();
                    $attachment->addField("ENV:", $GLOBALS['env_EnvironmentDisplayCode'], false);
                    $attachment->addField("Type:", 'Tablet', false);
                    $attachment->addField("Low Battery:", $battery_status . '%', true);
                    $attachment->addField("Charging:", 'N', true);

                    try {

                        $slack->send();
                    } catch (Exception $ex) {

                        // throw new Exception($ex->getMessage());
                        json_error("AS_1054", "",
                            "Slack post failed informing Ping failure! Post Array=" . json_encode($attachment->getAttachment()) . " -- " . $ex->getMessage(),
                            1, 1);
                    }

                    // Send message retailer tablet via Mobilock
                    // But skip retailers with Tablet App
                    if ($tabletAppRetailer == false) {

                        $messageParamters = [
                            "device_ids" => $tabletMobilockId,
                            "sender_name" => "AtYourGate",
                            "message_body" => "Battery level is below 50%, please plug in the charger."
                        ];

                        if (!sendMessageToPOSTablet($messageParamters)) {

                            // throw new Exception(json_encode(json_error_return_array("AS_1054", "", "Battery plug in request message failed to the retailer=" . $obj->get('retailer')->get("uniqueId"), 1)));
                            json_error("AS_1054", "",
                                "Battery plug in request message failed to the retailer=" . $obj->get('retailer')->get("uniqueId"),
                                2, 1);
                        }
                    }
                } else {
                    if ($battery_charging == false) {

                        $retailerName = $obj->get('retailer')->get("retailerName");
                        $airportIataCode = $obj->get('retailer')->get("airportIataCode");

                        // Slack it
                        $slack = createPosPingFailSlackChannelSlackMessageByAirportIataCode($obj->get('retailer')->get('location')->get('airportIataCode'));
                        //$slack = new SlackMessage($GLOBALS['env_SlackWH_posPingFail'], 'env_SlackWH_posPingFail');
                        $slack->setText($retailerName . " (@" . $airportIataCode . ")");

                        $attachment = $slack->addAttachment();
                        $attachment->addField("ENV:", $GLOBALS['env_EnvironmentDisplayCode'], false);
                        $attachment->addField("Type:", 'Tablet', false);
                        $attachment->addField("Charging:", 'N', false);

                        try {

                            $slack->send();
                        } catch (Exception $ex) {

                            // throw new Exception($ex->getMessage());
                            json_error("AS_1054", "",
                                "Slack post failed informing Ping failure! Post Array=" . json_encode($attachment->getAttachment()) . " -- " . $ex->getMessage(),
                                1, 1);
                        }

                        // Send message retailer tablet via Mobilock
                        // But skip retailers with Tablet App
                        if ($tabletAppRetailer == false) {

                            $messageParamters = [
                                "device_ids" => $tabletMobilockId,
                                "sender_name" => "AtYourGate",
                                "message_body" => "Please plug in the charger."
                            ];

                            if (!sendMessageToPOSTablet($messageParamters)) {

                                // throw new Exception(json_encode(json_error_return_array("AS_1054", "", "Battery plug in request message failed to the retailer=" . $obj->get('retailer')->get("uniqueId"), 1)));
                                json_error("AS_1054", "",
                                    "Battery plug in request message failed to the retailer=" . $obj->get('retailer')->get("uniqueId"),
                                    2, 1);
                            }
                        }
                    }
                }
            }
        }
    }

    // error_log("::ORDER_WORKER:: Done Ping Retailers...");
}

function build_fullfillment_times_cache()
{

    $airports = parseExecuteQuery(["isReady" => true], "Airports");

    foreach ($airports as $airport) {

        // TAG
        $locations = parseExecuteQuery([
            "includeInGateMap" => true,
            "airportIataCode" => $airport->get('airportIataCode')
        ], "TerminalGateMap", "airportIataCode", "");

        foreach ($locations as $location) {

            // Orignal fullfillment info
            $namedCacheKey = '__FULLFILLMENTINFO__' . $airport->get('airportIataCode') . '__' . $location->getObjectId();

            // Keys as integers
            $namedCacheKeySequenced = '__FULLFILLMENTINFO__sq__' . $airport->get('airportIataCode') . '__' . $location->getObjectId();

            $responseArray = fetchFullfillmentTimes($airport->get('airportIataCode'), $location->getObjectId());

            list($responseArraySequenced, $responseArray) = sortRetailersByFullfillmentTimesAndAddRetailerInfo($responseArray);

            setRouteCache([
                "cacheSlimRouteNamedKey" => $namedCacheKeySequenced,
                "jsonEncodedString" => json_encode($responseArraySequenced),
                "expireInSeconds" => intval($GLOBALS['env_PingRetailerIntervalInSecs']) * 3,
                "compressed" => true
            ]);

            setRouteCache([
                "cacheSlimRouteNamedKey" => $namedCacheKey,
                "jsonEncodedString" => json_encode($responseArray),
                "expireInSeconds" => intval($GLOBALS['env_PingRetailerIntervalInSecs']) * 3
            ]);
        }
    }
}

function build_curated_lists_cache()
{

    $airports = parseExecuteQuery(["isReady" => true], "Airports");

    foreach ($airports as $airport) {

        $airportIataCode = $airport->get('airportIataCode');
        $lists = parseExecuteQuery(["isActive" => true, "airportIataCode" => $airportIataCode], "List",
            "displaySequence");

        foreach ($lists as $list) {

            $locations = parseExecuteQuery(["includeInGateMap" => true, "airportIataCode" => $airportIataCode],
                "TerminalGateMap");

            foreach ($locations as $location) {

                $responseArray = [];
                $curatedList = [];

                list($requestedFullFillmentTimestamp, $futureFullfillment) = requestedFullFillmentTimestampForEstimates(0);

                $namedCacheKey = getCuratedListCacheKeyName($airportIataCode, $list->getObjectId(),
                    $location->getObjectId(), $requestedFullFillmentTimestamp, $futureFullfillment);

                // Build the list JSON from Cache
                $curatedList = buildCuratedList(
                    $airportIataCode,
                    $list->getObjectId(),
                    $location->getObjectId(),
                    "",
                    $requestedFullFillmentTimestamp,
                    true
                );

                // If a list was found
                if (count_like_php5($curatedList) > 0) {

                    $responseArray = $curatedList;

                    setRouteCache([
                        "cacheSlimRouteNamedKey" => $namedCacheKey,
                        "jsonEncodedString" => json_encode($responseArray),
                        "expireInSeconds" => intval($GLOBALS['env_PingRetailerIntervalInSecs']) * 3,
                        "compressed" => true
                    ]);
                }
            }
        }
    }
}

// Merge history cache with any older pending daily cache
// TAG
function merge_coupon_usage_cache($timestampSince)
{

    if ($timestampSince != 0) {

        // Calculate yesterday's timestamp
        $cacheKeySuffix = date("Y-m-d", ($timestampSince) - 24 * 60 * 60);
    } else {

        // If set to 0, then skip merging
        return;
    }

    list($byUserExists, $byCodeExists) = doesDailyCouponUsageCacheExist($cacheKeySuffix);

    // Merge by user cache
    if ($byUserExists) {

        // TAG
        mergeCouponUsageByUser($cacheKeySuffix);
    }

    // TAG
    // Merge by code cache
    if ($byCodeExists) {

        mergeCouponUsageByCode($cacheKeySuffix);
    }
}

function build_coupon_usage_cache($timestampSince)
{

    $usageByUsers = $usageByCoupons = [];

    if ($timestampSince != 0) {

        $cacheKeySuffix = date("Y-m-d", $timestampSince);
    } else {

        $cacheKeySuffix = "history";
        $timestampMidnight = strtotime("yesterday 11:59:59 pm");
    }

    // List all orders with coupons
    if ($timestampSince == 0) {

        $results = parseExecuteQuery([
            "__E__coupon" => true,
            "status" => listStatusesForCouponValidation(),
            "__LTE__submitTimestamp" => $timestampMidnight
        ], "Order", "", "", ["coupon", "user"]);
    } else {

        $results = parseExecuteQuery([
            "__E__coupon" => true,
            "status" => listStatusesForCouponValidation(),
            "__GTE__submitTimestamp" => $timestampSince
        ], "Order", "", "", ["coupon", "user"]);
    }

    foreach ($results as $row) {

        if (!$row->has("coupon")) {

            continue;
        }

        $couponCode = $row->get("coupon")->get("couponCode");
        $userId = $row->get("user")->getObjectId();

        if (!isset($usageByCoupons[$couponCode])) {

            $usageByCoupons[$couponCode] = 0;
        }

        $usageByCoupons[$couponCode] = $usageByCoupons[$couponCode] + 1;

        if (!isset($usageByUsers[$userId][$couponCode])) {

            $usageByUsers[$userId][$couponCode] = 0;
        }

        $usageByUsers[$userId][$couponCode] = $usageByUsers[$userId][$couponCode] + 1;
    }

    // List all UserCoupons
    if ($timestampSince == 0) {

        $results = parseExecuteQuery([
            "__E__signupCoupon" => true,
            "__LTE__createdAt" => DateTime::createFromFormat("Y-m-d H:i:s", gmdate("Y-m-d H:i:s", $timestampMidnight))
        ], "UserCredits", "", "", ["signupCoupon", "user"]);
    } else {

        $results = parseExecuteQuery([
            "__E__signupCoupon" => true,
            "__GTE__createdAt" => DateTime::createFromFormat("Y-m-d H:i:s", gmdate("Y-m-d H:i:s", $timestampSince))
        ], "UserCredits", "", "", ["signupCoupon", "user"]);
    }

    foreach ($results as $row) {

        if (!$row->has("signupCoupon")) {

            continue;
        }

        $couponCode = $row->get("signupCoupon")->get("couponCode");
        $userId = $row->get("user")->getObjectId();

        if (!isset($usageByCoupons[$couponCode])) {

            $usageByCoupons[$couponCode] = 0;
        }

        $usageByCoupons[$couponCode] = $usageByCoupons[$couponCode] + 1;

        if (!isset($usageByUsers[$userId][$couponCode])) {

            $usageByUsers[$userId][$couponCode] = 0;
        }

        $usageByUsers[$userId][$couponCode] = $usageByUsers[$userId][$couponCode] + 1;
    }

    // List of UserCredits
    if ($timestampSince == 0) {

        $results = parseExecuteQuery([
            "__LTE__createdAt" => DateTime::createFromFormat("Y-m-d H:i:s", gmdate("Y-m-d H:i:s", $timestampMidnight))
        ], "UserCoupons", "", "", ["coupon", "appliedToOrder"]);
    } else {

        $results = parseExecuteQuery([
            "__GTE__createdAt" => DateTime::createFromFormat("Y-m-d H:i:s", gmdate("Y-m-d H:i:s", $timestampSince))
        ], "UserCoupons", "", "", ["coupon", "appliedToOrder"]);
    }

    foreach ($results as $row) {

        if (!$row->has("coupon")) {

            continue;
        }

        // Skip those that have been applied to order since would have been count_like_php5ed in the Order query
        if ($row->has("appliedToOrder") && in_array($row->get("appliedToOrder")->get('status'),
                listStatusesForCouponValidation())
        ) {

            continue;
        }

        $couponCode = $row->get("coupon")->get("couponCode");
        $userId = $row->get("user")->getObjectId();

        if (!isset($usageByCoupons[$couponCode])) {

            $usageByCoupons[$couponCode] = 0;
        }

        $usageByCoupons[$couponCode] = $usageByCoupons[$couponCode] + 1;

        if (!isset($usageByUsers[$userId][$couponCode])) {

            $usageByUsers[$userId][$couponCode] = 0;
        }

        $usageByUsers[$userId][$couponCode] = $usageByUsers[$userId][$couponCode] + 1;
    }

    setCouponUsageByUser($usageByUsers, $cacheKeySuffix);
    setCouponUsageByCode($usageByCoupons, $cacheKeySuffix);
}

function rebuild_menu_cache()
{

    $retailerPOSConfig = parseExecuteQuery([], "RetailerPOSConfig", "", "", ["retailer"]);

    foreach ($retailerPOSConfig as $retailerPOS) {

        // just in case there is config but no retailer (some leftovers)
        if ($retailerPOS->get('retailer') == null) {
            continue;
        }

        $retailerId = $retailerPOS->get('retailer')->get('uniqueId');
        $namedCacheKey = 'menu' . '__ri__' . $retailerId;

        // if(!doesCacheExist(getNamedRouteCacheName($namedCacheKey))) {

        $objParseQueryRetailersItemsResults = parseExecuteQuery(array(
            "uniqueRetailerId" => $retailerId,
            "isActive" => true
        ), "RetailerItems", "itemDisplaySequence");

        if (count_like_php5($objParseQueryRetailersItemsResults) == 0) {

            json_error("AS_3024", "", "Rebuld cache skipped for " . $retailerId . " as no menu found.", 2, 1);
            continue;
        }

        $responseArray = getRetailerMenu($retailerId, time(), true);

        // Cache for EOD
        setRouteCache([
            "cacheSlimRouteNamedKey" => $namedCacheKey,
            "jsonEncodedString" => json_encode($responseArray),
            "expireInSeconds" => "EOD"
        ]);
        // }
    }
}

?>
