<?php

require_once 'dirpath.php';
require_once $dirpath . 'lib/initiate.inc.php';
require_once $dirpath . 'lib/errorhandlers_scheduled.php';

use App\Consumer\Entities\User;
use Parse\ParseClient;
use Parse\ParseQuery;
use Parse\ParseObject;
use Parse\ParseUser;
use Parse\ParseFile;
use Httpful\Request;


$email = 'chris+refer@atyourgate.com';
$code = 'Z243M';

$parseUser = parseExecuteQuery(array("email" => $email), "_User", "", "", [], 1);
$GLOBALS['user'] = clone($parseUser);


$x = new User([
    'id' => $parseUser->getObjectId(),
    'email' => $parseUser->get('email'),
    'firstName' => $parseUser->get('firstName'),
    'lastName' => $parseUser->get('lastName'),
    'profileImage' => $parseUser->get('profileImage'),
    'airEmpValidUntilTimestamp' => $parseUser->get('airEmpValidUntilTimestamp'),
    'emailVerified' => $parseUser->get('emailVerified'),
    'typeOfLogin' => $parseUser->get('typeOfLogin'),
    'username' => $parseUser->get('username'),
    'hasConsumerAccess' => $parseUser->get('hasConsumerAccess'),
]);




$userController = new \App\Consumer\Controllers\UserController();


$userController->addCouponForSignup($x,$code);

?>
