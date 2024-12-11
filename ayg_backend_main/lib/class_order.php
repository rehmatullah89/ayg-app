<?php

use Parse\ParseQuery;
use Parse\ParseObject;

class Order {

	private $orderId;
	private $retailerId;
	private $dbObj;
	private $userDBObj;
	private $retailerPOSConfigDBObj;

	private $orderModifiersObjList; // DO DEFINE AS ARRAY
	private $orderStatusesObjList = [];

	function __construct($userDBObj) {

		$this->userDBObj = $userDBObj;
	}

	function set($key, $value) {

		$this->$key = replaceSpecialChars($value);
	}

	function get($key) {

		return $this->$key;
	}

	function fetchOrderModifiersCount($orderId) {

		// If we have modifiers already fetched
		if(is_array($this->get("orderModifiersObjList"))) {

			return count_like_php5($this->get("orderModifiersObjList"));
		}

		return $this->fetchOrderModifiersCountFromDB($orderId);
	}

	function isOrderEmpty() {

		// If no items in the cart, then cart is empty
		if($this->fetchOrderModifiersCount($this->getOrderId()) == 0) {

			return true;
		}
		else {

			return false;
		}
	}

	function performChecks($rules = ["exists" => true]) {

		try {

			foreach($rules as $type => $value) {

				// Exists = true, requires an order to exist
				if(strcasecmp($type, "exists")==0
					&& $value == true) {

					$this->rulesValidateOrderExists();
				}

				// Exists = false, requires an order to not exist
				else if(strcasecmp($type, "exists")==0
					&& $value == false) {

					$this->rulesValidateOrderDoesNotExists();
				}

				// matchesRetailerId, matches the given retailer id
				else if(strcasecmp($type, "matchesRetailerId")==0
					&& !empty($value)) {

					$this->rulesValidateOrderMatchRetailerId($value);
				}

				// statusInList, matches one of the given status
				else if(strcasecmp($type, "statusInList")==0
					&& is_array($value)) {

					$this->rulesValidateOrderStatusInList($value);
				}
			}
		}
		catch(Exception $ex) {

			throw new Exception("Order Validation failed = " . $ex->getMessage() . " - OrderId = " . $this->get("orderId") . " - RetailerId = " . $this->get("retailerId") . " - Rules = " . json_encode($rules));
		}
	}

	function rulesValidateOrderExists() {

		if(!is_object($this->getDBObj())) {

			throw new Exception("Order not found");
		}
	}

	function rulesValidateOrderDoesNotExists() {

		if(is_object($this->getDBObj())) {

			throw new Exception("Order found");
		}
	}

	function rulesValidateOrderMatchRetailerId($retailerId) {

		if(strcasecmp($this->getRetailerId(), $retailerId) != 0) {

			throw new Exception("Retailer didn't match");
		}
	}

	function rulesValidateOrderStatusInList($statusList) {

		if(!in_array($this->getStatus(), $statusList)) {

			throw new Exception("Status didn't match");
		}
	}

	function getUserObjectId() {

		return $this->getFromDB("user>objectId");
	}

	function getUserObject() {

		return $this->getFromDB("user");
	}

	function getObjectId() {

		return $this->getFromDB("objectId");
	}

	function getOrderId() {

		return $this->getFromDB("objectId");
	}

	function getRetailerId() {

		return $this->getFromDB("retailer>uniqueId");
	}

	function getStatus() {

		return $this->getFromDB("status");
	}

	function getStatusDelivery() {

		return $this->getFromDB("statusDelivery");
	}

	function getFullfillmentType() {

		return $this->getFromDB("fullfillmentType");
	}

	function getETATimestamp() {

		return $this->getFromDB("etaTimestamp");
	}

	function getsubmitTimestamp() {

		return $this->getFromDB("submitTimestamp");
	}

	function getAirportIataCode() {

		return $this->getFromDB("retailer>airportIataCode");
	}

	function getDeliveryLocation() {

		return $this->getFromDB("deliveryLocation>objectId");
	}

	function getFlightTrip() {

		return $this->getFromDB("flightTrip");
	}

	function getFlightTripDepartureAirportIataCode() {

		return $this->getFromDB("flightTrip>flight>departureAirportIataCode");
	}

	function getFlightTripAirlineIataCode() {

		return $this->getFromDB("flightTrip>flight>airlineIataCode");
	}

	function getFlightTripArrivalAirportIataCode() {

		return $this->getFromDB("flightTrip>flight>arrivalAirportIataCode");
	}

	function getFlightTripLastKnownDepartureTimestamp() {

		return $this->getFromDB("flightTrip>flight>lastKnownDepartureTimestamp");
	}

	function getFlightTripAirlineFlightNum() {

		return $this->getFromDB("flightTrip>flight>airlineFlightNum");
	}

	function getUpdatedAt() {

		return $this->getFromDB("updatedAt");
	}

	function getRetailer() {

		return $this->getFromDB("retailer");
	}

	function getTotalsWithFees() {

		return $this->getFromDB("totalsWithFees");
	}

	function addOrderModifier($orderModifierObj) {

		$this->orderModifiersObjList[$orderModifierObj->getObjectId()] = $orderModifierObj;
	}

	function addOrderStatus($orderStatusObj) {

		$this->orderStatusesObjList[$orderStatusObj->getObjectId()] = $orderStatusObj;
	}

	// Sum all quantities of all order modifiers
	function getQuantity() {

		$quantity = 0;

		if(is_array($this->orderModifiersObjList)) {

			foreach($this->orderModifiersObjList as $orderModifier) {

				$quantity += $orderModifier->getItemQuantity();
			}
		}

		return $quantity;
	}

	function closeOrder() {

		return $this->closeOrderInDB();
	}

	function createNewOrder($retailerId) {

		$this->createNewOrderInDB($retailerId);
	}

	function getStatusForPrint($status='', $statusDelivery='') {

	    if(empty($status)) {

	        $status = $this->getStatus();
	        $statusDelivery = $this->getStatusDelivery();
	    }

	    $fullfillmentType = $this->getFullfillmentType();

	    // If completed order
	    if (strcasecmp(orderStatusType($status), "FULLFILLED") == 0) {

	        $statusToPrint = $GLOBALS['statusCompleted'][$fullfillmentType];
	    }
	    // Check if a print ready name exists
	    else if (!empty(($GLOBALS['statusNames'][$status]["print"]))) {

	        // Use the available print status
	        $statusToPrint = $GLOBALS['statusNames'][$status]["print"];

	        // If delivery order and a status value is available
	        // then use this
	        if (strcasecmp($fullfillmentType, "d") == 0
	            && !empty($GLOBALS['statusDeliveryNames'][$statusDelivery]["print"])
	            && strcasecmp(orderStatusType($status), "NOT_FULLFILLED") != 0
	        ) {

	            $statusToPrint = $GLOBALS['statusDeliveryNames'][$statusDelivery]["print"];
	        }
	    } 
	    // No status should be provided
	    else {

	        $statusToPrint = "";
	    }

	    return strtoupper($statusToPrint);
	}

	function getStatusCategory($status = '', $statusDelivery = '') {

	    if(empty($status)) {

	        $status = $this->getStatus();
	        $statusDelivery = $this->getStatusDelivery();
	    }

	    $fullfillmentType = $this->getFullfillmentType();

	    $statusCategoryCode = $GLOBALS['statusNames'][$status]["statusCategoryCode"];

	    // If delivery order and a status value is available
	    if (strcasecmp($fullfillmentType, "d") == 0
	        && !empty($GLOBALS['statusDeliveryNames'][$statusDelivery]["statusCategoryCode"])
	        && strcasecmp(orderStatusType($status), "NOT_FULLFILLED") != 0
	    ) {

	        $statusCategoryCode = $GLOBALS['statusDeliveryNames'][$statusDelivery]["statusCategoryCode"];
	    }

	    return $statusCategoryCode;
	}

	function getActiveOrCompletedCode() {

	    $flag = 'a';

	    // Order Status is fullfilled or Not fullfilled (e.g. cancelled)
	    if (in_array($this->getStatus(), array_merge(listStatusesForSuccessCompleted(), listStatusesForCancelled()))) {

	        // Don't List orders that were completed in last 1 hours
	        // These are included in active
	        // Exclude cancelled orders
	        if (($this->getETATimestamp() + 60 * 60) > time()
	            && !in_array($this->getStatus(), listStatusesForCancelled())
	        ) {

	            $flag = 'a';
	        }
	        else {

	            // Else List these orders
	            $flag = 'c';
	        }
	    }
	    else {

	        $flag = 'a';
	    }

	    return $flag;
	}

	function getStatusList() {

        //json_error(json_encode($this->getStatus()),json_encode($this->getStatus()));
        //json_error(json_encode(listStatusesForInformUser()),json_encode(listStatusesForInformUser()));
	    // If the order was cancelled
	    // Show Submitted time and then cancelled time
		if(in_array($this->getStatus(), listStatusesForCancelled())) {

			$this->fetchOrderStatuses(array_merge(listStatusesForSubmitted(true), listStatusesForCancelled(true)));
	    }

	    // Else
	    // Get all statuses that are inform user marked
	    else {

			$this->fetchOrderStatuses(listStatusesForInformUser());
	    }

	    $responseArrayTemp = array();
	    $responseArray = array();
	    $airporTimeZone = fetchAirportTimeZone($this->getAirportIataCode(), date_default_timezone_get());

		$orderStatusList = $this->get("orderStatusesObjList");

	    // Get all Statuses in an Array first
	    foreach($orderStatusList as $orderStatus) {

	        $status = $orderStatus->getStatus();
	        $statusDelivery = $orderStatus->getStatusDelivery();

	        $lastUpdated = !empty($orderStatus->getManualUpdatedAt()) ? $orderStatus->getManualUpdatedAt() : $orderStatus->getUpdatedAt()->getTimestamp();

	        // If at Order Status level, this flag set then, don't account for delivery level statuses separately
	        if (isset($GLOBALS['statusNames'][$status]['consolidateMultipleStatusReports'])
	            && $GLOBALS['statusNames'][$status]['consolidateMultipleStatusReports'] == true
	        ) {

	            // Create unique status key
	            $statusKey = $status;
	        } else {

	            // Create unique status key
	            $statusKey = $status . '-' . $statusDelivery;
	        }

	        // If Delivery Status is available, but this status is not allowed to be informed, then skip row
	        if

			(((!empty($statusDelivery) && !in_array($statusDelivery, listDeliveryStatusesForInformUser()))
	        	|| !in_array($statusKey, $GLOBALS['statusIndexexForPrint'][$this->getFullfillmentType()])


			)
			&&
				$this->getFromDB("isScheduled")!==true
			)
	        {
	            continue;
	        }

	        $responseArrayTemp[$statusKey]["lastUpdateAirportTime"] = orderFormatDate($airporTimeZone, $lastUpdated, 'time');
	        $responseArrayTemp[$statusKey]["lastUpdateTimestampUTC"] = $lastUpdated;
	        $responseArrayTemp[$statusKey]["status"] = $this->getStatusForPrint($status, $statusDelivery);
	        $responseArrayTemp[$statusKey]["statusCode"] = $status;
	        $responseArrayTemp[$statusKey]["statusDeliveryCode"] = $statusDelivery;
	        $responseArrayTemp[$statusKey]["statusCategoryCode"] = $this->getStatusCategory($status, $statusDelivery);
	    }

	    foreach ($responseArrayTemp as $statusArray) {

	        $responseArray[] = $statusArray;
	    }

	    return $responseArray;
	}

	function getStatusEmpty($status, $statusDelivery) {

	    $responseArrayTemp["lastUpdateAirportTime"] = "";
	    $responseArrayTemp["lastUpdateTimestampUTC"] = 0;
	    $responseArrayTemp["status"] = $this->getStatusForPrint($status, $statusDelivery);
	    $responseArrayTemp["statusCode"] = $status;
	    $responseArrayTemp["statusDeliveryCode"] = $statusDelivery;
	    $responseArrayTemp["statusCategoryCode"] = $this->getStatusCategory($status, $statusDelivery);

	    return $responseArrayTemp;
	}

	function getModifier($orderModifierId) {

		if(!isset($this->orderModifiersObjList[$orderModifierId])) {

			return "";
		}
		else {

			return $this->orderModifiersObjList[$orderModifierId];
		}
	}

	function addToCart($retailerItem, $itemQuantity, $itemComment, $modifierOptions, $taxes) {

		try {

			$obj = new OrderModifier('');
			$response = $obj->createNewOrderModifierInDB($this, $retailerItem, $itemQuantity, $itemComment, $modifierOptions, $taxes);
		}
		catch (Exception $ex) {

			throw new Exception($ex->getMessage());
		}

		return $response;
	}

	function updateCart($orderModifierId, $itemQuantity, $itemComment, $modifierOptions, $taxes) {

		try {

			$this->getModifier($orderModifierId)->updateOrderModifierInDB($itemQuantity, $itemComment, $modifierOptions, $taxes);
		}
		catch (Exception $ex) {

			throw new Exception($ex->getMessage());
		}
	}

	function deleteFromCart($orderModifierId) {

		try {

			$this->getModifier($orderModifierId)->deleteOrderModifierInDB();
		}
		catch (Exception $ex) {

			throw new Exception($ex->getMessage());
		}
	}

	function applyTip($tipPct=0, $tipDollar=0) {

		$tipPct = intval($tipPct);
		$this->applyTipInDB($tipPct, $tipDollar);	
	}

	function applyCoupon($coupon) {

		$this->applyCouponInDB($coupon);
	}

	function removeCoupon() {

		$this->removeCouponInDB();
	}

	function applyAirportEmployeeDiscount() {

		$this->applyAirportEmployeeDiscountInDB();
	}

	function removeAirportEmployeeDiscount() {

		$this->removeAirportEmployeeDiscountInDB();
	}

	function applyMilitaryDiscount() {

		$this->applyMilitaryDiscountInDB();
	}

	function removeMilitaryDiscount() {

		$this->removeMilitaryDiscountInDB();
	}

	///////////////////////////// DB //////////////////////////////

	function setOrderId() {

		$order = $this->getDBObj();

		if(!is_object($order)) {

			$orderId = "";
		}
		else {

			$orderId = $order->getObjectId();
		}

		$this->set("orderId", $orderId);
	}

	function getDBObjUser() {

		return $this->userDBObj;
	}

	function getFromDB($key) {

		if(!is_object($this->getDBObj())) {

			return "";
		}

		// Nested objct requested
		if(preg_match("/\>/si", $key)) {

			$obj = $this->getDBObj();

			$keyList = explode(">", $key);
			foreach($keyList as $keyName) {

				$obj = $this->getDBKey($obj, $keyName);
			}

			return $obj;
		}
		else {

			return $this->getDBKey($this->getDBObj(), $key);
		}
	}

	function setDBObj($dbObj) {

		$this->dbObj = $dbObj;
	}

	function getDBObj() {

		return $this->dbObj;
	}

	function getDBKey($obj, $keyName) {

		if(strcasecmp($keyName, "objectId")==0) {

			return $obj->getObjectId();
		}
		else if(strcasecmp($keyName, "updatedAt")==0) {

			return $obj->getUpdatedAt();
		}
		else {

			return $obj->get($keyName);
		}
	}

	function fetchOrderByOrderId($orderId) {

		$this->set("orderId", $orderId);

		$objOrder = parseExecuteQuery(["user" => $this->getDBObjUser(), "objectId" => $orderId], "Order", "", "", array("retailer", "retailer.location", "retailer.retailerType", "deliveryLocation", "coupon", "user", "flightTrip", "flightTrip.flight"), 1);

		$this->setDBObj($objOrder);
		$this->setOrderId();
	}

	function fetchOpenOrderByRetailerId($retailerId) {

		$this->set("retailerId", $retailerId);

        $obj = new ParseQuery("Retailers");
		$retailerObj = parseSetupQueryParams(["uniqueId" => $retailerId], $odj);

		$objOrder = parseExecuteQuery(["user" => $this->getDBObjUser(), "status" => listStatusesForCart(), "__MATCHESQUERY__retailer" => $retailerObj], "Order", "", "", array("retailer", "retailer.location", "retailer.retailerType", "deliveryLocation", "coupon", "user", "flightTrip", "flightTrip.flight"), 1);

		$this->setDBObj($objOrder);
		$this->setOrderId();
	}

	function fetchOpenOrderByOrderId($orderId) {

		$objOrder = parseExecuteQuery(["user" => $this->getDBObjUser(), "status" => listStatusesForCart(), "objectId" => $orderId], "Order", "", "", array("retailer", "retailer.location", "retailer.retailerType", "deliveryLocation", "coupon", "user", "flightTrip", "flightTrip.flight"), 1);

		$this->setDBObj($objOrder);
		$this->setOrderId();
	}

	function fetchOpenOrder() {

		$objOrder = parseExecuteQuery(["user" => $this->getDBObjUser(), "status" => listStatusesForCart()], "Order", "", "", array("retailer", "retailer.location", "retailer.retailerType", "deliveryLocation", "coupon", "user", "flightTrip", "flightTrip.flight"), 1);

		$this->setDBObj($objOrder);
		$this->setOrderId();
	}

	function fetchOrderModifiers() {

		$objOrderModifiers = parseExecuteQuery(["order" => $this->getDBObj()], "OrderModifiers");

        foreach($objOrderModifiers as $modifier) {

        	$obj = new OrderModifier($modifier->getObjectId());
        	$obj->setDBObj($modifier);
			$this->addOrderModifier($obj);
        }
	}

	function fetchOrderStatuses($statusList) {

        $objOrderStatuses = parseExecuteQuery(["order" => $this->getDBObj(), "status" => $statusList], "OrderStatus", "updatedAt");

        foreach($objOrderStatuses as $orderStatus) {

        	$obj = new OrderStatus($orderStatus->getObjectId());
        	$obj->setDBObj($orderStatus);
			$this->addOrderStatus($obj);
        }
	}

	function fetchOrderModifiersCountFromDB($orderId) {

		$obj = new ParseQuery("Order");
		$orderObj = parseSetupQueryParams(["objectId" => $orderId], $obj);
		$orderModifiersCount = parseExecuteQuery(["__MATCHESQUERY__order" => $orderObj], "OrderModifiers", "", "", [], 1, false, [], 'count');

		return $orderModifiersCount;
	}

	function fetchRetailerPOSConfig() {

		$objPOSConfig = parseExecuteQuery(["retailer" => $this->getRetailer()], "RetailerPOSConfig", "", "", [], 1);

		$this->retailerPOSConfigDBObj = $objPOSConfig;
	}

	function generateNewOrderId() {

	    // Fetch current sequence number
	    $orderSequenceObject = parseExecuteQuery(['keyName' => 'order'], "Sequences", "", "", [], 1);

	    $randomNumber = mt_rand(1, 15);

	    for($i=0;$i<=$randomNumber;$i++) {

		    // Increment and save
		    $orderSequenceObject->increment('sequenceNumber');
	    }

	    $orderSequenceObject->save();

	    // New Order Id is the saved id
	    $orderSequenceId = $orderSequenceObject->get('sequenceNumber');

	    return $orderSequenceId;
	}

	function createNewOrderInDB($retailerId) {

	    // Fetch Retailer object
	    $retailer = parseExecuteQuery(array("uniqueId" => $retailerId, "isActive" => true), "Retailers", "", "", [], 1);

	    if(count_like_php5($retailer) == 0) {

			throw new Exception("Order Creation failed as no active retailer found RetailerId = " . $retailerId);
	    }

	    // Generate unique order id
		$orderSequenceId = $this->generateNewOrderId();

	    try {

		    $createOrder = new ParseObject("Order");
		    $createOrder->set("status", 1);
		    $createOrder->set("user", $this->getDBObjUser());
		    $createOrder->set("retailer", $retailer);
		    $createOrder->set("interimOrderStatus", -1);
		    $createOrder->set("orderSequenceId", $orderSequenceId);

	        $createOrder->save();
	    }
	    catch (Exception $ex) {

			throw new Exception("Order Creation failed = " . $ex->getMessage());
	    }

	    $this->setDBObj($createOrder);

	    // Add status row
	    addOrderStatus($createOrder, ' ');
	}

	function applyTipInDB($tipPct, $tipDollar) {

		// Fetch the POS Config object if not already pulled
		if(!is_object($this->retailerPOSConfigDBObj)) {
			
			$this->fetchRetailerPOSConfig();
		}

		// Check if Retailer allows applying tips
		$areTipsAllowed = $this->retailerPOSConfigDBObj->get('areTipsAllowed');
		if(!$areTipsAllowed) {
			
			throw new Exception("Tips not allowed for Retailer");
		}

		// Save Tip PCT
		$objDB = $this->getDBObj();
		$objDB->set('tipPct', $tipPct);

		try {

			$objDB->save();
		}
		catch (Exception $ex) {

			throw new Exception("Tip apply failed = " . $ex->getMessage() . " - OrderId = " . $this->getObjectId());
		}

		// Update Order object
		$this->setDBObj($objDB);
	}

	function removeCouponInDB() {

		// Remove coupon reference
		$objDB = $this->getDBObj();
		$objDB->set("coupon", null);

		try {

			$objDB->save();
		}
		catch (Exception $ex) {

			throw new Exception("Coupon remove failed = " . $ex->getMessage() . " - OrderId = " . $this->getObjectId());
		}

		// Update Order object
		$this->setDBObj($objDB);
	}

	function applyCouponInDB($couponObj) {

		// Apply object
		// $objDB = new ParseObject("Order", $this->getObjectId());
		$objDB = $this->getDBObj();
		$objDB->set("coupon", $couponObj);

		try {

			$objDB->save();
		}
		catch (Exception $ex) {

			throw new Exception("Coupon apply failed = " . $ex->getMessage() . " - OrderId = " . $this->getObjectId());
		}

		// Update Order object
		$this->setDBObj($objDB);
	}

	function applyAirportEmployeeDiscountInDB() {

		// Apply object
		// $objDB = new ParseObject("Order", $this->getObjectId());
		$objDB = $this->getDBObj();
		$objDB->set("airportEmployeeDiscount", true);

		try {

			$objDB->save();
		}
		catch (Exception $ex) {

			throw new Exception("Airport Employee Flag apply failed = " . $ex->getMessage() . " - OrderId = " . $this->getObjectId());
		}

		// Update Order object
		$this->setDBObj($objDB);
	}

	function removeAirportEmployeeDiscountInDB() {

		// Apply object
		// $objDB = new ParseObject("Order", $this->getObjectId());
		$objDB = $this->getDBObj();
		$objDB->set("airportEmployeeDiscount", false);

		try {

			$objDB->save();
		}
		catch (Exception $ex) {

			throw new Exception("Airport Employee Flag remove failed = " . $ex->getMessage() . " - OrderId = " . $this->getObjectId());
		}

		// Update Order object
		$this->setDBObj($objDB);
	}

	function applyMilitaryDiscountInDB() {

		// Apply object
		// $objDB = new ParseObject("Order", $this->getObjectId());
		$objDB = $this->getDBObj();
		$objDB->set("militaryDiscount", true);

		try {

			$objDB->save();
		}
		catch (Exception $ex) {

			throw new Exception("Airport Employee Flag apply failed = " . $ex->getMessage() . " - OrderId = " . $this->getObjectId());
		}

		// Update Order object
		$this->setDBObj($objDB);
	}

	function removeMilitaryDiscountInDB() {

		// Apply object
		// $objDB = new ParseObject("Order", $this->getObjectId());
		$objDB = $this->getDBObj();
		$objDB->set("militaryDiscount", false);

		try {

			$objDB->save();
		}
		catch (Exception $ex) {

			throw new Exception("Airport Employee Flag remove failed = " . $ex->getMessage() . " - OrderId = " . $this->getObjectId());
		}

		// Update Order object
		$this->setDBObj($objDB);
	}

	function closeOrderInDB() {

        $status = $this->getStatus();

	    // If Order is found, set its status as Abandoned
	    if(in_array($status, listStatusesForCart())) {

			$this->changeStatusToAbandonInDB();

	        return true;
	    }
	    else {

	        return false;
	    }

	    $this->setDBObj($createOrder);

	    // Add status row
	    addOrderStatus($createOrder, ' ');
	}

	function changeStatusToAbandonInDB() {

		$objOrder = $this->getDBObj();
	    $objOrder->set("status", 100);
        $objOrder->save();

        $this->setDBObj($objOrder);

        // Add Status row
	    addOrderStatus($objOrder);
	}
}

class OrderModifier {

	private $orderModifierId; // object Id
	private $dbObj;

	function __construct($orderModifierId) {

		$this->set("orderModifierId", $orderModifierId);
	}

	function set($key, $value) {

		$this->$key = replaceSpecialChars($value);
	}

	function get($key) {

		return $this->$key;
	}

	function setDBObj($dbObj) {

		$this->set("dbObj", $dbObj);
	}

	function getDBObj() {

		return $this->get("dbObj");
	}

	function getFromDB($key) {

		if(!is_object($this->getDBObj())) {

			return "";
		}

		// Nested objct requested
		if(preg_match("/\>/si", $key)) {

			$obj = $this->getDBObj();

			$keyList = explode(">", $key);
			foreach($keyList as $keyName) {

				$obj = $this->getDBKey($obj, $keyName);
			}

			return $obj;
		}
		else {

			return $this->getDBKey($this->getDBObj(), $key);
		}
	}

	function getDBKey($obj, $keyName) {

		if(strcasecmp($keyName, "objectId")==0) {

			return $obj->getObjectId();
		}
		else if(strcasecmp($keyName, "updatedAt")==0) {

			return $obj->getUpdatedAt();
		}
		else {

			return $obj->get($keyName);
		}
	}

	function fetchOrderModifier() {

	}

	function getOrderModifierId() {

		$this->get("orderModifierId");
	}

	function getItemQuantity() {

		return $this->getFromDB("itemQuantity");
	}

	function getObjectId() {

		return $this->getFromDB("objectId");
	}

	function createNewOrderModifierInDB($order, $retailerItem, $itemQuantity, $itemComment, $modifierOptions, $taxes) {

		$objDB = new ParseObject("OrderModifiers");

		$objDB->set("order", $order->getDBObj());
		$objDB->set("retailerItem", $retailerItem->getDBObj());
		$objDB->set("itemQuantity", $itemQuantity);
		$objDB->set("itemComment", $itemComment);
		$objDB->set("taxes", $taxes);

		if(is_array($modifierOptions)) {

			$objDB->set("modifierOptions", json_encode($modifierOptions));
		}

		try {

			$objDB->save();
		}
		catch (Exception $ex) {

			throw new Exception("Item add to cart failed = " . $ex->getMessage() . " - ItemId = " . $retailerItem->getUniqueId());
		}

		$this->setDBObj($objDB);
		return $this->getObjectId();
	}

	function updateOrderModifierInDB($itemQuantity, $itemComment, $modifierOptions, $taxes) {

		$objDB = new ParseObject("OrderModifiers", $this->getObjectId());

		$objDB->set("itemQuantity", $itemQuantity);
		$objDB->set("itemComment", $itemComment);
		$objDB->set("taxes", $taxes);

		if(is_array($modifierOptions)) {
			
			$objDB->set("modifierOptions", json_encode($modifierOptions));
		}

		try {

			$objDB->save();
		}
		catch (Exception $ex) {

			throw new Exception("Item update to cart failed = " . $ex->getMessage() . " - OrderModifierId = " . $this->getOrderModifierId());
		}

		$this->setDBObj($objDB);
		return $this->getObjectId();
	}

	function deleteOrderModifierInDB() {

		$objDB = new ParseObject("OrderModifiers", $this->getObjectId());

		try {

			$objDB->destroy();
			$objDB->save();
		}
		catch (Exception $ex) {

			throw new Exception("Item deletion from cart failed = " . $ex->getMessage() . " - OrderModifierId = " . $this->getOrderModifierId());
		}

		return "";
	}
}

class OrderStatus {

	private $orderStatusId;
	private $dbObj;

	function __construct($orderStatusId) {

		$this->set("orderStatusId", $orderStatusId);
	}

	function set($key, $value) {

		$this->$key = replaceSpecialChars($value);
	}

	function get($key) {

		return $this->$key;
	}

	function setDBObj($dbObj) {

		$this->set("dbObj", $dbObj);
	}

	function getDBObj() {

		return $this->get("dbObj");
	}

	function getFromDB($key) {

		if(!is_object($this->getDBObj())) {

			return "";
		}

		// Nested objct requested
		if(preg_match("/\>/si", $key)) {

			$obj = $this->getDBObj();

			$keyList = explode(">", $key);
			foreach($keyList as $keyName) {

				$obj = $this->getDBKey($obj, $keyName);
			}

			return $obj;
		}
		else {

			return $this->getDBKey($this->getDBObj(), $key);
		}
	}

	function getDBKey($obj, $keyName) {

		if(strcasecmp($keyName, "objectId")==0) {

			return $obj->getObjectId();
		}
		else if(strcasecmp($keyName, "updatedAt")==0) {

			return $obj->getUpdatedAt();
		}
		else if($obj->has($keyName)) {

			return $obj->get($keyName);
		}
		else {

			return "";
		}
	}

	function fetchOrderStatus() {

	}

	function getOrderStatusId() {

		$this->get("orderStatusId");
	}

	function getUniqueId() {

		return $this->getFromDB("uniqueId");
	}

	function getObjectId() {

		return $this->getFromDB("objectId");
	}

	function getStatus() {

		return $this->getFromDB("status");
	}

	function getStatusDelivery() {

		return $this->getFromDB("statusDelivery");
	}

	function getUpdatedAt() {

		return $this->getFromDB("updatedAt");
	}

	function getManualUpdatedAt() {

		return $this->getFromDB("manualTimestamp");
	}
}

?>
