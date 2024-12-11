<?php

use App\Consumer\Helpers\DateTimeHelper;

$_SERVER['REQUEST_METHOD'] = '';
$_SERVER['REMOTE_ADDR'] = '';
$_SERVER['REQUEST_URI'] = '';
$_SERVER['SERVER_NAME'] = '';



ini_set("memory_limit","384M"); // Max 512M

define("WORKER", true);
define("QUEUE", true);

require_once 'dirpath.php';
require_once $dirpath . 'lib/initiate.inc.php';
require_once $dirpath . 'lib/errorhandlers_scheduled.php';
require_once $dirpath . 'scheduled/_process_orders.php';
require_once $dirpath . 'scheduled/_confirm_print_orders.php';

require_once $dirpath . 'scheduled/_confirm_pos_orders.php';
require_once $dirpath . 'scheduled/_confirm_tablet_orders.php';
require_once $dirpath . 'scheduled/_send_order_receipt.php';
require_once $dirpath . 'scheduled/_process_delivery.php';
require_once $dirpath . 'scheduled/_send_email.php';
require_once $dirpath . 'scheduled/_create_onesignal_device.php';
require_once $dirpath . 'scheduled/_queue_functions.php';
require_once $dirpath . 'scheduled/_ping_retailers.php';
require_once $dirpath . 'scheduled/_ping_slack_delivery.php';
require_once $dirpath . 'scheduled/_process_delivery_slack.php';
require_once $dirpath . 'scheduled/_worker_functions.php';
require_once $dirpath . 'scheduled/_send_user_communication.php';
require_once $dirpath . 'scheduled/_process_flight.php';

/*
$x=\App\Consumer\Helpers\DateTimeHelper::getOrderFullfillmentTimeRangeEstimateDisplay2(
    1628879850,
    450,
    450,
    'America/New_York'
);

    var_dump($x);

die();

$repo = new \App\Consumer\Repositories\OrderDeliveryPlanParseRepository();
$list =$repo->getListByAirportIataCode('JFK');
var_dump($list );die();


$starting = '01:00 AM';
$ending = '05:00 PM';


$timestamp = 1627451550;
$timezone = 'America/New_York';


$dateTime = new \DateTime('now', new \DateTimeZone($timezone));
$dateTime->setTimestamp($timestamp);
$weekDay = $dateTime->format('N');


$startingDateTime = DateTimeHelper::setHourAndMinuteBasedOnRetailerHours(clone $dateTime, $starting);
$endingDateTime = DateTimeHelper::setHourAndMinuteBasedOnRetailerHours(clone $dateTime, $ending);

var_dump($dateTime);
var_dump($startingDateTime);
var_dump($endingDateTime);


var_dump($startingDateTime > $dateTime);
var_dump($endingDateTime> $dateTime);


die();

*/

/*
$x='{\"RatingQuestions\":[],\"ShowSurveyLink\":true,\"acceptDateUTC\":\"1\\\/1\\\/1900 12:00:00 AM\",\"airportIdent\":\"SEA\",\"btCardToken\":\"Guest:213781057:kghnkpb\",\"cancelDateUTC\":null,\"cancelReason\":null,\"cartExceptions\":null,\"completionDateUTC\":\"1\\\/1\\\/1900 12:00:00 AM\",\"creditCardAuth\":\"GWXDTQ\",\"creditCardLastFour\":\"1111\",\"creditCardName\":\"VISA\",\"customerEmail\":\"ludwik+graborder@atyourgate.com\",\"customerImage\":null,\"deliveryDateUTC\":\"1\\\/1\\\/1900 12:00:00 AM\",\"dfAcceptDateUTC\":\"\\\/Date(-62135596800000+0000)\\\/\",\"dfCancelDateUTC\":\"\\\/Date(-62135596800000+0000)\\\/\",\"dfCompletionDateUTC\":\"\\\/Date(-62135596800000+0000)\\\/\",\"dfDeliveryDateUTC\":\"\\\/Date(-62135596800000+0000)\\\/\",\"dfOrderDateUTC\":\"\\\/Date(1629988251000+0000)\\\/\",\"discountAmount\":\"0\",\"discountCode\":null,\"discountDescription\":null,\"discountModifiableAtPortal\":false,\"discountOwner\":null,\"discountPushToPOS\":false,\"email\":\"guest@grabmobileapp.com\",\"exception\":\"\",\"facebookUserID\":\"\",\"firstName\":\"Ludwik\",\"floorID\":null,\"freedomPaySession\":null,\"hasEmployeeDiscount\":false,\"holdOrder\":false,\"isLockerOrder\":false,\"isPendingOrderEnabled\":false,\"isPreOrderEnabled\":false,\"isUnitTest\":false,\"kobp\":\"6d845fd7cb1e7c97577e51f97f5ffd50\",\"languageCode\":\"en\",\"lastName\":\"Ludwik\",\"mobilePhone\":\"\",\"mobilePhoneCountry\":\"\",\"mobilePhoneCountryCode\":null,\"netsSession\":null,\"orderDateUTC\":\"8\\\/26\\\/2021 2:30:51 PM\",\"orderDetails\":[{\"SourceLanguage\":null,\"TranslatedAdditionalInstructions\":null,\"TranslatedItemSubName\":null,\"TranslationLanguage\":null,\"additionalInstructions\":\"\",\"bUpdateItemFromPOS\":false,\"cost\":\"17.69\",\"costDisplay\":\"$17.69\",\"inventoryChoices\":[{\"SourceLanguage\":null,\"TranslatedChoiceGroupName\":null,\"TranslationLanguage\":null,\"calorieCount\":0,\"choiceAvailable\":false,\"choiceCost\":\"0.00\",\"choiceCostDisplay\":\"$0.00\",\"choiceDescription\":\"Wheat\",\"choiceEndTime\":\"\\\/Date(-62135596800000+0000)\\\/\",\"choiceGroupName\":null,\"choiceID\":\"2105853\",\"choiceOrder\":\"2\",\"choiceStartTime\":\"\\\/Date(-62135596800000+0000)\\\/\",\"imageName\":null,\"indexDisplay\":0,\"inventoryItemID_Anchor\":null,\"inventoryMainOptionChoice\":null,\"nestedLevel\":0,\"productID\":null,\"retailImageChoice\":null,\"selected\":true,\"translatedChoiceDescription\":null}],\"inventoryItemID\":\"1685988\",\"inventoryItemName\":\"Chicken Fried Steak\",\"inventoryItemSubID\":\"1924184\",\"inventoryItemSubName\":\"MAIN\",\"inventoryOptions\":[],\"itemOrder\":\"1\",\"orderDetailID\":\"84464\",\"orderID\":\"48043\",\"quantity\":1,\"translatedinventoryItemName\":null}],\"orderHistoryStarRatingWindowMinutes\":0,\"orderID\":\"48043\",\"orderReadyForPickup\":false,\"orderStatus\":0,\"orderTip\":0,\"orderType\":1,\"partnerCode\":\"6d845fd7cb1e7c97577e51f97f5ffd50\",\"platformCalling\":1,\"poiID\":\"poiID:134\",\"posOrderID\":\"\",\"posTicketNumber\":null,\"preAuthOrderID\":\"\",\"readyDateTime\":\"\\\/Date(1629964851000+0000)\\\/\",\"receiptLink\":null,\"selected\":false,\"spreedlyTransaction\":null,\"store\":{\"GMTOffset\":null,\"NormalStoreOpenHours\":[],\"OATAutoReceiptForVKEnabled\":false,\"OATBespokeProductSizeOrder\":false,\"OATBrowseModeEnabled\":false,\"OATDineInNameOptional\":false,\"OATDineInNameRequired\":false,\"OATHideZeroDollar\":false,\"OATItemSearchEnabled\":false,\"OATOrderOnlyNoPayment\":false,\"OATOrderingModeEnabled\":true,\"OATPayTabNowModeEnabled\":false,\"OATSplitTabCustomEnabled\":false,\"OATSplitTabEvenEnabled\":false,\"OATSplitTabMaxSplits\":0,\"OATSplitTabMinTab\":0,\"OATTakeAwayEnabled\":false,\"OATTakeAwayNameRequired\":false,\"OATTipForAlcoholOnly\":false,\"OATTwoColumnCategoriesEnabled\":false,\"OmnivoreStoreUsesTicketNumberAsID\":false,\"ReqEmpAssign\":false,\"ShowReceiptForZero\":false,\"ShowsOrdersOnTablet\":false,\"TemporaryStoreOpenHours\":[],\"additionalDeliveryTime\":0,\"airportIdent\":\"SEA\",\"airportIdentICAO\":null,\"alcoholMealLimitEnabled\":false,\"allowedAlcoholItemsPerMealItem\":0,\"autoCloseTimeMinutes\":0,\"autoReceiptEnabled\":false,\"bAddItemsAtTabletEnabled\":false,\"bAllowKiosksOfflineBasedOnStoreHours\":false,\"bDeliveryOnline\":false,\"bDineInEnabled\":false,\"bEmployeeItems\":false,\"bKioskShowAllergenCheckbox\":false,\"bOpenTicketEnabled\":false,\"bPOSTippingEnabled\":false,\"bPendingOrderEnabled\":false,\"bPickupEnabled\":false,\"bPreOrderEnabled\":false,\"bPurchasableRetail\":false,\"bRWebShowAllergenCheckbox\":false,\"bShowCalorieCount\":false,\"bShowCalorieDisplay\":false,\"bSpecialNotes\":false,\"bSpecialNotesRequired\":false,\"bStoreDelivery\":false,\"bStoreDineIn\":false,\"bStoreDineToGo\":false,\"bStoreIsCached\":false,\"bStoreIsClosingSoon\":false,\"bStoreIsCurrentlyOpen\":false,\"bStoreIsOpeningSoon\":false,\"bStoreItemLimitations\":false,\"bStoreLocal\":false,\"bStoreOther\":false,\"bStoreTaxFree\":false,\"bTableTopEmpDiscountEnabled\":false,\"bTextReceiptEnabled\":false,\"bUseDeliveryPath\":false,\"categories\":[{\"categoryDescription\":\"American\",\"categoryID\":\"1\",\"categoryImageName\":\"food_american.png\",\"categoryType\":\"Food\",\"primaryCategory\":true}],\"comboEngine\":null,\"currencyCode\":null,\"currencySymbol\":null,\"deliveryLocation\":null,\"emailReceiptEnabled\":false,\"employeeDiscountOwner\":0,\"employeeDiscountOwnerDescription\":null,\"employeeDiscountType\":0,\"employeeDiscountTypeDescription\":null,\"employeeDiscountValue\":0,\"exception\":null,\"favorites\":null,\"feeCost\":null,\"fees\":null,\"grabServer\":null,\"iPreOrderDaysWindow\":0,\"injectsTableIntoPOS\":false,\"inventoryItemAttributeTypes\":null,\"inventoryItemMains\":[],\"inventoryPOS\":null,\"inventoryTitles\":null,\"isTableTop\":false,\"itemPromotions\":null,\"lastLoadedChoiceID\":null,\"lastLoadedChoiceOrder\":null,\"lastLoadedInventoryItemID\":null,\"lastLoadedInventoryItemSubID\":null,\"lastLoadedOptionID\":null,\"lastLoadedOptionOrder\":null,\"loadedValueAccountEnabled\":false,\"localEndTimeToday\":null,\"localStartTimeToday\":null,\"localStoreTimeWeekly\":null,\"menuLevelVersion\":null,\"menuVersionID\":null,\"mobileFeaturedItems\":null,\"modList\":null,\"modSets\":null,\"nearGate\":\"Gate A5\",\"nearGateByLocusLabs\":false,\"nutritionButtonTitle\":null,\"nutritionLink\":null,\"orderSummaryFootnote\":null,\"otherStoreIcon\":null,\"payNowDisabled\":false,\"paymentProviderName\":null,\"platform\":null,\"platformCalling\":null,\"posTables\":null,\"prepTimeMax\":\"30\",\"prepTimeMin\":\"25\",\"receiptTextOverride\":null,\"redRoosterLoyaltyEnabled\":false,\"retailBrandTypes\":null,\"serverDateTimeGMT\":\"\\\/Date(-62135596800000+0000)\\\/\",\"serverTimeGMT\":null,\"serverTimeLocal\":null,\"serverTimeLocalDateTime\":\"\\\/Date(-62135596800000+0000)\\\/\",\"serverTimeLocalDateTimeString\":null,\"showTableInComments\":false,\"sortProductSizesByCostDescending\":false,\"staticUpsellAddon\":false,\"storeClosesIn\":null,\"storeCountryCode\":null,\"storeDelivery\":null,\"storeEmail\":null,\"storeHasTempOperationsActive\":false,\"storeID\":\"384\",\"storeImageName\":\"Afrca_logo.jpg\",\"storeMenuActiveForCache\":0,\"storeMenuItemsCount\":0,\"storeName\":\"Africa Lounge\",\"storeOnline\":false,\"storeOpensIn\":null,\"storeOperatorIdent\":null,\"storeOperatorName\":null,\"storePOS\":null,\"storePOSRefreshDateTimeUTC\":\"\\\/Date(-62135596800000+0000)\\\/\",\"storePOSType\":null,\"storePhoneNumber\":null,\"storePrepTime\":\"25-30 MIN\",\"storeSizes\":null,\"storeWaypointDescription\":\"SEA Concourse A Africa Lounge\",\"storeWaypointID\":\"23083\",\"storeWaypointLatitude\":null,\"storeWaypointLongitude\":null,\"storeWaypointTerminalID\":0,\"tabMaximum\":0,\"taxRate\":null,\"taxes\":null,\"tenders\":null,\"tippingAtCloseOnly\":false,\"tippingAutoAddOnClose\":false,\"tippingAutoPercentage\":0,\"upsaleV2\":null,\"usesTables\":false,\"usesTipping\":false,\"warnIfExistingTicketOnTable\":false},\"storeOrderComment\":null,\"storeOrderRating\":null,\"storePickupLocation\":\"\",\"storeWaypointDescription\":\"SEA Concourse A Africa Lounge\",\"storeWaypointID\":\"23083\",\"storeWaypointLatitude\":null,\"storeWaypointLongitude\":null,\"subTotalCost\":\"17.69\",\"subTotalCostBeforeDiscount\":\"17.69\",\"taxesCost\":\"1.79\",\"tipWindowMinutes\":0,\"totalCost\":\"19.48\",\"transactionID\":\"8k1066qh\"}';
$x='{\"RatingQuestions\":null,\"ShowSurveyLink\":false,\"acceptDateUTC\":null,\"airportIdent\":\"SEA\",\"btCardToken\":null,\"cancelDateUTC\":null,\"cancelReason\":null,\"cartExceptions\":[],\"completionDateUTC\":null,\"creditCardAuth\":null,\"creditCardLastFour\":null,\"creditCardName\":null,\"customerEmail\":\"ludwik+graborder@atyourgate.com\",\"customerImage\":null,\"deliveryDateUTC\":null,\"dfAcceptDateUTC\":\"\\\/Date(-62135596800000+0000)\\\/\",\"dfCancelDateUTC\":\"\\\/Date(-62135596800000+0000)\\\/\",\"dfCompletionDateUTC\":\"\\\/Date(-62135596800000+0000)\\\/\",\"dfDeliveryDateUTC\":\"\\\/Date(-62135596800000+0000)\\\/\",\"dfOrderDateUTC\":\"\\\/Date(-62135596800000+0000)\\\/\",\"discountAmount\":null,\"discountCode\":null,\"discountDescription\":null,\"discountModifiableAtPortal\":false,\"discountOwner\":null,\"discountPushToPOS\":false,\"email\":\"guest@grabmobileapp.com\",\"exception\":\"Order failed to enter the store\u2019s point-of-sale system. YOUR CARD WAS NOT CHARGED - any pending charges will disappear within 72 hours.\",\"facebookUserID\":null,\"firstName\":\"Ludwik\",\"floorID\":null,\"freedomPaySession\":null,\"hasEmployeeDiscount\":false,\"holdOrder\":false,\"isLockerOrder\":false,\"isPendingOrderEnabled\":false,\"isPreOrderEnabled\":false,\"isUnitTest\":false,\"kobp\":\"6d845fd7cb1e7c97577e51f97f5ffd50\",\"languageCode\":\"en\",\"lastName\":\"Ludwik\",\"mobilePhone\":\"\",\"mobilePhoneCountry\":\"\",\"mobilePhoneCountryCode\":null,\"netsSession\":null,\"orderDateUTC\":null,\"orderDetails\":null,\"orderHistoryStarRatingWindowMinutes\":0,\"orderID\":null,\"orderReadyForPickup\":false,\"orderStatus\":0,\"orderTip\":0,\"orderType\":0,\"partnerCode\":\"6d845fd7cb1e7c97577e51f97f5ffd50\",\"platformCalling\":1,\"poiID\":null,\"posOrderID\":null,\"posTicketNumber\":null,\"preAuthOrderID\":null,\"readyDateTime\":\"\\\/Date(-62135596800000+0000)\\\/\",\"receiptLink\":null,\"selected\":false,\"spreedlyTransaction\":null,\"store\":{\"GMTOffset\":\"-7\",\"NormalStoreOpenHours\":[],\"OATAutoReceiptForVKEnabled\":false,\"OATBespokeProductSizeOrder\":false,\"OATBrowseModeEnabled\":false,\"OATDineInNameOptional\":false,\"OATDineInNameRequired\":false,\"OATHideZeroDollar\":false,\"OATItemSearchEnabled\":false,\"OATOrderOnlyNoPayment\":false,\"OATOrderingModeEnabled\":true,\"OATPayTabNowModeEnabled\":false,\"OATSplitTabCustomEnabled\":false,\"OATSplitTabEvenEnabled\":false,\"OATSplitTabMaxSplits\":0,\"OATSplitTabMinTab\":0,\"OATTakeAwayEnabled\":false,\"OATTakeAwayNameRequired\":false,\"OATTipForAlcoholOnly\":false,\"OATTwoColumnCategoriesEnabled\":false,\"OmnivoreStoreUsesTicketNumberAsID\":false,\"ReqEmpAssign\":false,\"ShowReceiptForZero\":false,\"ShowsOrdersOnTablet\":false,\"TemporaryStoreOpenHours\":[],\"additionalDeliveryTime\":0,\"airportIdent\":\"SEA\",\"airportIdentICAO\":\"KSEA\",\"alcoholMealLimitEnabled\":false,\"allowedAlcoholItemsPerMealItem\":0,\"autoCloseTimeMinutes\":0,\"autoReceiptEnabled\":false,\"bAddItemsAtTabletEnabled\":false,\"bAllowKiosksOfflineBasedOnStoreHours\":false,\"bDeliveryOnline\":false,\"bDineInEnabled\":false,\"bEmployeeItems\":false,\"bKioskShowAllergenCheckbox\":false,\"bOpenTicketEnabled\":false,\"bPOSTippingEnabled\":false,\"bPendingOrderEnabled\":false,\"bPickupEnabled\":false,\"bPreOrderEnabled\":false,\"bPurchasableRetail\":false,\"bRWebShowAllergenCheckbox\":false,\"bShowCalorieCount\":false,\"bShowCalorieDisplay\":false,\"bSpecialNotes\":false,\"bSpecialNotesRequired\":false,\"bStoreDelivery\":false,\"bStoreDineIn\":false,\"bStoreDineToGo\":false,\"bStoreIsCached\":false,\"bStoreIsClosingSoon\":false,\"bStoreIsCurrentlyOpen\":true,\"bStoreIsOpeningSoon\":false,\"bStoreItemLimitations\":false,\"bStoreLocal\":false,\"bStoreOther\":false,\"bStoreTaxFree\":false,\"bTableTopEmpDiscountEnabled\":false,\"bTextReceiptEnabled\":false,\"bUseDeliveryPath\":false,\"categories\":[{\"categoryDescription\":\"American\",\"categoryID\":\"1\",\"categoryImageName\":\"food_american.png\",\"categoryType\":\"Food\",\"primaryCategory\":true}],\"comboEngine\":null,\"currencyCode\":\"USD\",\"currencySymbol\":\"$\",\"deliveryLocation\":null,\"emailReceiptEnabled\":false,\"employeeDiscountOwner\":0,\"employeeDiscountOwnerDescription\":null,\"employeeDiscountType\":0,\"employeeDiscountTypeDescription\":null,\"employeeDiscountValue\":0,\"exception\":null,\"favorites\":[],\"feeCost\":\"0\",\"fees\":null,\"grabServer\":null,\"iPreOrderDaysWindow\":0,\"injectsTableIntoPOS\":false,\"inventoryItemAttributeTypes\":null,\"inventoryItemMains\":[],\"inventoryPOS\":null,\"inventoryTitles\":[],\"isTableTop\":false,\"itemPromotions\":null,\"lastLoadedChoiceID\":null,\"lastLoadedChoiceOrder\":null,\"lastLoadedInventoryItemID\":null,\"lastLoadedInventoryItemSubID\":null,\"lastLoadedOptionID\":null,\"lastLoadedOptionOrder\":null,\"loadedValueAccountEnabled\":false,\"localEndTimeToday\":\"7:00 PM\",\"localStartTimeToday\":\"4:30 AM\",\"localStoreTimeWeekly\":\"mo;4:30 AM;7:00 PM;tu;4:30 AM;7:00 PM;we;4:30 AM;7:00 PM;th;4:30 AM;7:00 PM;fr;4:30 AM;7:00 PM;sa;4:30 AM;7:00 PM;su;4:30 AM;7:00 PM;\",\"menuLevelVersion\":null,\"menuVersionID\":null,\"mobileFeaturedItems\":null,\"modList\":null,\"modSets\":null,\"nearGate\":\"Gate A5\",\"nearGateByLocusLabs\":false,\"nutritionButtonTitle\":null,\"nutritionLink\":null,\"orderSummaryFootnote\":null,\"otherStoreIcon\":null,\"payNowDisabled\":false,\"paymentProviderName\":null,\"platform\":null,\"platformCalling\":null,\"posTables\":null,\"prepTimeMax\":\"30\",\"prepTimeMin\":\"25\",\"receiptTextOverride\":null,\"redRoosterLoyaltyEnabled\":false,\"retailBrandTypes\":null,\"serverDateTimeGMT\":\"\\\/Date(-62135596800000+0000)\\\/\",\"serverTimeGMT\":\"8\\\/26\\\/2021 4:24 PM\",\"serverTimeLocal\":\"8\\\/26\\\/2021 9:24 AM\",\"serverTimeLocalDateTime\":\"\\\/Date(-62135596800000+0000)\\\/\",\"serverTimeLocalDateTimeString\":null,\"showTableInComments\":false,\"sortProductSizesByCostDescending\":false,\"staticUpsellAddon\":false,\"storeClosesIn\":null,\"storeCountryCode\":null,\"storeDelivery\":null,\"storeEmail\":\"\",\"storeHasTempOperationsActive\":false,\"storeID\":\"384\",\"storeImageName\":\"Afrca_logo.jpg\",\"storeMenuActiveForCache\":0,\"storeMenuItemsCount\":0,\"storeName\":\"Africa Lounge\",\"storeOnline\":true,\"storeOpensIn\":null,\"storeOperatorIdent\":null,\"storeOperatorName\":null,\"storePOS\":null,\"storePOSRefreshDateTimeUTC\":\"\\\/Date(-62135596800000+0000)\\\/\",\"storePOSType\":null,\"storePhoneNumber\":\"\",\"storePrepTime\":\"25-30 MIN\",\"storeSizes\":null,\"storeWaypointDescription\":\"SEA Concourse A Africa Lounge\",\"storeWaypointID\":\"23083\",\"storeWaypointLatitude\":\"47.441501\",\"storeWaypointLongitude\":\"-122.300134\",\"storeWaypointTerminalID\":569,\"tabMaximum\":0,\"taxRate\":\"0.101\",\"taxes\":null,\"tenders\":null,\"tippingAtCloseOnly\":false,\"tippingAutoAddOnClose\":false,\"tippingAutoPercentage\":0,\"upsaleV2\":null,\"usesTables\":false,\"usesTipping\":false,\"warnIfExistingTicketOnTable\":false},\"storeOrderComment\":null,\"storeOrderRating\":null,\"storePickupLocation\":\"\",\"storeWaypointDescription\":null,\"storeWaypointID\":\"23083\",\"storeWaypointLatitude\":null,\"storeWaypointLongitude\":null,\"subTotalCost\":null,\"subTotalCostBeforeDiscount\":null,\"taxesCost\":null,\"tipWindowMinutes\":0,\"totalCost\":null,\"transactionID\":null}';
$x=stripslashes($x);

var_dump($x);

die();




$location= new \Parse\ParseObject('TerminalGateMap', '6PqO1GwsE4');
$location->fetch(true);
$x=isDeliveryFromSlackAvailableForDelivery(
    $location,
    0,
    true
);
var_dump($x);
die();

*/
$retailerMdw = new \Parse\ParseObject('Retailers', 'ze98KPAJ3P');
$retailerMdw->fetch(true);

$result = isRetailerClosed($retailerMdw, 0, 0);
var_dump($result);

$result = isRetailerPingActive($retailerMdw);
var_dump($result);

$result = pingRetailer($retailerMdw);
var_dump($result);

/*
$deliveryLocation = new \Parse\ParseObject('TerminalGateMap','Ovn9tknLVG');
$deliveryLocation->fetch();
$result = isAnyDeliveryFromSlackAvailable($deliveryLocation,0);
var_dump($result);
*/
