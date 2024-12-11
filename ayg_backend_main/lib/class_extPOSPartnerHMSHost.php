<?php

use Httpful\Request;
use Parse\ParseClient;
use Parse\ParseQuery;
use Parse\ParseObject;
use Parse\ParseUser;
use Parse\ParseFile;

	$GLOBALS['__menuLoaderConfig']['hmshost']['itemCategoriesNotAllowedThruSecurity'] = ["Beverages", "Bottled Beverages", "Irresistible Desserts", "Non- Alcoholic Beverages", "Non-Alcoholic Beverages", "Coffee Bar", "Happy endings"];
	$GLOBALS['__menuLoaderConfig']['hmshost']['unallowedItemsThruSecurityKeywords'] = ["Yogurt", "Coffee", "Iced", "Tea", "Juice Bar", "Mike Bar", "Soda Bottle", "Ice", "Smoothie", "Soup", "creme", "Latte"];
	$GLOBALS['__menuLoaderConfig']['hmshost']['unallowedItems'] = ["Bottle of Bud Light", "Bottle of Michelob Ultra", "Yogurt Parfait"];

    $GLOBALS['__menuLoaderConfig']['hmshost']['forceNewVersionForItem'] = ["itemPrice", "itemPOSName", "itemDisplayDescription"];
    $GLOBALS['__menuLoaderConfig']['hmshost']['forceNewVersionForItemTimes'] = ["restrictOrderTimes"];
    $GLOBALS['__menuLoaderConfig']['hmshost']['forceNewVersionForModifier'] = ["modifierPOSName", "maxQuantity", "minQuantity", "isRequired"];
    $GLOBALS['__menuLoaderConfig']['hmshost']['forceNewVersionForModifierOption'] = ["optionPOSName", "pricePerUnit"];

    // These columns will be updated
    $GLOBALS['__menuLoaderConfig']['hmshost']['columnsToUpdateForItem'] = ["itemPrice", "itemPOSName", "itemDisplayDescription", "itemCategoryName", "allowedThruSecurity", "itemDisplayName"];
    $GLOBALS['__menuLoaderConfig']['hmshost']['columnsToUpdateForItemTimes'] = ["restrictOrderTimes"];
    $GLOBALS['__menuLoaderConfig']['hmshost']['columnsToUpdateForModifiers'] = ["modifierPOSName", "minQuantity", "maxQuantity", "isRequired"];
    $GLOBALS['__menuLoaderConfig']['hmshost']['columnsToUpdateForModifierOptions'] = ["optionPOSName", "pricePerUnit", "optionDisplaySequence"];

class HMSHost {

	protected $connection = [];
	protected $connection_url;
	protected $connection_subscription_key;
	protected $action_subscription_key;
	protected $session_id;
	protected $property_id;
	// JMD
	protected $revenue_center_id;
	protected $uniqueRetailerId;
	protected $menu;
	protected $menuItemImages = [];
	protected $menuItemModifiers = [];
	protected $partner = 'hmshost';
	protected $retailerInfo = [];

	function __construct($property_id, $revenue_center_id, $uniqueRetailerId='', $connectType='ping') {

		$this->connection_url = $GLOBALS['env_HMSHostURL'];
		$this->connection_subscription_key = $GLOBALS['env_HMSHostPassKey_SubscriptionKey_Menu'];

		if(strcasecmp($connectType, 'menu')==0) {

			// Integration keys
		    $this->connection = [
		    	"ClientID" => $GLOBALS['env_HMSHostClientID'],
				"Username" => $GLOBALS['env_HMSHostUsername_Menu'],
				"PassKey" => $GLOBALS['env_HMSHostPassKey_Menu']
			];

			$this->action_subscription_key = $GLOBALS['env_HMSHostPassKey_SubscriptionKey_Menu'];
		}
		else if(strcasecmp($connectType, 'ping')==0) {

		    $this->connection = [
		    	"ClientID" => $GLOBALS['env_HMSHostClientID'],
				"Username" => $GLOBALS['env_HMSHostUsername_Ping'],
				"PassKey" => $GLOBALS['env_HMSHostPassKey_Ping']
			];

			$this->action_subscription_key = $GLOBALS['env_HMSHostPassKey_SubscriptionKey_Ping'];
		}
		else if(strcasecmp($connectType, 'order')==0) {

		    $this->connection = [
		    	"ClientID" => $GLOBALS['env_HMSHostClientID'],
				"Username" => $GLOBALS['env_HMSHostUsername_Order'],
				"PassKey" => $GLOBALS['env_HMSHostPassKey_Order']
			];

			$this->action_subscription_key = $GLOBALS['env_HMSHostPassKey_SubscriptionKey_Order'];
		}

		$this->session_start();

		$this->setPropertyId($property_id);
		$this->setRevenueCenterId($revenue_center_id);
		$this->setUniqueRetailerId($uniqueRetailerId);
	}

	function setPropertyId($property_id) {

		$this->property_id = $property_id;
	}

	function setRevenueCenterId($revenue_center_id) {

		$this->revenue_center_id = $revenue_center_id;
	}

	function setUniqueRetailerId($uniqueRetailerId) {

		$this->uniqueRetailerId = $uniqueRetailerId;
	}

	function session_start() {

	    $response = "";

		// Session Integration keys
		$connection = [
			"ClientID" => $GLOBALS['env_HMSHostClientID'],
			"Username" => $GLOBALS['env_HMSHostUsername_Ping'],
			"PassKey" => $GLOBALS['env_HMSHostPassKey_Ping']
		];

		$connection_subscription_key = $this->connection_subscription_key;

	    try {

	        $response = Request::post($this->connection_url . '/catalog' . '/session/begin')
	                ->sendsJson()
	                ->addHeader('Ocp-Apim-Subscription-Key', $connection_subscription_key)
	                ->body(json_encode($connection))
	                ->timeout(30)
	                ->send();
	    }
	    catch (Exception $ex) {

	        json_error("AS_1081", "", "Failed to begin session with HMSHost - " . json_encode($this->connection), 3, 1);
	        throw new Exception($ex->getMessage());
	    }

	    if(!isset($response->body)
	    	|| !isset($response->body->Status)
			|| strcasecmp($response->body->Status, "SUCCESS")!=0
			|| empty($response->body->SessionID)) {

	        json_error("AS_1082", "", "Failed to begin session with HMSHost - " . json_encode($response), 3, 1);
	        throw new Exception("Failed to beging session. " . json_encode($response->body));
	    }

	    $this->session_id = $response->body->SessionID;
	}

	function session_end() {

	    $response = "";

	    try {

	        $response = Request::post($this->connection_url . '/catalog' . '/session/end')
	                ->sendsJson()
	                ->addHeader('Ocp-Apim-Subscription-Key', $this->connection_subscription_key)
	                ->body(json_encode(array_merge($this->connection, ['SessionID' => $this->session_id])))
	                ->timeout(30)
	                ->send();
	    }
	    catch (Exception $ex) {

	        json_error("AS_1083", "", "Failed to end session with HMSHost - " . json_encode($this->connection), 3, 1);
	        return false;
	    }

	    if(!isset($response->body)
	    	|| !isset($response->body->Status)
			|| strcasecmp($response->body->Status, "SUCCESS")!=0) {

	        json_error("AS_1085", "", "Failed to end session with HMSHost - " . json_encode($response), 3, 1);
	        return false;
	    }

	    return $response->body->SessionID;
	}

	function ping_retailer() {

	    $response = "";

	    try {

	        $response = Request::post($this->connection_url . '/diagnostics' . '/services/status')
	                ->sendsJson()
	                ->addHeader('Ocp-Apim-Subscription-Key', $this->action_subscription_key)
	                ->body(json_encode(array_merge($this->connection, ['SessionID' => $this->session_id, 'PropertyID' => $this->property_id, 'RevenueCenterID' => $this->revenue_center_id])))
	                ->timeout(30)
	                ->send();
	    }
	    catch (Exception $ex) {

	        json_error("AS_1088", "", "Failed to ping retailer - " . $this->revenue_center_id . " - " . $ex->getMessage(), 3, 1);
	        return [false, $ex->getMessage()];
	    }

	    $errorMsg = "";
	    if(isset($response->body->message)) {

	    	$errorMsg .= $response->body->message;
	    }

	    if(isset($response->body->Error)) {

	    	$errorMsg .= json_encode($response->body->Error);
	    }

	    if(!isset($response->body)
	    	|| !isset($response->body->Status)
			|| strcasecmp($response->body->Status, "SUCCESS")!=0
			|| empty($response->body->SessionID)
			|| !isset($response->body->Active)) {

	        json_error("AS_1089", "", "Failed to ping retailer - " . $this->revenue_center_id . " - " . $errorMsg, 3, 1);
	        return [false, $errorMsg];
	    }

	    return [$response->body->Active, $errorMsg];
	}

	function format_cart($cartInternal, $retailerTotals, $tenderType, $forStep=1) {

		// $cartFormatted["PropertyId"] = $this->property_id;
		// $cartFormatted["RevenueCenterID"] = $this->revenue_center_id;
		$cartFormatted["OrderTypeIdRef"] = 2;
		$cartFormatted["GuestCheckRef"] = "";
		$cartFormatted["TableRef"] = "0";

		// Add payment info, only needed when ordering
		if($forStep == 2) {

			$cartFormatted["payment"]["authCode"] = $cartInternal["internal"]["orderIdDisplay"];
			$cartFormatted["payment"]["cardNumber"] = $cartInternal["internal"]["orderIdDisplay"];
			$cartFormatted["payment"]["tenderType"] = $tenderType;
			$cartFormatted["payment"]["amount"] = $retailerTotals["Total"]/100;
		}

		foreach($cartInternal["items"] as $i => $item) {

			$cartFormatted["cart"]["items"][$i]["id"] = $item["extItemId"];
			$cartFormatted["cart"]["items"][$i]["price"] = dollar_format_float_with_decimals($item["itemPrice"]);
			$cartFormatted["cart"]["items"][$i]["Quantity"] = $item["itemQuantity"];
			$cartFormatted["cart"]["items"][$i]["freetext"] = $item["itemComment"];

			if(isset($item["options"])) {

				foreach($item["options"] as $o => $option) {

					$cartFormatted["cart"]["items"][$i]["Modifiers"][$o]["Id"] = $option["extOptionId"];
					$cartFormatted["cart"]["items"][$i]["Modifiers"][$o]["Quantity"] = $option["optionQuantity"];
					$cartFormatted["cart"]["items"][$i]["Modifiers"][$o]["Price"] = dollar_format_float_with_decimals($option["pricePerUnit"]);
					$cartFormatted["cart"]["items"][$i]["Modifiers"][$o]["FreeText"] = "";
				}
			}
		}

		return $cartFormatted;
	}

	function push_cart_for_totals($orderId, $totalForRetailer, $cartFormatted) {

	    $response = "";
	    try {

	        $response = Request::post($this->connection_url . '/order' . '/fullcart/totals')
	                ->sendsJson()
	                ->addHeader('Ocp-Apim-Subscription-Key', $this->action_subscription_key)
	                ->body(json_encode(array_merge($this->connection, ['SessionID' => $this->session_id, 'PropertyID' => $this->property_id, 'RevenueCenterID' => $this->revenue_center_id], $cartFormatted)))
	                ->timeout(30)
	                ->send();
	    }
	    catch (Exception $ex) {

	        json_error("AS_1088", "", "Failed to get cart totals - " . $orderId . " - " . $ex->getMessage(), 1, 1);
	        throw new Exception($ex->getMessage());
	    }

	    if(!isset($response->body)
	    	|| !isset($response->body->Status)
			|| strcasecmp($response->body->Status, "SUCCESS")!=0
			|| empty($response->body->SessionID)) {

	        json_error("AS_1089", "", "Failed to get cart totals - " . $orderId, 1, 1);
	        throw new Exception("No Cart found. " . json_encode($response->body));
	    }

	    // Match totals
	    if(!isset($response->body->AmountDueTotal)
	    	|| $response->body->AmountDueTotal != dollar_format_float_with_decimals($totalForRetailer)) {

	        json_error("AS_1090", "", "Total doesn't match - " . $orderId, 1, 1);
	        throw new Exception("Total doesn't match. " . dollar_format_float_with_decimals($totalForRetailer) . " <> " . json_encode($response->body));
	    }

	    return true;
	}

	function get_taxes_and_subtotal($orderId, $cartInternal) {

		$cartFormatted = $this->format_cart($cartInternal, [], 0, 1);

	    $response = "";

	    try {

	        $response = Request::post($this->connection_url . '/order' . '/fullcart/totals')
	                ->sendsJson()
	                ->addHeader('Ocp-Apim-Subscription-Key', $this->action_subscription_key)
	                ->body(json_encode(array_merge($this->connection, ['SessionID' => $this->session_id, 'PropertyID' => $this->property_id, 'RevenueCenterID' => $this->revenue_center_id], $cartFormatted)))
	                ->timeout(30)
	                ->send();
	    }
	    catch (Exception $ex) {

	        json_error("AS_1088", "", "Failed to get cart totals - " . $orderId . " - " . $ex->getMessage(), 1, 1);
	        throw new Exception($ex->getMessage());
	    }

	    if(!isset($response->body)
	    	|| !isset($response->body->Status)
			|| strcasecmp($response->body->Status, "SUCCESS")!=0
	    	|| !isset($response->body->TotalTax)
	    	|| !isset($response->body->Subtotal)
			|| empty($response->body->SessionID)) {

	        json_error("AS_1089", "", "Failed to get cart totals - " . $orderId, 1, 1);
	        throw new Exception("No Cart found. " . json_encode($response->body));
	    }

	    return [intval(trim(floatval(trim($response->body->Subtotal)*100))), intval(trim(floatval(trim($response->body->TotalTax)*100)))];
	}

	function push_cart_for_submission($orderId, $cartFormatted) {

	    $response = "";

	    try {

	        $response = Request::post($this->connection_url . '/order' . '/fullcart/submit')
	                ->sendsJson()
	                ->addHeader('Ocp-Apim-Subscription-Key', $this->action_subscription_key)
	                ->body(json_encode(array_merge($this->connection, ['SessionID' => $this->session_id, 'PropertyID' => $this->property_id, 'RevenueCenterID' => $this->revenue_center_id], $cartFormatted)))
	                ->timeout(30)
	                ->send();
	    }
	    catch (Exception $ex) {

	        json_error("AS_1088", "", "Failed to submit cart - " . $orderId . " - " . $ex->getMessage(), 1, 1);
	        throw new Exception($ex->getMessage());
	    }

	    if(!isset($response->body)
	    	|| !isset($response->body->Status)
			|| strcasecmp($response->body->Status, "SUCCESS")!=0
	    	|| !isset($response->body->Order)
	    	|| empty($response->body->Order->TransactionGuid)
			|| empty($response->body->SessionID)) {

	    	$reason = "";
	    	if(isset($response->body->Error->Reason)) {

	    		$reason = $response->body->Error->Reason;
	    	}

	        json_error("AS_1089", "", "Failed to submit cart - " . $reason . ' - ' . $orderId, 1, 1);
	        throw new Exception("Order submission failed. " . $reason . " - " . json_encode($response->body));
	    }

	    $cartId = $transactionGuid = $transactionNumber = "";
	    if(isset($response->body->Cart->CartID)) {

	    	$cartId = $response->body->Cart->CartID;
	    }
	    if(isset($response->body->Order->TransactionGuid)) {

	    	$transactionGuid = $response->body->Order->TransactionGuid;
	    }
	    if(isset($response->body->Order->TransactionNumber)) {

	    	$transactionNumber = $response->body->Order->TransactionNumber;
	    }

	    return $cartId . ' ~ ' . $transactionGuid . ' ~ ' . $transactionNumber;
	}

	function confirm_items_are_valid($orderId, $cartFormatted) {

	    $response = "";
	    try {

	        $response = Request::post($this->connection_url . '/order' . '/fullcart/totals')
	                ->sendsJson()
	                ->addHeader('Ocp-Apim-Subscription-Key', $this->action_subscription_key)
	                ->body(json_encode(array_merge($this->connection, ['SessionID' => $this->session_id, 'PropertyID' => $this->property_id, 'RevenueCenterID' => $this->revenue_center_id], $cartFormatted)))
	                ->timeout(30)
	                ->send();
	    }
	    catch (Exception $ex) {

	        json_error("AS_1088", "", "Failed to get cart totals during add to cart - " . $orderId . " - " . $ex->getMessage(), 1, 1);
	        throw new Exception($ex->getMessage());
	    }

		$itemPrices = $invalidItems = [];
	    if(isset($response->body)) {

			// Check for invalid items
			if(isset($response->body->Cart->InvalidItems)
				&& count_like_php5($response->body->Cart->InvalidItems) > 0) {

				foreach($response->body->Cart->InvalidItems as $item) {
					
					$invalidItems[$item->ID] = "Invalid Item";
					$itemPrices[$item->ID] = 0;
				}
			}

			// Availability
			if(isset($response->body->Cart->Items)
				&& count_like_php5($response->body->Cart->Items) > 0) {

				foreach($response->body->Cart->Items as $item) {

					if(isset($item->Price)) {

						$itemPrices[$item->ID] = trim($item->Price)*100;
					}
					
					if(isset($item->IsOutOfStock) && $item->IsOutOfStock == true) {

						$invalidItems[$item->ID] = "Item Out of Stock";
					}

					else if(isset($item->Active) && $item->Active == false) {

						$invalidItems[$item->ID] = "Item Not valid (not active)";
					}
				}
			}
	    }
		else {

	        json_error("AS_1089", "", "Failed to get cart totals - " . $orderId, 1, 1);
	        throw new Exception(json_encode($response->body));
		}

		$flag = true;
		$totalAmountDue = $totalTax = 0;
		if(count_like_php5($invalidItems) > 0) {
			
			$flag = false;
		}
		else if(isset($response->body->TotalTax)) {

			$totalTax = trim($response->body->TotalTax)*100;
		}
		
		if(isset($response->body->AmountDueTotal)) {

			$totalAmountDue = trim($response->body->AmountDueTotal)*100;
		}
		
		if(isset($response->body->Subtotal)) {

			$subtotalDue = trim($response->body->Subtotal)*100;
		}

	    return [$flag, $invalidItems, $itemPrices, intval_external($totalTax), intval_external($totalAmountDue), intval_external($subtotalDue)];
	}

	function getListOfRetailersByAirport() {

	    $response = "";

	    try {

	        $response = Request::post($this->connection_url . '/catalog' . '/revenuecenters/byproperty')
	                ->sendsJson()
	                ->addHeader('Ocp-Apim-Subscription-Key', $this->action_subscription_key)
	                ->body(json_encode(array_merge($this->connection, ['SessionID' => $this->session_id, 'PropertyID' => $this->property_id])))
	                ->timeout(30)
	                ->send();
	    }
	    catch (Exception $ex) {

	        json_error("AS_1088", "", "Failed to get revenue center list " . " - " . $ex->getMessage(), 1, 1);
	        throw new Exception($ex->getMessage());
	    }

	    if(!isset($response->body)
	    	|| !isset($response->body->Status)
			|| strcasecmp($response->body->Status, "SUCCESS")!=0
			|| empty($response->body->SessionID)) {

	        json_error("AS_1089", "", "Failed to get revenue center list", 1, 1);
	        throw new Exception("No retailers found. " . json_encode($response->body));
	    }

	    $responseArray = [];
	    foreach($response->body->Items as $retailer) {

	    	$responseArray[$retailer->ID]["retailerName"] = $retailer->Name;
	    	$responseArray[$retailer->ID]["isActive"] = $retailer->Active == true ? "Y" : "N";
	    }

	    return $responseArray;
	}

	function menu_modifiers_pull() {

	    $response = "";

	    try {

	        $response = Request::post($this->connection_url . '/catalog' . '/menuitems/modifiergroups/byrevenuecenter')
	                ->sendsJson()
	                ->addHeader('Ocp-Apim-Subscription-Key', $this->action_subscription_key)
	                ->body(json_encode(array_merge($this->connection, ['SessionID' => $this->session_id, 'PropertyID' => $this->property_id, 'RevenueCenterID' => $this->revenue_center_id])))
	                ->timeout(30)
	                ->send();
	    }
	    catch (Exception $ex) {

	        json_error("AS_1086", "", "Failed to extract of menu items modifier groups - " . json_encode($this->connection), 3, 1);
	        return false;
	    }

	    if(!isset($response->body)
	    	|| !isset($response->body->Items)
	    	|| !isset($response->body->Status)
			|| strcasecmp($response->body->Status, "SUCCESS")!=0
			|| empty($response->body->SessionID)) {

	        json_error("AS_1087", "", "Failed to extract of menu items - " . json_encode($response), 3, 1);
	        return false;
	    }

	    foreach($response->body->Items as $item) {

	    	if(isset($item->ImageUrl) && !empty($item->ImageUrl)) {

		    	$this->menuItemImages[$item->ID] = $item->ImageUrl;
	    	}

			// JMD
	    	if(isset($item->ModifierGroups) && count_like_php5($item->ModifierGroups) > 0) {

	    		foreach($item->ModifierGroups as $modifier) {

	    			$modifierIdHash = md5($modifier->Name);

	    			if(!isset($this->menuItemModifiers[$item->ID][$modifierIdHash])) {

	    				$this->menuItemModifiers[$item->ID][$modifierIdHash]["id"] = $modifier->Id;
	    			}

	    			if(isset($modifier->Sequence)) {

				    	$this->menuItemModifiers[$item->ID][$modifierIdHash]["sequence"] = intval($modifier->Sequence);
	    			}

				    // JMD
			    	if(isset($modifier->Modifiers) && !empty($modifier->Modifiers)
			    		&& count_like_php5($modifier->Modifiers) > 0) {

		    			foreach($modifier->Modifiers as $option) {

					    	$this->menuItemModifiers[$item->ID][$modifierIdHash][$option->ID]["sequence"] = intval($option->Sequence);
		    			}
		    		}
	    		}
	    	}
	    }
	}

	function menu_pull() {

	    $response = "";

	    try {

	        $response = Request::post($this->connection_url . '/catalog' . '/menuitems/byrevenuecenter')
	                ->sendsJson()
	                ->addHeader('Ocp-Apim-Subscription-Key', $this->action_subscription_key)
	                ->body(json_encode(array_merge($this->connection, ['SessionID' => $this->session_id, 'PropertyID' => $this->property_id, 'RevenueCenterID' => $this->revenue_center_id])))
	                ->timeout(30)
	                ->send();
	    }
	    catch (Exception $ex) {

	        json_error("AS_1086", "", "Failed to extract of menu items - " . json_encode($this->connection), 3, 1);
	        return false;
	    }

	    if(!isset($response->body)
	    	|| !isset($response->body->Status)
			|| strcasecmp($response->body->Status, "SUCCESS")!=0
			|| empty($response->body->SessionID)) {

	        json_error("AS_1087", "", "Failed to extract of menu items - " . json_encode($response), 3, 1);
	        return false;
	    }

	    // JMD
	    $this->menu = $response->body;
	    // JMD
	}

	// JMD
	function menu_extract($itemCategoriesNotAllowedThruSecurity, $unallowedItems, $unallowedItemsThruSecurityKeywords) {

		$itemRows = [];
		$itemsSkipped = [];
		foreach($this->menu->Items as $item) {

			// Item information
			$itemCategoryName = $this->get_category($item);			
			$itemPOSName = $this->get_name($item);			
			$itemCalories = $this->get_calories($item);
			$itemDisplayDescription = $itemCalories . $this->get_description($item);
			$isActive = $this->get_is_active($item);
			$itemPrice = $this->get_price($item);
			$itemId = $this->get_id($item);
			$uniqueId = $this->get_unique_id($item, $this->uniqueRetailerId);
			$itemDisplaySequence = $this->get_display_sequence($item);

			// JMD
			$itemRows[$itemId]["itemId"] = $itemId;
			$itemRows[$itemId]["itemCategoryName"] = $itemCategoryName;
			$itemRows[$itemId]["itemPOSName"] = $itemPOSName;

			// Skip items with 0 price
			if($itemPrice <= 0) {

				$itemRows[$itemId]["isActive"] = "N";
				// JMD
				$itemsSkipped[$itemId][] = "Price set to 0";
			}
			// Keywords matched the name of unallowed items
			else if(in_array(str_replace('"', '', $itemRows[$itemId]["itemPOSName"]), $unallowedItems)) {

				// $itemRows[$itemId]["isActive"] = "N";
				$itemRows[$itemId]["isActive"] = $isActive;
				$itemsSkipped[$itemId][] = "Item name keyword match";
			}
			// JMD
			else {

				$itemRows[$itemId]["isActive"] = $isActive;
			}

			$itemRows[$itemId]["itemDisplayDescription"] = $itemDisplayDescription;
			$itemRows[$itemId]["itemPrice"] = $itemPrice;
			$itemRows[$itemId]["uniqueRetailerId"] = $this->uniqueRetailerId;
			$itemRows[$itemId]["uniqueId"] = $uniqueId;
			$itemRows[$itemId]["itemDisplaySequence"] = $itemDisplaySequence;
			$itemRows[$itemId]["itemImageURL"] = $this->get_item_image_name($item, $uniqueId);
			$itemRows[$itemId]["taxCategory"] = "";
			$itemRows[$itemId]["itemTags"] = "";
			$itemRows[$itemId]["itemDisplayName"] = "";

			if(in_array($itemRows[$itemId]["itemCategoryName"], $itemCategoriesNotAllowedThruSecurity)) {

				$itemRows[$itemId]["allowedThruSecurity"] = "N";
			}
			else {

				$itemRows[$itemId]["allowedThruSecurity"] = "Y";

				$itemNameToSearch = str_replace('"', '', $itemRows[$itemId]["itemPOSName"]);

				// Check if any of the keywords match items that can't be taken across security
				foreach($unallowedItemsThruSecurityKeywords as $keyword) {

					if(preg_match("/" . $keyword . "/si", $itemNameToSearch)) {

						$itemRows[$itemId]["allowedThruSecurity"] = "N";
						break;
					}
				}
			}

			$itemRows[$itemId]["priceLevelId"] = "";
			$itemRows[$itemId]["itemTags"] = "";

			// 86 the item
			$itemToBe86 = "N";
			if($this->get_is_86($item)) {

				$itemToBe86 = "Y";
			}

			$itemRows[$itemId]["86item"] = $itemToBe86;

			// Prep time
			$prepTime = $this->get_prep_time($item);
			$prepRestrictTimesGroup1 = $this->get_prep_time_restrictions($item, $prepTime);

			// Restrict times
			$restrictOrderTimes = $this->get_item_times($item);

			if(!is_bool($restrictOrderTimes)) {

				for($dayOfWeek=1;$dayOfWeek<=7;$dayOfWeek++) {

					$itemRows[$itemId]["__itemTimes"][$dayOfWeek]["uniqueRetailerItemId"] = $itemRows[$itemId]["uniqueId"];
					$itemRows[$itemId]["__itemTimes"][$dayOfWeek]["dayOfWeek"] = $dayOfWeek;
					$itemRows[$itemId]["__itemTimes"][$dayOfWeek]["restrictOrderTimes"] = $restrictOrderTimes;

					// Prep times file to be created
					// Needed if avg retailer prep time is lower than item's
					if($prepTime > 0) {

						$itemRows[$itemId]["__itemTimes"][$dayOfWeek]["prepRestrictTimesGroup1"] = $restrictOrderTimes;
						$itemRows[$itemId]["__itemTimes"][$dayOfWeek]["prepTimeCategoryIdGroup1"] = $prepTime;
					}
					else {

						$itemRows[$itemId]["__itemTimes"][$dayOfWeek]["prepRestrictTimesGroup1"] = "";
						$itemRows[$itemId]["__itemTimes"][$dayOfWeek]["prepTimeCategoryIdGroup1"] = "";
					}

					$itemRows[$itemId]["__itemTimes"][$dayOfWeek]["prepRestrictTimesGroup2"] = "";
					$itemRows[$itemId]["__itemTimes"][$dayOfWeek]["prepTimeCategoryIdGroup2"] = "";
					$itemRows[$itemId]["__itemTimes"][$dayOfWeek]["prepRestrictTimesGroup3"] = "";
					$itemRows[$itemId]["__itemTimes"][$dayOfWeek]["prepTimeCategoryIdGroup3"] = "";
					$itemRows[$itemId]["__itemTimes"][$dayOfWeek]["isActive"] = "Y";
				}
			}

			// Modifiers
			if(isset($item->Modifiers)
				&& count_like_php5($item->Modifiers) > 0) {

				$itemRows[$itemId]["__modifiers"] = $this->get_modifier_rows($item, $item->Modifiers);
			}
		}

		return [$itemRows, $itemsSkipped];
	}

	function menu_initial_load($itemRows, $itemsSkipped, $uniqueRetailerId) {

		// Generate rows
		/*
		foreach($itemsSkipped as $itemId => $row) {

			$results = parseExecuteQuery(["uniqueId" => $itemRows[$itemId]["uniqueId"]], "RetailerItems3rdPartyApprovals");

			// JMD
			if(count_like_php5($results) == 0) {

				$query = new ParseObject("RetailerItems3rdPartyApprovals");
				$query->set("uniqueId", $itemRows[$itemId]["uniqueId"]);
				$query->set("uniqueRetailerId", $itemRows[$itemId]["uniqueRetailerId"]);
				$query->set("itemPOSName", $itemRows[$itemId]["itemPOSName"]);
				$query->set("reasonForInitialSkip", $row[0]);
				$query->set("allowedThruSecurity", $itemRows[$itemId]["allowedThruSecurity"]);
				$query->set("approved", false);
				$query->save();
			}
		}
		*/

		$results = parseExecuteQuery(["uniqueRetailerId" => $uniqueRetailerId], "RetailerItems3rdPartyApprovals");
	
		$loaded = 0;
		foreach($itemRows as $itemId => $row) {

			// JMD
			$rowFound = false;
			foreach($results as $dbRow) {

				if(strcasecmp($dbRow->get('uniqueId'), $row["uniqueId"])==0) {

					$rowFound = true;
					break;
				}
			}

			if($rowFound == false) {

				$loaded++;

				$query = new ParseObject("RetailerItems3rdPartyApprovals");
				$query->set("uniqueId", $row["uniqueId"]);
				$query->set("uniqueRetailerId", $row["uniqueRetailerId"]);
				$query->set("itemPOSName", $row["itemPOSName"]);
				$query->set("allowedThruSecurity", $row["allowedThruSecurity"]);

				if(isset($itemsSkipped[$itemId])) {

					$query->set("reasonForInitialSkip", $itemsSkipped[$itemId][0]);
					// $query->set("reviewed", false);
					// $query->set("approved", false);
				}
				// else {

				// 	$query->set("reviewed", true);
				// 	$query->set("approved", true);
				// }

				$query->set("reviewed", false);
				$query->set("approved", false);
				$query->save();
			}
		}

		return $loaded;
	}

	function menu_initial_load_to_file($S3Path, $itemRows, $loadToS3=true, $localPath='') {

		// Generate files
		$csvItemHeader = "";
		$csvItemValues = "";
		$csvItemTimesHeader = "";
		$csvItemTimesValues = "";
		$csvItemModifiersHeader = "";
		$csvItemModifiersValues = "";
		$csvItemOptionsHeader = "";
		$csvItemOptionsValues = "";

		// Generate rows
		$count = 0;
		// JMD
		foreach($itemRows as $i => $row) {

			// Load each attribute
			foreach(array_keys($row) as $attribute) {

				// Generate modifiers file
				if(strcasecmp($attribute, "__modifiers")==0) {

					$header = "";
					foreach($row["__modifiers"] as $modifierRows) {

						$headerModifier = "";
						foreach($modifierRows as $modifierAttribute => $modifierValue) {

							// Generate options file
							if(strcasecmp($modifierAttribute, "__options")==0) {

								$headerOptions = "";
								foreach($modifierRows["__options"] as $optionRows) {

									foreach($optionRows as $optionAttribute => $optionValue) {

										if(empty($csvItemOptionsHeader)) {

											$headerOptions .= '"' . addslashesfordoublequotes($optionAttribute) . '"' . ",";
										}

										$csvItemOptionsValues .= '"' . addslashesfordoublequotes($optionValue) . '"' . ",";
									}

									if(empty($csvItemOptionsHeader)) {
						
										$csvItemOptionsHeader = $headerOptions;
									}

									$csvItemOptionsValues .= "\r\n";
								}
							}
							// Generate modifiers file
							else {
		
								if(empty($csvItemModifiersHeader)) {

									$headerModifier .= '"' . addslashesfordoublequotes($modifierAttribute) . '"' . ",";
								}

								$csvItemModifiersValues .= '"' . addslashesfordoublequotes($modifierValue) . '"' . ",";
							}
						}

						if(empty($csvItemModifiersHeader)) {
				
							$csvItemModifiersHeader = $headerModifier;
						}

						$csvItemModifiersValues .= "\r\n";
					}
				}
				// Generate item times file
				else if(strcasecmp($attribute, "__itemTimes")==0) {

					$header = "";
					foreach($row["__itemTimes"] as $itemTimesRows) {

						foreach($itemTimesRows as $itemTimesAttribute => $itemTimesValue) {

							if(empty($csvItemTimesHeader)) {

								$header .= $itemTimesAttribute . ",";
							}

							$csvItemTimesValues .= $itemTimesValue . ",";
						}

						if(empty($csvItemTimesHeader)) {
		
							$csvItemTimesHeader = $header;
						}

						$csvItemTimesValues .= "\r\n";
					}
				}
				// Generate item file
				else {

					if($count == 0) {

						$csvItemHeader .= $attribute . ",";
					}

					$csvItemValues .= '"' . addslashesfordoublequotes($row[$attribute]) . '"' . ",";
				}
			}

			$csvItemValues .= "\r\n";
			$count++;
		}

		$fileNameWithPathItems = 'items.csv';
		$fileNameWithPathModifiers = 'modifiers.csv';
		$fileNameWithPathOptions = 'options.csv';
		$fileNameWithPathItemTimes = 'itemtimes.csv';

		// Write to S3
		// JMD
		if($loadToS3) {

			$s3_client = getS3ClientObject();

			// Items file
			S3UploadFileWithContents($s3_client, $GLOBALS['env_S3BucketName'], $S3Path . $fileNameWithPathItems, $csvItemHeader . "\r\n" . $csvItemValues, false);

			// Modifiers file
			S3UploadFileWithContents($s3_client, $GLOBALS['env_S3BucketName'], $S3Path . $fileNameWithPathModifiers, $csvItemModifiersHeader . "\r\n" . $csvItemModifiersValues, false);

			// Item Options file
			S3UploadFileWithContents($s3_client, $GLOBALS['env_S3BucketName'], $S3Path . $fileNameWithPathOptions, $csvItemOptionsHeader . "\r\n" . $csvItemOptionsValues, false);

			// Item Times file
			S3UploadFileWithContents($s3_client, $GLOBALS['env_S3BucketName'], $S3Path . $fileNameWithPathItemTimes, $csvItemTimesHeader . "\r\n" . $csvItemTimesValues, false);
		}
		else {

			$file = fopen($localPath . $fileNameWithPathItems, 'w');
			fwrite($file, $csvItemHeader . "\r\n" . $csvItemValues);
			fclose($file);

			$file = fopen($localPath . $fileNameWithPathModifiers, 'w');
			fwrite($file, $csvItemModifiersHeader . "\r\n" . $csvItemModifiersValues);
			fclose($file);

			$file = fopen($localPath . $fileNameWithPathOptions, 'w');
			fwrite($file, $csvItemOptionsHeader . "\r\n" . $csvItemOptionsValues);
			fclose($file);

			$file = fopen($localPath . $fileNameWithPathItemTimes, 'w');
			fwrite($file, $csvItemTimesHeader . "\r\n" . $csvItemTimesValues);
			fclose($file);
		}
	}

	function cleanTextForCSV($string) {

		return $string;
		return str_replace(',', '-', $string);
	}

	function get_name($item) {

		return $this->cleanTextForCSV(trimFull($item->Name));
	}

	function get_item_image_name($item, $uniqueId) {

		// if($item->ID == '1033008-1') {

		// 	$this->menuItemImages[$item->ID] = "https://pbs.twimg.com/profile_images/779795536891314176/UjzJDxMN.jpg";
		// }

		if(isset($this->menuItemImages[$item->ID])) {

            $filename = basename(parse_url($this->menuItemImages[$item->ID], PHP_URL_PATH));

            // Download file from web to local location
			$imageInvalid = false;
            $originalFileName = time() . "_" . $filename;
            $tmp_download_location = rtrim(sys_get_temp_dir());
            $localFileNameWithPath = $tmp_download_location . "/" . $originalFileName;
            $fileContents = @file_get_contents($this->menuItemImages[$item->ID]);

            if(!empty($fileContents)) {

				$fp = fopen($localFileNameWithPath, 'w');
	            fwrite($fp, $fileContents);
	            fclose($fp);

	            // Check if the downloaded file is an image
				if(function_exists('exif_imagetype')) {

					if(!exif_imagetype($localFileNameWithPath)) {

						$imageInvalid = true;
					}
				}
				else if(!getimagesize($localFileNameWithPath)) {

					$imageInvalid = true;
				}
            }
            else {

				$imageInvalid = true;
            }

			if($imageInvalid == true) {

			    s3logMenuLoader(printLogTime() . "Image provided for " . $item->ID . " is invalid - " . $this->menuItemImages[$item->ID] . "\r\n");
			    return "";
			}

            // Upload file to S3
            $S3FileName = 'hmshost_' . $uniqueId . '_' . $filename;
			$s3_client = getS3ClientObject();
			$this->retailerInfo = getRetailerInfo($this->uniqueRetailerId);
			$airportIataCode = $this->retailerInfo["airportIataCode"];
			$retailerDirectoryName = getRetailerS3MenuDirectoryName($this->retailerInfo);
			$keyWithFolderPath = getS3KeyPath_RetailerMenuImagesPreLoad($airportIataCode, $this->partner, $retailerDirectoryName) . $S3FileName;
			$url = S3UploadFileWithPath($s3_client, $GLOBALS['env_S3BucketName'], $keyWithFolderPath, $localFileNameWithPath, false);
			unlink($localFileNameWithPath);

			if(is_array($url)) {

				s3logMenuLoader(printLogTime() . $lookupKeyName . " - is required but lookup failed!" . "\r\n");
				return "";
			}

			return $S3FileName;
		}

		return "";
	}

	function get_calories($item) {

		return (isset($item->Calories) && !empty($item->Calories)) ? "Calories: " . trimFull($item->Calories) . ". " : "";
	}

	function get_display_sequence($item) {

		return 0;
	}

	function get_modifier_display_sequence($itemId, $modifierGroupName) {

		$modifierGroupHash = md5($modifierGroupName);

		if(isset($this->menuItemModifiers[$itemId][$modifierGroupHash]["sequence"])) {

			return $this->menuItemModifiers[$itemId][$modifierGroupHash]["sequence"];
		}

		return 0;
	}

	function get_option_display_sequence($itemId, $modifierGroupName, $optionId) {

		$modifierGroupHash = md5($modifierGroupName);

		if(isset($this->menuItemModifiers[$itemId][$modifierGroupHash][$optionId]["sequence"])) {

			return $this->menuItemModifiers[$itemId][$modifierGroupHash][$optionId]["sequence"];
		}

		return 0;
	}

	function get_id($item) {

		return replaceSpecialChars($item->ID);
	}

	function get_modifier_id($itemId, $modifierGroupName) {

		$modifierGroupHash = md5($modifierGroupName);

		if(isset($this->menuItemModifiers[$itemId][$modifierGroupHash]["id"])) {

			return $this->menuItemModifiers[$itemId][$modifierGroupHash]["id"];
		}
		else {

			return replaceSpecialChars($itemId . '_' . md5($modifierGroupName));
		}
	}
/*
	function get_modifier_id($item, $modifierName) {

		return replaceSpecialChars($item->ID . '_' . md5($modifierName));
	}
*/
	function get_option_id($item, $option) {

		return trimFull($option->ID);
	}

	function get_unique_id($item, $suffix) {

		return "hmshost_" . md5(replaceSpecialChars($item->ID) . "_" . $suffix);
	}

	function get_unique_id_modifier($itemId, $modifierId, $suffix) {

		return "hmshost_" . md5($itemId . "_" . $modifierId . "_" . $suffix);
	}

/*
	function get_unique_id_modifier($modifier, $suffix) {

		return "hmshost_" . md5($modifier . "_" . $suffix);
	}
*/

	function get_unique_id_option($itemId, $modifierId, $optionId, $suffix) {

		return "hmshost_" . md5($itemId . "_" . $modifierId . "_" . $optionId . '_' . $suffix);
	}

/*
	function get_unique_id_option($option, $suffix) {

		return "hmshost_" . md5(replaceSpecialChars($option->ID) . "_" . $suffix);
	}
*/

	function get_is_active($item) {

		return $item->Active == true ? "Y" : "N";
	}

	function get_is_86($item) {

		// return $item->IsOutOfStock == true || $item->ItemAvailabilityStatus == false ? "Y" : "N";
		return $item->IsOutOfStock == true ? true : false;
	}

	function get_description($item) {

		// $description = $this->cleanTextForCSV(replaceSpecialChars(trim($item->Description), " ", true));
		$description = $this->cleanTextForCSV(trimFull($item->Description));

		if(empty($description)) {

			$description = " ";
		}

		return $description;
	}

	function get_prep_time($item) {

		// Time given is in minutes
		return intval(trim($item->PreparationTime)) * 60;
	}

	function get_price($item) {

		return trim(floatval(trim($item->Price)) * 100);
	}

	function get_category($item) {

		$category_name = "";

		if(isset($item->Categories))
		foreach($item->Categories as $category) {

			$category_name .= trimFull($category->Name);
			// $category_name .= replaceSpecialChars($category->Name, " ", true);
			break;
		}

		if(isset($item->Subcategories))
		foreach($item->Subcategories as $subcategory) {

			// $category_name .= replaceSpecialCharsAllowNumsAndLettersOnly($subcategory->Name);
			break;
		}

		return $this->cleanTextForCSV($category_name);
		// return substr($category_name, 0, -1);
	}

	function get_prep_time_restrictions($item, $prep_time) {

		if(intval_external($prep_time) == 0) {

			return "";
		}

		$restrictOrderTimes = $this->get_item_times($item);

		// default time group range
		if(is_bool($restrictOrderTimes)) {

			return "12:01 AM - 11:59 PM";
		}

		return "";
	}

	function get_item_times($item) {

		if(isset($item->Subcategories)) {

			foreach($item->Subcategories as $category) {

				if(isset($category->OpenTime)
					&& isset($category->CloseTime)) {

					$fromTime = date("g:i A", strtotime($category->OpenTime));
					$toTime = date("g:i A", strtotime($category->CloseTime));

					if($fromTime == $toTime) {

						return false;
					}

					return $fromTime . " - " . $toTime;
				}
			}
		}

		return false;
	}

	function get_modifier_rows($item, $modifiers) {

		$modifierRows = [];
		foreach($modifiers as $optionAPISequence => $modifier) {

			$uniqueRetailerItemId = $this->get_unique_id($item, $this->uniqueRetailerId);
			$modifierPOSName = $this->get_group_name($modifier);
			// JMD
			// $modifierId = md5($modifierPOSName);
			// $modifierId = $this->get_modifier_id($item, $modifierPOSName);
			$modifierId = $this->get_modifier_id($item->ID, $modifierPOSName);
			// $modifierUniqueId = $this->get_unique_id_modifier($uniqueRetailerItemId, $modifierId);
			$modifierUniqueId = $this->get_unique_id_modifier($item->ID, $modifierId, $this->uniqueRetailerId);
			// $modifierDisplaySequence = $this->get_display_sequence($modifier);
			$modifierDisplaySequence = $this->get_modifier_display_sequence($item->ID, $modifierPOSName);

			if(isset($modifier->Type)
				&& strcasecmp($modifier->Type, "REQUIRED")==0) {

				$minQuantity = 1;
				$maxQuantity = 1;
				$isRequired = "Y";
			}
			// Else all HMSHost modifiers are optional
			else {

				$minQuantity = 0;
				$maxQuantity = 1;
				$isRequired = "N";
			}

			if(!isset($modifierRows[$modifierId])) {

				$modifierRows[$modifierId]["modifierId"] = $modifierId;
				$modifierRows[$modifierId]["modifierPOSName"] = $modifierPOSName;
				$modifierRows[$modifierId]["uniqueId"] = $modifierUniqueId;
				$modifierRows[$modifierId]["modifierDisplaySequence"] = $modifierDisplaySequence;
				$modifierRows[$modifierId]["minQuantity"] = $minQuantity;
				$modifierRows[$modifierId]["maxQuantity"] = $maxQuantity;
				$modifierRows[$modifierId]["isRequired"] = $isRequired;
				$modifierRows[$modifierId]["uniqueRetailerItemId"] = $uniqueRetailerItemId;
				$modifierRows[$modifierId]["isActive"] = "Y";
				$modifierRows[$modifierId]["modifierDisplayName"] = "";
			}

			$optionId = $this->get_option_id($item, $modifier);
			$optionPOSName = $this->get_name($modifier);

			$optionDisplayDescription = $this->get_description($modifier);
			// $optionUniqueId = $this->get_unique_id_option($modifier, $uniqueRetailerItemId);
			$optionUniqueId = $this->get_unique_id_option($item->ID, $modifierId, $optionId, $this->uniqueRetailerId);

			// $optionDisplaySequence = $optionAPISequence + 1;
			$optionDisplaySequence = $this->get_option_display_sequence($item->ID, $modifierPOSName, $optionId);

			$modifierRows[$modifierId]["__options"][$optionId] = [
				"optionPOSName" => $optionPOSName, 
				"optionDisplayDescription" => $optionDisplayDescription, 
				"optionId" => $optionId, 
				"uniqueId" => $optionUniqueId, 
				"isActive" => $this->get_is_active($modifier), 
				"uniqueRetailerItemModifierId" => $modifierUniqueId, 
				"pricePerUnit" => $this->get_price($modifier),
				"optionDisplayName" => "",
				"optionDisplaySequence" => $optionDisplaySequence,
				"priceLevelId" => ""
			];
		}

		return $modifierRows;
	}

	function get_group_name($modifier) {

		return replaceSpecialChars($modifier->GroupName, " ", true);
	}

	function menu_item_customizable_to_file($S3Path, $uniqueRetailerId) {

		$results = parseExecuteQuery(["uniqueRetailerId" => $uniqueRetailerId], "RetailerItems3rdPartyApprovals", "", "", ["taxCategory"]);

		$csvHeader = $csvValues = "";
		$csvHeader = "uniqueId" . ","
				   . "uniqueRetailerId" . ","
				   . "itemPOSName" . ","
				   . "reasonForInitialSkip" . ","
				   . "itemImageURL" . ","
				   . "allowedThruSecurity" . ","
				   . "itemDisplayName" . ","
				   . "itemSecondCategoryName" . ","
				   . "itemThirdCategoryName" . ","
				   . "itemTags" . ","
				   . "itemDisplaySequence" . ","
				   . "taxCategory" . ","
				   . "reviewed" . ","
				   . "approved" . ","
				   ;

		foreach($results as $i => $row) {

			// JMD
			$csvValues .= '"' . $row->get("uniqueId") . '"' . ",";
			$csvValues .= '"' . $row->get("uniqueRetailerId") . '"' . ",";
			$csvValues .= '"' . $row->get("itemPOSName") . '"' . ",";
			$csvValues .= '"' . $row->get("reasonForInitialSkip") . '"' . ",";
			$csvValues .= '"' . $row->get("itemImageURL") . '"' . ",";
			$csvValues .= '"' . $row->get("allowedThruSecurity") . '"' . ",";
			$csvValues .= '"' . $row->get("itemDisplayName") . '"' . ",";
			$csvValues .= '"' . $row->get("itemSecondCategoryName") . '"' . ",";
			$csvValues .= '"' . $row->get("itemThirdCategoryName") . '"' . ",";
			$csvValues .= '"' . $row->get("itemTags") . '"' . ",";
			$csvValues .= '"' . $row->get("itemDisplaySequence") . '"' . ",";

			$csvValues .= (($row->has("taxCategory") && !empty($row->get("taxCategory"))) ? $row->get("taxCategory")->get("categoryId") : "") . ",";

			$csvValues .= ($row->get("reviewed") == true ? "Y" : "N") . ",";

			$csvValues .= ($row->get("approved") == true ? "Y" : "N");

			$csvValues .= "\r\n";
		}

		// Generate files
		$filename = 'items - customizable.csv';

        $localFileName = time() . "_" . $filename;
        $localFilePath = rtrim(sys_get_temp_dir()) . '/' . $localFileName;

        // JMD
		// Save file locally
		$file = fopen($localFilePath, 'w');
		fwrite($file, substr($csvHeader, 0, -1) . "\r\n" . $csvValues);
		fclose($file);

		// Write to S3
		$s3_client = getS3ClientObject();
		$S3filename = $filename;
		S3UploadFileWithPath($s3_client, $GLOBALS['env_S3BucketName'], $S3Path . $S3filename, $localFilePath, false);

		// Delete local file
		unlink($localFilePath);
	}

	function load_menu_item_customizable_file_to_db($S3Path, $uniqueRetailerId) {

		// Read file
		$filename = 'items - customizable.csv';

		$objectKeyIsArray = ["uniqueId" => "N",
				   "uniqueRetailerId" => "N",
				   "itemPOSName" => "N",
				   "reasonForInitialSkip" => "N",
				   "itemImageURL" => "N",
				   "allowedThruSecurity" => "NB",
				   "itemDisplayName" => "N",
				   "itemSecondCategoryName" => "N",
				   "itemThirdCategoryName" => "N",
				   "itemTags" => "N",
				   "itemDisplaySequence" => "I",
				   "taxCategory" => "N",
				   "reviewed" => "N",
				   "approved" => "N"];

		$s3_client = getS3ClientObject();
		if(!S3FileExsists($s3_client, $GLOBALS['env_S3BucketName'], substr($S3Path, 0, -1), $filename)) {

			throw new Exception("File not found: " . $S3Path . $filename);
		}

		// Time we last loaded this file
		$lastLoadCustomizableFileTime = intval_external(getMenuLoaderCustomizableLoadTime($uniqueRetailerId));
		$lastModifiedTime = S3GetLastModifiedTimeFile($s3_client, $GLOBALS['env_S3BucketName'], $S3Path . $filename);

		// File Modified time fetch failed
		if(is_array($lastModifiedTime)) {

		    s3logMenuLoader(printLogTime() . "Customizable file time not found " . $lastModifiedTime["error_message_log"] . "\r\n");
			$lastModifiedTime = time() + 100;
		}

	    s3logMenuLoader(printLogTime() . $S3Path . $filename . " - File last modified: " . date("M-j-Y G:i:s T", $lastModifiedTime) . ". File last loaded: " . date("M-j-Y G:i:s T", $lastLoadCustomizableFileTime) . "\r\n");

		// We loaded the before and it hasn't changed since
		if($lastLoadCustomizableFileTime >= $lastModifiedTime) {

			return false;
		}

		$fileUrl = S3GetPrivateFile($s3_client, $GLOBALS['env_S3BucketName'], $S3Path . $filename, 2);
		$fileArray = array_map('str_getcsv', file($fileUrl));

		$objectKeys = array_map('trim', array_shift($fileArray));

		prepareAndPostToParse("", "", "RetailerItems3rdPartyApprovals", $fileArray, $objectKeyIsArray, $objectKeys, "N", ["uniqueId", "uniqueRetailerId"], [], [], false, array_keys($objectKeyIsArray));

		return true;
	}

    function __destruct() {

    	$this->session_end();
    }
}

?>
