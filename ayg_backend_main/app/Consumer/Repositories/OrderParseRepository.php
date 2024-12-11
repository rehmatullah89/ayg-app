<?php
namespace App\Consumer\Repositories;

use App\Consumer\Entities\Order;
use App\Consumer\Exceptions\Exception;
use App\Consumer\Mappers\ParseOrderIntoOrderMapper;
use App\Consumer\Mappers\ParseRetailerIntoRetailerMapper;
use App\Consumer\Mappers\ParseUserIntoUserMapper;
use App\Consumer\Mappers\ParseTerminalGateMapIntoTerminalGateMapMapper;
use App\Delivery\Repositories\OrderDeliveryStatusCacheRepository;
use Parse\ParseQuery;

/**
 * Class OrderParseRepository
 * @package App\Consumer\Repositories
 */
class OrderParseRepository extends ParseRepository implements OrderRepositoryInterface
{

    public function getOrderWithRetailer(string $orderId): Order
    {
        // Also checks if the order belongs to the User
        $parseOrder = parseExecuteQuery(array(
            "objectId" => $orderId
        ), "Order", "", "",
            array("retailer", "retailer.location", "retailer.retailerType", "deliveryLocation", "coupon", "user"), 1);

        $order = ParseOrderIntoOrderMapper::map($parseOrder);
        $location = ParseTerminalGateMapIntoTerminalGateMapMapper::map($parseOrder->get('retailer')->get('location'));
        $retailer = ParseRetailerIntoRetailerMapper::map($parseOrder->get('retailer'));
        $retailer->setLocation($location);
        $order->setRetailer($retailer);
        return $order;
    }


    /**
     * @param $orderId
     * @param $userId
     * @return bool
     *
     * returns true if Order found for given order Id and userId
     */
    public function checkIfOrderExistsForAGivenUser($orderId, $userId)
    {
        $userInnerQuery = new ParseQuery('_User');
        $userInnerQuery->equalTo("objectId", $userId);

        $query = new ParseQuery("Order");
        $query->equalTo("objectId", $orderId);
        $query->matchesQuery("user", $userInnerQuery);
        $query->limit(1);
        $totalRecords = $query->count();

        if (!$totalRecords) {
            return false;
        }
        return true;
    }

    public function abandonOpenOrdersByUserId(string $userId): void
    {
        $userInnerQuery = new ParseQuery('_User');
        $userInnerQuery->equalTo("objectId", $userId);

        $query = new ParseQuery('Order');
        $query->matchesQuery("user", $userInnerQuery);
        $query->equalTo("status", Order::STATUS_NOT_ORDERED);
        $query->find();
        $records = $query->find();
        foreach ($records as $orderObject) {
            $orderObject->set('status', Order::STATUS_NOT_ABANDONED);
            $orderObject->set('comment', Order::COMMENT_CART_DELETED_DUE_TO_VERIFICATION_BY_OTHER_USER);
            $orderObject->save();
        }
    }

    public function switchCartOwner(string $fromUserId, string $toUserUserId): void
    {
        $userFromInnerQuery = new ParseQuery('_User');
        $userFromInnerQuery->equalTo("objectId", $fromUserId);
        $userToInnerQuery = new ParseQuery('_User');

        $userToInnerQuery->equalTo("objectId", $toUserUserId);
        $userToInnerQueryResult = $userToInnerQuery->find();
        if (empty($userToInnerQueryResult) || $userToInnerQueryResult === false || !is_array($userToInnerQueryResult)) {
            throw new \Exception('User not Found');
        }
        $userToInnerQueryResult = $userToInnerQueryResult[0];

        $query = new ParseQuery('Order');
        $query->matchesQuery("user", $userFromInnerQuery);
        $query->equalTo("status", Order::STATUS_NOT_ORDERED);
        $query->find();
        $records = $query->find();
        foreach ($records as $orderObject) {
            $orderObject->set('user', $userToInnerQueryResult);
            $orderObject->save();
        }
    }

    public function saveTipData(string $orderId, ?int $tipAsPercentage, ?int $tipAsFixedValue): Order
    {
        $query = new ParseQuery("Order");
        $query->equalTo("objectId", $orderId);
        $query->includeKey("user");
        $query->limit(1);
        $orders = $query->find();
        $order = $orders[0];

        if (in_array($order->get('status'), Order::STATUSES_LIST_CART) === false) {
            throw new Exception('You can apply tip only to not submitted order');
        }

        if ($tipAsPercentage === null && $tipAsFixedValue !== null) {
            $order->set('tipCents', $tipAsFixedValue);
            $order->set('tipPct', null);
            $order->set('tipAppliedAs', Order::TIP_APPLIED_AS_FIXED_VALUE);
            $order->save();
        }

        if ($tipAsPercentage !== null && $tipAsFixedValue === null) {
            $order->set('tipCents', null);
            $order->set('tipPct', $tipAsPercentage);
            $order->set('tipAppliedAs', Order::TIP_APPLIED_AS_PERCENTAGE);
            $order->save();
        }

        return ParseOrderIntoOrderMapper::mapWithUser($order);
    }

    public function saveCartItems($postVars): array
    {
        $orderId = urldecode($postVars['orderId']);
        $orderItemId = urldecode($postVars['orderItemId']);
        $uniqueRetailerItemId = urldecode($postVars['uniqueRetailerItemId']);
        $itemQuantity = intval(urldecode($postVars['itemQuantity']));
        $itemComment = sanitize(urldecode($postVars['itemComment']));
        $options = $postVars['options']; // URL Decoded from Slim Library

        if (empty($itemComment)){
            // a hack, next part is checking if it is a "0" string
            $itemComment = '0';
        }

        if (empty($orderId)
            || empty_zero_allowed($orderItemId)
            || empty($uniqueRetailerItemId)
            || empty($itemQuantity)
            || empty_zero_allowed($itemComment)
            || empty_zero_allowed($options)
        ) {

            json_error("AS_005", "", "Incorrect API Call. PostVars = " . json_encode($postVars));
        }

        if (isItem86isedFortheDay($uniqueRetailerItemId)) {

            json_error("AS_895", "We are sorry, but this item is currently not available.",
                "Item 86 found, Unique Id: " . $uniqueRetailerItemId . ", Order Id: " . $orderId, 1);
        }

        /////////////////////////////////////////////////////////////////
        // Initialize Retailer under user
        $retailerItem = new RetailerItem($uniqueRetailerItemId);

        // Fetch Retailer Item
        $retailerItem->fetchRetailerItem();

        // Fetch Retailer Item Modifiers
        $retailerItem->fetchRetailerItemModifiers();

        // Fetch Retailer Item Modifier Options
        $retailerItem->fetchRetailerItemModifierOptions();

        if (empty($retailerItem->getObjectId())) {

            json_error("AS_868", "", "Item not found = " . $uniqueRetailerItemId, 1);
        }
        /////////////////////////////////////////////////////////////////

        // Initialize Order under user
        $order = new Order($GLOBALS['user']);

        // Fetch available order by order id
        $order->fetchOrderByOrderId($orderId);

        ////////////////////////////////////////////////
        // Verify if an Open Order was found
        try {

            $rules = [
                "exists" => true,
                "statusInList" => listStatusesForCart(),
                "matchesRetailerId" => $retailerItem->getUniqueRetailerId()
            ];

            // Validate if we found the order
            $order->performChecks($rules);
        } catch (Exception $ex) {

            json_error("AS_805", "",
                "Order not found or not yet submitted! Order Id = " . $orderId . " - " . $ex->getMessage());
        }

        $itemComment = $itemComment;
        if (strcasecmp(strval($itemComment), "0") == 0) {

            $itemComment = "";
        }

        $modifierOptionsInJSONForSaving = array();
        if ($retailerItem->hasModifiers()) {

            // JSON decode the Options array
            try {

                $options = json_decode($options, true);

                if (is_array($options)) {

                    $options = sanitize_array($options);
                } else {

                    $options = [];
                }
            } catch (Exception $ex) {
            }

            if (!is_array($options)) {

                json_error("AS_807", "You must select the required options.",
                    "Modifier details not provided. Options array was not well-formed." . " PostVars = " . json_encode($postVars),
                    1);
            }

            // Process Options
            $modifierGroupsForOptionsSelected = array();
            $optionsProcessed = array();

            foreach ($options as $index => $optionSelected) {

                // If missing indexes
                if (!isset($optionSelected["id"]) || empty(trim($optionSelected["id"]))
                    || !isset($optionSelected["quantity"]) || empty(intval($optionSelected["quantity"]))
                ) {

                    json_error("AS_808", "You must select the required options.",
                        "Submitted modifier details (Id or Quantity) are not valid." . " PostVars = " . json_encode($postVars));
                }

                $optionSelected["id"] = trim($optionSelected["id"]);
                $optionSelected["quantity"] = intval($optionSelected["quantity"]);

                // Modifier Quantity is set to 0, so considered as a modifier deletion
                if ($optionSelected["quantity"] == 0) {

                    continue;
                }

                // Add to Options Processed
                $optionsProcessed[$optionSelected["id"]] = $optionSelected;
            }

            // List all modifiers for the item in the DB
            $requiredModifiersButNotSelected = $requiredModifiers = $retailerItem->getRequiredModifiers();

            // List all Modifier options for the item in the DB
            $objModifiersOptions = $retailerItem->getModifierOptions();

            // Iterate thru DB Modifiers to verify options rules and missing required options
            $optionsProcessedFromDBCount = 0;
            foreach ($objModifiersOptions as $obj) {

                $uniqueOptionId = $obj->getUniqueId();

                // If this option was selected
                if (isset($optionsProcessed[$uniqueOptionId])) {

                    $optionsProcessedFromDBCount++;

                    $uniqueRetailerItemModifierId = $obj->getUniqueRetailerItemModifierId();

                    // Add Quantities for the Modifier Group at level so we can confirm the min / max logic later
                    // If index was not yet initialized, set to 0
                    if (!isset($modifierGroupsForOptionsSelected[$uniqueRetailerItemModifierId])) {

                        $modifierGroupsForOptionsSelected[$uniqueRetailerItemModifierId] = 0;
                    }

                    // Add quantity
                    $modifierGroupsForOptionsSelected[$uniqueRetailerItemModifierId] += $optionsProcessed[$uniqueOptionId]["quantity"];

                    // Save the options in JSON so to be stored in DB
                    $modifierOptionsInJSONForSaving[] = array(
                        "objectId" => $obj->getObjectId(),
                        "optionId" => $obj->getOptionId(),
                        "id" => $optionsProcessed[$uniqueOptionId]["id"],
                        "quantity" => $optionsProcessed[$uniqueOptionId]["quantity"],
                        "price" => empty($obj->getPricePerUnit()) ? 0 : $obj->getPricePerUnit()
                    );
                }
            }

            // If the count of options found in RetailerItemModifierOptions < options sent
            // Then some invalid options were provided
            if ($optionsProcessedFromDBCount < count_like_php5($optionsProcessed)) {

                json_error("AS_811", "", "Some invalid options were provided" . " PostVars = " . json_encode($postVars), 2);
            }

            // Check if the required minimum quantity and max quantity are in place for those Options that are selected
            foreach ($modifierGroupsForOptionsSelected as $uniqueRetailerItemModifierId => $quantitySelected) {

                $modifierIsRequired = $retailerItem->getModifier($uniqueRetailerItemModifierId)->getIsRequired();
                $modifierMaxQuantity = $retailerItem->getModifier($uniqueRetailerItemModifierId)->getMaxQuantity();
                $modifierMinQuantity = $retailerItem->getModifier($uniqueRetailerItemModifierId)->getMinQuantity();

                if ($modifierIsRequired) {

                    // Mark that this required modifier was indeed selected
                    unset($requiredModifiersButNotSelected[$uniqueRetailerItemModifierId]);

                    // If Quantity is < min required
                    if ($quantitySelected < $modifierMinQuantity) {

                        json_error("AS_809", "You must select options before adding to item.",
                            "Modifier Options selected are less than minimum required. Required min quantity rule violated for Modifier Group $uniqueRetailerItemModifierId Quantity selected $quantitySelected but min required $modifierMinQuantity" . " PostVars = " . json_encode($postVars),
                            2);
                    }
                }

                // If Quantity is > max allowed
                if ($quantitySelected > $modifierMaxQuantity && $modifierMaxQuantity != 0) {

                    json_error("AS_810",
                        "Quantity selected for the option is higher than supported by the Retailer. Please select a lower value.",
                        "Modifier Options selected are greater than max allowed. Max quantity rule violated for Modifier Group $uniqueRetailerItemModifierId Quantity selected $quantitySelected but max allowed $modifierMaxQuantity" . " PostVars = " . json_encode($postVars),
                        2);
                }
            }

            // Check all required modifiers were selection
            if (count_like_php5($requiredModifiersButNotSelected) > 0) {

                json_error("AS_811", "You must select the appropriate options before adding to item.",
                    "Required Modifiers were not selected." . " PostVars = " . json_encode($postVars), 2);
            }
        }

        ////////////////////////////////////////////////////////////////////////////////////////////////
        // Check if we have reach max for the Order
        $order->fetchOrderModifiers();

        // Start with requested quantity
        //$orderQuantity = $order->getQuantity() + $itemQuantity;

        // If item being updated, then remove the quantity of the item
        //if (!empty($orderItemId)) {

        //    $orderQuantity = $orderQuantity - $order->getModifier($orderItemId)->getItemQuantity();
        //}

        // Max 10 items
        //if ($orderQuantity > 10) {

        //json_error("AS_876", "You may order only up to 10 items per order. Please adjust quantities.", "Max order quantity reached ($orderQuantity)." . " PostVars = " . json_encode($postVars));
        //}
        ////////////////////////////////////////////////////////////////////////////////////////////////

        // Deleting Item
        // Delete entry if the ItemQuantity or ModifierQuantity is set to 0
        if ($itemQuantity == 0
            && !empty($orderItemId) && $orderItemId != "0"
        ) {

            if (empty($order->getModifier($orderItemId))) {

                json_error("AS_893", "",
                    "Cart Operation Failed! - ModifierId not found. PostVars = " . json_encode($postVars), 1);
            }

            try {

                $order->deleteFromCart($orderItemId);
                $orderItemObjectId = $orderItemId;
                $logForAction = 'delete';
            } catch (Exception $ex) {

                json_error("AS_875", "",
                    "Cart Operation Failed!" . $ex->getMessage() . " PostVars = " . json_encode($postVars), 1);
            }
        }
        // Updating Item
        if (!empty($orderItemId) && $orderItemId != "0") {

            if (empty($order->getModifier($orderItemId))) {

                json_error("AS_893", "",
                    "Cart Operation Failed! - ModifierId not found. PostVars = " . json_encode($postVars), 1);
            }

            $itemTax = 0;

            ////////////////////////////////////////////////////
            // Check with external partner before adding to cart
            ////////////////////////////////////////////////////
            list($isExternalPartnerOrder, $tenderType, $dualPartnerConfig) = isExternalPartnerOrder($order->getRetailer()->get("uniqueId"));

            if ($isExternalPartnerOrder == true) {

                // Prepare Item Array
                if (strcasecmp($dualPartnerConfig->get('partner'), 'hmshost') == 0) {

                    $item = [$retailerItem->getDBObj(), $itemQuantity, $modifierOptionsInJSONForSaving];
                    $cartFormatted = prepareHMSHostItemArray([$item]);
                }

                $itemTax = getPartnerTaxes($dualPartnerConfig, $cartFormatted, $order->getDBObj(),
                    $retailerItem->getDBObj());
            }
            ////////////////////////////////////////////////////

            try {

                $orderItemObjectId = $order->updateCart($orderItemId, $itemQuantity, $itemComment,
                    $modifierOptionsInJSONForSaving, json_encode(["itemTax" => $itemTax]));
                $logForAction = 'update';
            } catch (Exception $ex) {

                json_error("AS_875", "",
                    "Cart Operation Failed!" . $ex->getMessage() . " PostVars = " . json_encode($postVars), 1);
            }
        } // Adding Item
        else {

            $itemTax = 0;

            ////////////////////////////////////////////////////
            // Check with external partner before adding to cart
            ////////////////////////////////////////////////////
            list($isExternalPartnerOrder, $tenderType, $dualPartnerConfig) = isExternalPartnerOrder($order->getRetailer()->get("uniqueId"));

            if ($isExternalPartnerOrder == true) {

                // Prepare Item Array
                if (strcasecmp($dualPartnerConfig->get('partner'), 'hmshost') == 0) {

                    $item = [$retailerItem->getDBObj(), $itemQuantity, $modifierOptionsInJSONForSaving];
                    $cartFormatted = prepareHMSHostItemArray([$item]);
                }

                $itemTax = getPartnerTaxes($dualPartnerConfig, $cartFormatted, $order->getDBObj(),
                    $retailerItem->getDBObj());
            }
            ////////////////////////////////////////////////////

            try {

                $orderItemObjectId = $order->addToCart($retailerItem, $itemQuantity, $itemComment,
                    $modifierOptionsInJSONForSaving, json_encode(["itemTax" => $itemTax]));
                $logForAction = 'add';
            } catch (Exception $ex) {

                json_error("AS_875", "",
                    "Cart Operation Failed!" . $ex->getMessage() . " PostVars = " . json_encode($postVars), 1);
            }
        }

        // Log user event
        if ($GLOBALS['env_LogUserActions']) {

            try {

                $retailer = parseExecuteQuery(["uniqueId" => $retailerItem->getUniqueRetailerId()], "Retailers", "", "",
                    ["location"], 1);
                $retailerName = $retailer->get('retailerName') . ' (' . $retailer->get('location')->get('locationDisplayName') . ')';
                $airportIataCode = $retailer->get('airportIataCode');

                //$workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueConsumerName']);
                $workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueMidPriorityAsynchConsumerName']);

                $workerQueue->sendMessage(
                    array(
                        "action" => "log_user_action_add_cart",
                        "content" =>
                            array(
                                "objectId" => $GLOBALS['user']->getObjectId(),
                                "data" => json_encode([
                                    "retailer" => $retailerName,
                                    "actionForRetailerAirportIataCode" => $airportIataCode,
                                    "airportIataCode" => $airportIataCode,
                                    "orderId" => $order->getObjectId(),
                                    "retailerUniqueId" => $retailer->get('retailerUniqueId'),
                                    "uniquetailerItemId" => $uniqueRetailerItemId,
                                    "actionType" => $logForAction
                                ]),
                                "timestamp" => time()
                            )
                    )
                );
            } catch (Exception $ex) {

                $response = json_decode($ex->getMessage(), true);
                json_error($response["error_code"], "",
                    "Log user action queue message failed " . $response["error_message_log"], 1, 1);
            }
        }

        // If the customer was acquired by referral and has available credits
        if (wasUserBeenAcquiredViaReferral($order->getUserObject())
            && getAvailableUserCreditsViaMap($order->getUserObject())[1] > 0
        ) {

            // build the cart again
            // check if there is a coupon and if the cart now has referral credits
            // If so, remove any coupons that might been added before the item was added or removed if the referral credit is on the order, this is stop double dipping
            if (doesOrderHaveReferralSignupCreditApplied($order->getDBObj())) {

                $order->removeCoupon();
            }
        }

        // Drop Cart cache
        $namedCacheKey = 'cart' . '__u__' . $order->getUserObjectId() . '__o__' . $order->getObjectId();
        delCacheByKey(getNamedRouteCacheName($namedCacheKey));

        $namedCacheKey = 'cartv2' . '__u__' . $order->getUserObjectId() . '__o__' . $order->getObjectId();
        delCacheByKey(getNamedRouteCacheName($namedCacheKey));

        delCacheByKey(getCacheKeyHMSHostTaxForOrder($order->getObjectId()));

        $responseArray = array("orderItemObjectId" => $orderItemObjectId);

        return $responseArray;
    }
}
