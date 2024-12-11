<?php

use App\Background\Services\ReportServiceFactory;
use Parse\ParseObject;
use Parse\ParseUser;

/**
 * @param $retailerUniqueId
 * @param $openTimestamp
 * @param $closeTimestamp
 */
function identifyTabletsUptimePercentageByDay($retailerUniqueId, $openTimestamp, $closeTimestamp) {

    // timezone is taken from server settings

    // identify start and end timestamp for a given day
    // $startDateTime = new DateTime($day);
    // $endDateTime = clone $startDateTime;
    // $endDateTime = $endDateTime->add(new DateInterval('P0Y0DT23H59M59S'));

    $startDateTimeTimestamp = $openTimestamp; //$startDateTime->getTimestamp();
    $endDateTimeTimestamp = $closeTimestamp; //$endDateTime->getTimestamp();
    $expectedPingInterval = getenv('env_PingRetailerIntervalInSecs');

    $reportServiceFactory = ReportServiceFactory::create($GLOBALS['logsPdoConnection']);
    try {
        $lostPercentage = $reportServiceFactory->getLostRetailerPingPercentageForAGivenTimeRange($retailerUniqueId, $startDateTimeTimestamp, $endDateTimeTimestamp, $expectedPingInterval);
    } catch (\App\Background\Exceptions\PingNotFoundException $exception) {
        // there is no pings in a given range
        return -1;
    }

    // when there is more pings then expected lost percentage will be less then zero
    if ($lostPercentage < 0) {

        $lostPercentage = 0;
    }

    return 100.0 - $lostPercentage;
}


/**
 * @param $retailerId
 * @param $email
 * @param $password
 * @return ParseObject | null
 *
 * add Retailer to POS User
 * }
 * Fetches retailer name from Retailers and assigns as first name
 * and 'Tablet' as last name
 * Assigns the typeOfLogin of user as 't' with hasTabletPOSAccess to true
 *
 * add RetailerTabletUsers with respective Retailer and User
 *
 */
function createTabletUserForRetailer($retailerUniqueId, $email, $password, $comments, $isAdmin = false)
{

    $objRetailer = parseExecuteQuery(array("uniqueId" => $retailerUniqueId), "Retailers", "", "", [], 1);

    if (empty($objRetailer)) {

        return [false, "Retailer not found"];
    }

    // Check if retailer is of App type
    $objRetailerPOSConfig = parseExecuteQuery(array("retailer" => $objRetailer), "RetailerPOSConfig", "", "", [], 1, true);
    if (!empty($objRetailerPOSConfig)) {

        $printerId = $objRetailerPOSConfig->get('printerId');
        $locationId = $objRetailerPOSConfig->get('locationId');
        $tabletId = $objRetailerPOSConfig->get('tabletId');

        if (!empty($printerId)
            || !empty($locationId)
            || !empty($tabletId)
        ) {

            $tabletAppRetailer = false;
        } else {

            $tabletAppRetailer = true;
        }

        if (!$tabletAppRetailer) {

            // return [false, "Retailer not app type"];
        }
    } else {

        return [false, "Retailer has no POS Config row"];
    }

    if ($isAdmin) {

        $retailerUserType = 2;
    } else {

        $retailerUserType = 1;
    }

    if (!preg_match("/^ops\+/si", $email)
        && $isAdmin
    ) {

        return [false, "User can not be made Admin. Must have email address with ops+..."];
    }

    $type = "t";
    $email = strtolower(sanitizeEmail($email));
    $username = createUsernameFromEmail($email, $type);

    $userCreated = false;
    $objUser = parseExecuteQuery(["username" => $username], "_User", "", "", [], 1);
/*
    if (!preg_match("/\@airportsherpa.io$/si", $email)) {

        return [false, "User not allowed. Must use @airportsherpa.io email."];
    }
*/
    // Check if the user already exists?
    // Add retailer to the user's access list
    if (!empty($objUser)) {

        // Check if Retailer Tablet association already exists
        $objRetailerTabletUser = parseExecuteQuery(array("retailer" => $objRetailer, "tabletUser" => $objUser), "RetailerTabletUsers", "", "", [], 1);

        if (!empty($objRetailerTabletUser)) {

            return [true, "Association already exists"];
        } else {

            // Find if there are any rows for this user in the RetailerTabletUser
            $objRetailerTabletUser = parseExecuteQuery(array("retailer" => $objRetailer), "RetailerTabletUsers", "", "", [], 1);

            // if the user is not an admin, then error an exit as this is not supposed to have multiple retailers
            if ($objUser->get("retailerUserType") != 2
                && !empty($objRetailerTabletUser)
            ) {

                return [false, "User is not admin, but more than one retailer requested"];
            }
        }
    } // Create user
    else {

        $firstName = $isAdmin == true ? "Ops" : $objRetailer->get('retailerName');
        $lastName = "Tablet";

        $objUser = new ParseUser();
        $objUser->set("username", $username);
        $objUser->set("password", generatePasswordHash($password));
        $objUser->set("email", $email);
        $objUser->set("firstName", $firstName);
        $objUser->set("lastName", $lastName);
        // $objUser->set("emailVerified", true);
        $objUser->set("isActive", true);
        $objUser->set("isLocked", false);
        $objUser->set("isBetaActive", true);
        $objUser->set("airEmpValidUntilTimestamp", 0);
        $objUser->set("retailerUserType", $retailerUserType);
        $objUser->set("typeOfLogin", $type);

        // Get flags that should be set for access
        list($hasConsumerAccess, $hasDeliveryAccess, $hasTabletPOSAccess) = getAccountTypeFlags($type);

        $objUser->set("hasTabletPOSAccess", $hasTabletPOSAccess);
        $objUser->set("hasDeliveryAccess", false);
        $objUser->set("hasConsumerAccess", false);
        $objUser->signUp();

        $userCreated = true;
    }

    // Add retailer association
    $parseRetailerTabletUsers = new ParseObject("RetailerTabletUsers");

    $parseRetailerTabletUsers->set('retailer', $objRetailer);
    $parseRetailerTabletUsers->set('tabletUser', $objUser);
    $parseRetailerTabletUsers->set('comments', $comments);

    $parseRetailerTabletUsers->save();

    $errorMsg = $userCreated == true ? "User created, association added." : "User existed, association added";
    return [true, $errorMsg];
}

?>
