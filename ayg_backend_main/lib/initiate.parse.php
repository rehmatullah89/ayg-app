<?php

use Parse\ParseClient;

if(!empty($env_ParseServerURL)) {

	ParseClient::setServerURL($env_ParseServerURL, $env_ParseMount);
	ParseClient::initialize($env_ParseApplicationId, $env_ParseRestAPIKey, $env_ParseMasterKey);
}

$parseClassAttributes = array(
		"_Role" => array(

				"acl" => "parseClass",
				"ttl" => "NOC", // NOC = no cache, 0 = don't expire, EOD = End of Day, EOD = End of Week, other values state that specific value
				"cacheOnlyWhenResult" => false
			),
		"_Session" => array(
				"acl" => "parseClass",
				"ttl" => "NOC",
				"cacheOnlyWhenResult" => false
				),
		"_User" => array(
				"acl" => "parseClass",
				"ttl" => "NOC",
				"cacheOnlyWhenResult" => false
				),
		"AirlineTerminalMap" => array(
				"acl" => "metaClass",
				"ttl" => "EOD",
				"cacheOnlyWhenResult" => false
			),
		"Airlines" => array(
				"acl" => "metaClass",
				"ttl" => "EOD",
				"cacheOnlyWhenResult" => false
			),
		"Airports" => array(
				"acl" => "metaClass",
				"ttl" => "EOD",
				"cacheOnlyWhenResult" => false
			),
		"BetaInvites" => array(
				"acl" => "metaClass",
				"ttl" => "NOC",
				"cacheOnlyWhenResult" => false
			),
		"Config" => array(
				"acl" => "metaClass",
				"ttl" => "NOC",
				"cacheOnlyWhenResult" => false
			),
        "Coupons" => array(
            "acl" => "metaClass",
            "ttl" => "EOD",
            "cacheOnlyWhenResult" => false
        ),
        "CouponGroups" => array(
            "acl" => "metaClass",
            "ttl" => "EOD",
            "cacheOnlyWhenResult" => false
        ),
        "UserCoupons" => array(
            "acl" => "metaClass",
            "ttl" => "NOC",
            "cacheOnlyWhenResult" => false
        ),
        "UserCredits" => array(
            "acl" => "metaClass",
            "ttl" => "NOC",
            "cacheOnlyWhenResult" => false
        ),
        "UserCreditsAppliedMap" => array(
            "acl" => "metaClass",
            "ttl" => "NOC",
            "cacheOnlyWhenResult" => false
        ),
        "UserCreditsApplied" => array(
            "acl" => "metaClass",
            "ttl" => "NOC",
            "cacheOnlyWhenResult" => false
        ),
        "UserReferral" => array(
            "acl" => "metaClass",
            "ttl" => "NOC",
            "cacheOnlyWhenResult" => false
        ),
        "UserReferralUsage" => array(
            "acl" => "metaClass",
            "ttl" => "NOC",
            "cacheOnlyWhenResult" => false
        ),
        "UserReferralOffer" => array(
            "acl" => "metaClass",
            "ttl" => "NOC",
            "cacheOnlyWhenResult" => false
        ),
		"RetailerCategory" => array(
				"acl" => "metaClass",
				"ttl" => "EOD",
				"cacheOnlyWhenResult" => false
			),
		"RetailerFoodSeatingType" => array(
				"acl" => "metaClass",
				"ttl" => "EOD",
				"cacheOnlyWhenResult" => false
			),
		"RetailerItemCategories" => array(
				"acl" => "metaClass",
				"ttl" => "EOD",
				"cacheOnlyWhenResult" => false
			),
		"RetailerItemModifierOptions" => array(
				"acl" => "metaClass",
				"ttl" => "EOD",
				"cacheOnlyWhenResult" => false
			),
		"RetailerItemModifiers" => array(
				"acl" => "metaClass",
				"ttl" => "EOD",
				"cacheOnlyWhenResult" => false
			),
		"RetailerItems" => array(
				"acl" => "metaClass",
				"ttl" => "EOD",
				"cacheOnlyWhenResult" => false
			),
		"RetailerItemProperties" => array(
				"acl" => "metaClass",
				"ttl" => "EOD",
				"cacheOnlyWhenResult" => false
			),
		"RetailerPOSConfig" => array(
				"acl" => "metaClass",
				"ttl" => "EOD",
				"cacheOnlyWhenResult" => false
			),
		"RetailerType" => array(
				"acl" => "metaClass",
				"ttl" => "EOD",
				"cacheOnlyWhenResult" => false
			),
		"RetailerPriceCategory" => array(
				"acl" => "metaClass",
				"ttl" => "EOD",
				"cacheOnlyWhenResult" => false
			),
		"RetailerItems3rdPartyApprovals" => array(
				"acl" => "metaClass",
				"ttl" => "NOC",
				"cacheOnlyWhenResult" => false
			),
		"Retailers" => array(
				"acl" => "metaClass",
				"ttl" => "EOD",
				"cacheOnlyWhenResult" => false
			),
		"RetailerAds" => array(
				"acl" => "metaClass",
				"ttl" => "NOC",
				"cacheOnlyWhenResult" => false
			),
		"RetailerTabletUsers" => array(
				"acl" => "metaClass",
				"ttl" => "NOC",
				"cacheOnlyWhenResult" => false
			),
		"TerminalGateMap" => array(
				"acl" => "metaClass",
				"ttl" => "EOD",
				"cacheOnlyWhenResult" => false
			),
		"TerminalGateMapRetailerRestrictions" => array(
				"acl" => "metaClass",
				"ttl" => "EOD",
				"cacheOnlyWhenResult" => false
			),
		"ContactSubmission" => array(
				"acl" => "metaClass",
				"ttl" => "NOC",
				"cacheOnlyWhenResult" => false
			),
		"OrderProcessingErrors" => array(
				"acl" => "metaClass",
				"ttl" => "NOC",
				"cacheOnlyWhenResult" => false
			),
		"OrderVariances" => array(
				"acl" => "metaClass",
				"ttl" => "NOC",
				"cacheOnlyWhenResult" => false
			),
		"OrderDelayedRefund" => array(
				"acl" => "userOrderClass",
				"ttl" => "NOC",
				"cacheOnlyWhenResult" => false
			),
		"OrderDelays" => array(
				"acl" => "metaClass",
				"ttl" => "NOC",
				"cacheOnlyWhenResult" => false
			),
		"Partner" => array(
				"acl" => "metaClass",
				"ttl" => "NOC",
				"cacheOnlyWhenResult" => false
			),
		"PartnerSessions" => array(
				"acl" => "metaClass",
				"ttl" => "NOC",
				"cacheOnlyWhenResult" => false
			),
		"SessionDevices" => array(
				"acl" => "metaClass",
				"ttl" => "NOC",
				"cacheOnlyWhenResult" => false
			),
		"FlightTrips" => array(
				"acl" => "userClass",
				"ttl" => "NOC",
				"cacheOnlyWhenResult" => false
			),
		"UserTrips" => array(
				"acl" => "userClass",
				"ttl" => "NOC",
				"cacheOnlyWhenResult" => false
			),
		"OrderRatingRequests" => array(
				"acl" => "userClass",
				"ttl" => "NOC",
				"cacheOnlyWhenResult" => false
			),
		"UserDevices" => array(
				"acl" => "userClass",
				"ttl" => "NOC",
				"cacheOnlyWhenResult" => false
			),
		"UserDevicesWhiteList" => array(
				"acl" => "userClass",
				"ttl" => "NOC",
				"cacheOnlyWhenResult" => false
			),
		"UserPhones" => array(
				"acl" => "userClass",
				"ttl" => "NOC",
				"cacheOnlyWhenResult" => false
			),
		"UserDevicesBlocked" => array(
				"acl" => "userClass",
				"ttl" => "NOC",
				"cacheOnlyWhenResult" => false
			),
		"UserPhonesWhiteList" => array(
				"acl" => "userClass",
				"ttl" => "NOC",
				"cacheOnlyWhenResult" => false
			),
		"UserPhonesCarrierBlocked" => array(
				"acl" => "userClass",
				"ttl" => "NOC",
				"cacheOnlyWhenResult" => false
			),
		"UserVerification" => array(
				"acl" => "userClass",
				"ttl" => "NOC",
				"cacheOnlyWhenResult" => false
			),
		"BugReports" => array(
				"acl" => "userClass",
				"ttl" => "NOC",
				"cacheOnlyWhenResult" => false
			),
		"DeliverySession" => array(
				"acl" => "userClass",
				"ttl" => "NOC",
				"cacheOnlyWhenResult" => false
			),
		"DeliveryUser" => array(
				"acl" => "userClass",
				"ttl" => "NOC",
				"cacheOnlyWhenResult" => false
			),
		"FlightStatuses" => array(
				"acl" => "userClass",
				"ttl" => "NOC",
				"cacheOnlyWhenResult" => false
			),
		"Flights" => array(
				"acl" => "userClass",
				"ttl" => "NOC",
				"cacheOnlyWhenResult" => false
			),
		"Payments" => array(
				"acl" => "userClass",
				"ttl" => "NOC",
				"cacheOnlyWhenResult" => false
			),
		"RetailerVisitsUser" => array(
				"acl" => "userClass",
				"ttl" => "NOC",
				"cacheOnlyWhenResult" => false
			),
		"TripItSessions" => array(
				"acl" => "userClass",
				"ttl" => "NOC",
				"cacheOnlyWhenResult" => false
			),
		"TripItTokens" => array(
				"acl" => "userClass",
				"ttl" => "NOC",
				"cacheOnlyWhenResult" => false
			),
		"DeliveryAssignment" => array(
				"acl" => "userOrderClass",
				"ttl" => "NOC",
				"cacheOnlyWhenResult" => false
			),
		"DeliveryStatus" => array(
				"acl" => "userOrderClass",
				"ttl" => "NOC",
				"cacheOnlyWhenResult" => false
			),
		"Order" => array(
				"acl" => "userOrderClass",
				"ttl" => "NOC",
				"cacheOnlyWhenResult" => false
			),
		"OrderModifiers" => array(
				"acl" => "userOrderClass",
				"ttl" => "NOC",
				"cacheOnlyWhenResult" => false
			),
		"OrderCountsByRetailer" => array(
				"acl" => "userOrderClass",
				"ttl" => "NOC",
				"cacheOnlyWhenResult" => false
			),
		"OrderStatus" => array(
				"acl" => "userOrderClass",
				"ttl" => "NOC",
				"cacheOnlyWhenResult" => false
			),
		"AirEmployeeRequests" => array(
				"acl" => "useClass",
				"ttl" => "NOC",
				"cacheOnlyWhenResult" => false
			),
		"Trips" => array(
				"acl" => "userOrderClass",
				"ttl" => "NOC",
				"cacheOnlyWhenResult" => false
			),
		"Sequences" => array(
				"acl" => "commonClass",
				"ttl" => "NOC",
				"cacheOnlyWhenResult" => false
			),
		"DirectionImages" => array(
				"acl" => "commonClass",
				"ttl" => "NOC",
				"cacheOnlyWhenResult" => false
			),
		"RetailersVisitsTop" => array(
				"acl" => "commonClass",
				"ttl" => "NOC",
				"cacheOnlyWhenResult" => false
			),
		"List" => array(
				"acl" => "commonClass",
				"ttl" => "EOD",
				"cacheOnlyWhenResult" => false
			),
		"ListDetails" => array(
				"acl" => "commonClass",
				"ttl" => "EOD",
				"cacheOnlyWhenResult" => false
			),
		"DualPartnerConfig" => array(
				"acl" => "commonClass",
				"ttl" => "NOC",
				"cacheOnlyWhenResult" => false
			),
		"zWebsiteDownloads" => array(
				"acl" => "commonClass",
				"ttl" => "NOC",
				"cacheOnlyWhenResult" => false
			),
		"zAppRatingRequestClicks" => array(
				"acl" => "commonClass",
				"ttl" => "NOC",
				"cacheOnlyWhenResult" => false
			),
		"zLogFlightAPI" => array(
				"acl" => "commonClass",
				"ttl" => "NOC",
				"cacheOnlyWhenResult" => false
			),
		"zQAappRetailerLookup" => array(
				"acl" => "commonClass",
				"ttl" => "NOC",
				"cacheOnlyWhenResult" => false
			),
		"zDeliverySlackOrderAssignments" => array(
				"acl" => "commonClass",
				"ttl" => "NOC",
				"cacheOnlyWhenResult" => false
			),
		"zDeliverySlackUser" => array(
				"acl" => "commonClass",
				"ttl" => "EOD",
				"cacheOnlyWhenResult" => false
			),
		"zDeliverySlackUserLocationRestrictions" => array(
				"acl" => "commonClass",
				"ttl" => "EOD",
				"cacheOnlyWhenResult" => false
			),
		"zDuplicateUsage" => array(
				"acl" => "commonClass",
				"ttl" => "NOC",
				"cacheOnlyWhenResult" => false
			),
		"zDeliveryCoveragePeriod" => array(
				"acl" => "commonClass",
				"ttl" => "NOC",
				"cacheOnlyWhenResult" => false
			),
		"zLogInstall" => array(
				"acl" => "commonClass",
				"ttl" => "NOC",
				"cacheOnlyWhenResult" => false
			),
    "RetailerPartners" => array(
        "acl" => "commonClass",
        "ttl" => "NOC",
        "cacheOnlyWhenResult" => false
    ),
	);

?>
