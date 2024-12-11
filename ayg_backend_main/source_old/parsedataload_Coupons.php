<?php

// @todo - no file

	require 'dirpath.php';
	$fullPathToBackendLibraries = "";
	
	require_once $fullPathToBackendLibraries . 'vendor/autoload.php';
	require_once $fullPathToBackendLibraries . 'lib/initiate.inc.php';
	require_once $fullPathToBackendLibraries . 'lib/gatemaps.inc.php';
	require_once $fullPathToBackendLibraries . 'lib/functions_directions.php';
	
	// require 'parsedataload_functions.php';


	use Parse\ParseClient;
	use Parse\ParseQuery;
	use Parse\ParseObject;
	use Parse\ParseUser;
	use Parse\ParseFile;
	use Parse\ParseGeoPoint;

	require $fullPathToBackendLibraries . 'lib/initiate.parse.php';

	ob_start();	
	while (ob_get_level() > 0)
    ob_end_flush();

	$fileArray = array_map('str_getcsv', file('<path_to_files>\Coupons - prod.csv'));

	// Skip the Header row and create key arrays
	$objectKeys = array_map('trim', array_shift($fileArray));
	
	// Array lists which columns are Arrays (with semi-colon separated values) with a Y are columns, G for GeoPoints, I is integer, N neither, X don't use as data for upload
	$objectKeyIsArray = array(
								"couponCode" => "N",
								"isActive" => "N",
								"forSignup" => "N",
								"forCart" => "N",
								"couponDiscountCents" => "I",
								"couponDiscountPCT" => "F",
								"couponDiscountForFeeCents" => "I",
								"couponDiscountForFeePCT" => "F",
								"couponDiscountPCTMaxCents" => "I",
								"activeTimestamp" => "I",
								"expiresTimestamp" => "I",
								"isRetailerCompensated" => "N",
								"maxUserAllowedByAll" => "I",
								"maxUserAllowedByUser" => "I",
								"maxUsageAllowedByDevice" => "I",
								"isFirstUseOnly" => "N",
								"applicableAirportIataCodes" => "Y",
								"applicableRetailerUniqueIds" => "Y",
								"applicableConsumerEmail" => "X",
								"description" => "N",
								"applyDiscountToOrderMinOfInCents" => "I",
								"onSignupAcctCreditsInCents" => "I",
								"onSignupAcctCreditsWelcomeMsg" => "N",
								"onSignupAcctCreditsWelcomeLogoFilename" => "N",
								"allowWithReferralCredit" => "N",
								"fullfillmentTypeRestrict" => "N",
								"savingsTextDisplay" => "N",
								"onSignupAcctCreditsExpiresTimestamp" => "I",
								"disallowForCreditReasonCodes" => "Y",
							);

	$referenceLookup = array(
			"applicableUser" => array(
								"className" => "_User",
								"isRequired" => false,
								"whenColumnValuePresentIsRequired" => "applicableConsumerEmail", // Name of the column to check, if it is preset then isRequired is assumed to be true
								"lookupCols" => array(
													// Column in ClassName => Column in File
													"email" => "applicableConsumerEmail",
													// "__LKPVAL__isActive" => true
												),
								// "lookupColsType" => array(
								// 					"email" => "Y", // An array
								// 				)
							),
	);

	$imagesIndexesWithPaths = array(
			"onSignupAcctCreditsWelcomeLogoFilename" => [
					"S3KeyPath" => getS3KeyPath_ImagesCouponLogo(),
					"useUniqueIdInName" => "N",
					"useThisColumnValueInName" => "couponCode",
					"maxWidth" => '',
					"maxHeight" => '',
					"createThumbnail" => false,
					"imagePath" => 'D:\Cloud\Google Drive\Airport Sherpa\Airport Sherpa Operations (new)\11.1 - Data\CouponLogos'],
	);

	echo("---- Processing Codes - setting to lower case" . "<br />\n");
	flush();
	@ob_flush();
	$fileArrayCouponGroup[] = ["couponCode", "groupId"];
	$couponCodeKey = array_search("couponCode", $objectKeys);
	$groupIdKey = array_search("groupId", $objectKeys);
	$fileArrayCouponsUnique = [];
	$uniqueCodes = [];
	foreach($fileArray as $index => $object) {
		
		// Check coupon code doesn't start with an Z
		if(preg_match("/^Z(.*)/si", $object[$couponCodeKey])) {

			die($object[$couponCodeKey] . " - Coupon code cannot begin with an Z");
		}

		// Lower case the coupon code
		$object[$couponCodeKey] = strtolower($object[$couponCodeKey]);

		if(in_array($object[$couponCodeKey], $uniqueCodes)) {

			die($object[$couponCodeKey] . " - duplicate");
		}

		$uniqueCodes[] = $object[$couponCodeKey];

		// List all coupons (unique list)
		$fileArrayCouponsUnique[$object[$couponCodeKey]] = 1;

		// Save it back to array
		$fileArray[$index] = $object;

		// Group Id list
		$groupIdList = explode(";", $object[$groupIdKey]);

		foreach($groupIdList as $groupId) {

			$groupId = trim($groupId);

			if(empty($groupId)) {

				continue;
			}

			// Save it back to array
			$fileArrayCouponGroup[] = [$object[$couponCodeKey], $groupId];
		}
	}

	prepareAndPostToParse($env_ParseApplicationId, $env_ParseRestAPIKey, "Coupons", $fileArray, $objectKeyIsArray, $objectKeys, "N", array("couponCode"), $imagesIndexesWithPaths, $referenceLookup); // the second to last array lists the keys to combine to make a lookupkey


	/////////////////////////////////////////////////////////////////////////////////////
	/////////////////////////////////////////////////////////////////////////////////////
	/////////////////////////////////////////////////////////////////////////////////////

	echo("---- Removing CouponGroups" . "<br />\n");
	flush();
	@ob_flush();
	// Remove all rows from CouponGroups for coupon (since there is no other way to delete a group)
	$couponGroupCount = 0;
	foreach(array_keys($fileArrayCouponsUnique) as $couponCode) {

	    $couponRefObject = new ParseQuery("Coupons");
	    $couponAssociation = parseSetupQueryParams(["couponCode" => $couponCode], $couponRefObject);

	    // Find all CouponGroups for this coupon
	    $couponGroups = parseExecuteQuery(["__MATCHESQUERY__coupon" => $couponAssociation], "CouponGroups");
	    $couponGroupCount += count_like_php5($couponGroups);

	    foreach($couponGroups as $couponGroup) {

	    	$couponGroup->destroy();
	    	$couponGroup->save();
	    }
	}
	echo("---- Removed $couponGroupCount CouponGroups" . "<br />\n");
	flush();
	@ob_flush();

	echo("---- Loading CouponGroups" . "<br />\n");
	flush();
	@ob_flush();
	// Skip the Header row and create key arrays
	$objectKeys = array_map('trim', array_shift($fileArrayCouponGroup));
	
	// Array lists which columns are Arrays (with semi-colon separated values) with a Y are columns, G for GeoPoints, I is integer, N neither, X don't use as data for upload
	$objectKeyIsArray = array(
								"couponCode" => "X",
								"groupId" => "N"
							);

	$referenceLookup = array(
			"coupon" => array(
								"className" => "Coupons",
								"isRequired" => true,
								"whenColumnValuePresentIsRequired" => "couponCode", // Name of the column to check, if it is preset then isRequired is assumed to be true
								"lookupCols" => array(
													// Column in ClassName => Column in File
													"couponCode" => "couponCode",
													// "__LKPVAL__isActive" => true
												),
							),
	);

	$imagesIndexesWithPaths = array(
	);

	prepareAndPostToParse($env_ParseApplicationId, $env_ParseRestAPIKey, "CouponGroups", $fileArrayCouponGroup, $objectKeyIsArray, $objectKeys, "Y", array("couponCode", "groupId"), $imagesIndexesWithPaths, $referenceLookup); // the second to last array lists the keys to combine to make a lookupkey




	$cacheKeyList[] = $GLOBALS['redis']->keys("*Coupon*");
	echo("Resetting cache..." . "\r\n" . "<br />");

	print_r(resetCache($cacheKeyList));

	@ob_end_clean();
	
?>
