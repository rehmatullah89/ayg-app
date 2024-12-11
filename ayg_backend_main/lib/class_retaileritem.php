<?php

use Parse\ParseQuery;
use Parse\ParseObject;

class RetailerItem {

	private $retailerId;
	private $retailerItemId;
	private $dbObj;
	private $retailerItemModifierListObjs = [];
	private $retailerItemModifierOptionListObjs = [];

	function __construct($retailerItemId) {

		$this->set("retailerItemId", $retailerItemId);
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

	function fetchRetailerItem() {

		$objRetailerItem = parseExecuteQuery(["uniqueId" => $this->get("retailerItemId"), "isActive" => true], "RetailerItems", "", "", [], 1);
		$this->setDBObj($objRetailerItem);
	}

	function fetchRetailerItemModifiers() {

		$objRetailerItemModifiers = parseExecuteQuery(["uniqueRetailerItemId" => $this->getUniqueId(), "isActive" => true], "RetailerItemModifiers");

		foreach($objRetailerItemModifiers as $modifier) {

			// Create a modifier object
			$obj = new RetailerItemModifier('');
			$obj->setDBObj($modifier);

			$this->addModifier($obj);
		}
	}

	function getUniqueId() {

		return $this->getFromDB("uniqueId");
	}

	function getUniqueRetailerId() {

		return $this->getFromDB("uniqueRetailerId");
	}

	function hasModifiers() {

		return count_like_php5($this->get("retailerItemModifierListObjs"));
	}

	function addModifier($modifierObj) {

		$this->retailerItemModifierListObjs[$modifierObj->getUniqueId()] = $modifierObj;
	}

	function getModifier($modifierId) {

		return $this->retailerItemModifierListObjs[$modifierId];
	}

	function getRequiredModifiers() {

		$requiredModifiers = [];
		foreach($this->retailerItemModifierListObjs as $modifier) {

			if($modifier->getIsRequired()) {

				$requiredModifiers[$modifier->getUniqueId()] = true;
			}
		}

		return $requiredModifiers;
	}

	function fetchRetailerItemModifierOptions() {

		$objModifiersOptions = parseExecuteQuery(array("__CONTAINEDIN__uniqueRetailerItemModifierId" => array_keys($this->retailerItemModifierListObjs), "isActive" => true), "RetailerItemModifierOptions");

		foreach($objModifiersOptions as $option) {

			// Create an option object
			$obj = new RetailerItemModifierOption('');
			$obj->setDBObj($option);

			$this->addModifierOption($obj);
		}
	}

	function addModifierOption($retailerItemModifierOption) {

		$this->retailerItemModifierOptionListObjs[$retailerItemModifierOption->getUniqueId()] = $retailerItemModifierOption;
	}

	function getObjectId() {

		return $this->getFromDB("objectId");
	}

	function getModifierOption($optionId) {

		return $this->retailerItemModifierOptionListObjs[$optionId];
	}

	function getModifierOptions() {

		return $this->retailerItemModifierOptionListObjs;
	}
}

class RetailerItemModifier {

	private $retailerItemModifierId;
	private $dbObj;

	function __construct($retailerItemModifierId) {

		$this->set("retailerItemModifierId", $retailerItemModifierId);
	}

	function set($key, $value) {

		$this->$key = replaceSpecialChars($value);
	}

	function get($key) {

		return $this->$key;
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

		$this->set("dbObj", $dbObj);
	}

	function getDBObj() {

		return $this->get("dbObj");
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

	function fetchRetailerItemModifier() {

		$objRetailerItemModifier = parseExecuteQuery(["uniqueId" => $this->getRetailerItemModifierId(), "isActive" => true], "RetailerItemModifiers", "", "", [], 1);

		$this->setDBObj($objRetailerItemModifier);
		$this->set("retailerItemModifierId", $this->getUniqueId());
	}

	function getRetailerItemModifierId() {

		$this->get("retailerItemModifierId");
	}

	function getUniqueId() {

		return $this->getFromDB("uniqueId");
	}

	function getIsRequired() {

		return $this->getFromDB("isRequired");
	}

	function getMaxQuantity() {

		return $this->getFromDB("maxQuantity");
	}

	function getMinQuantity() {

		return $this->getFromDB("minQuantity");
	}
}

class RetailerItemModifierOption {

	private $retailerItemModifierOptionId;
	private $dbObj;

	function __construct($retailerItemModifierOptionId) {

		$this->set("retailerItemModifierOptionId", $retailerItemModifierOptionId);
	}

	function set($key, $value) {

		$this->$key = replaceSpecialChars($value);
	}

	function get($key) {

		return $this->$key;
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

		$this->set("dbObj", $dbObj);
	}

	function getDBObj() {

		return $this->get("dbObj");
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

	function fetchRetailerItemModifierOption() {

		$objRetailerItemModifierOption = parseExecuteQuery(["uniqueId" => $this->getRetailerItemModifierOptionId(), "isActive" => true], "RetailerItemModifierOptions", "", "", [], 1);

		$this->setDBObj($objRetailerItemModifierOption);
		$this->set("retailerItemModifierOptionId", $this->getUniqueId());
	}

	function getRetailerItemModifierOptionId() {

		$this->get("retailerItemModifierOptionId");
	}

	function getObjectId() {

		return $this->getFromDB("objectId");
	}

	function getUniqueId() {

		return $this->getFromDB("uniqueId");
	}

	function getOptionId() {

		return $this->getFromDB("optionId");
	}

	function getPricePerUnit() {

		return $this->getFromDB("pricePerUnit");
	}

	function getUniqueRetailerItemModifierId() {

		return $this->getFromDB("uniqueRetailerItemModifierId");
	}
}

?>