<?php
$allowedOrigins = [
    "http://ayg-deb.test",
    "https://ayg.ssasoft.com",
    "http://ec2-18-116-237-65.us-east-2.compute.amazonaws.com",
    "http://ec2-18-190-155-186.us-east-2.compute.amazonaws.com", // test
    "https://order.atyourgate.com/", // prod
];

if (isset($_SERVER["HTTP_REFERER"]) && in_array(trim($_SERVER["HTTP_REFERER"],'/'), $allowedOrigins)) {
    header("Access-Control-Allow-Origin: " . trim($_SERVER["HTTP_REFERER"],'/'));
}


use App\Tablet\Errors\ErrorPrefix;
use App\Tablet\Errors\IncorrectApiCallError;
use App\Tablet\Responses\Response;

require 'dirpath.php';

require __DIR__ . '/../../lib/initiate.inc.php';
require __DIR__ . '/../../lib/errorhandlers.php';

use App\Tablet\Helpers\QueueMessageHelper;

/**
 * https://airportsherpa.atlassian.net/browse/MVP-1268
 * Tablet SignIn functionality
 * Can be call only by tablet user
 */
$app->post('/user/signin/a/:apikey/e/:epoch/u/:sessionToken',
    \App\Tablet\Middleware\ApiMiddleware::class . '::apiAuthWithoutSession',
    \App\Tablet\Middleware\UserSignInMiddleware::class . '::validate',
    \App\Tablet\Controllers\UserController::class . ':signIn'
);

/**
 * https://airportsherpa.atlassian.net/browse/MVP-1268
 * Tablet SignOut functionality
 * Can be call only by tablet user
 */
$app->post('/user/signout/a/:apikey/e/:epoch/u/:sessionToken',
    \App\Tablet\Middleware\ApiMiddleware::class . '::apiAuth',
    \App\Tablet\Controllers\UserController::class . ':signOut'
);

/*
 * Jira Ticket - https://airportsherpa.atlassian.net/browse/MVP-1269
 * Gets active Orders
 * Can be call only by tablet user
 */
$app->get('/order/getActiveOrders/a/:apikey/e/:epoch/u/:sessionToken/page/:page/limit/:limit',
    \App\Tablet\Middleware\ApiMiddleware::class . '::apiAuth',
    \App\Tablet\Controllers\OrderController::class . ':getActiveOrders'
);

/*
 * Jira Ticket - https://airportsherpa.atlassian.net/browse/MVP-1272
 * Gets past Orders
 * Can be call only by tablet user
 */
$app->get('/order/getPastOrders/a/:apikey/e/:epoch/u/:sessionToken/page/:page/limit/:limit',
    \App\Tablet\Middleware\ApiMiddleware::class . '::apiAuth',
    \App\Tablet\Controllers\OrderController::class . ':getPastOrders'
);

/*
 * Jira Ticket - https://airportsherpa.atlassian.net/browse/MVP-1270
 * Retailer request help for an Order
 * Can be call only by tablet user
 */
$app->post('/order/helpRequest/a/:apikey/e/:epoch/u/:sessionToken',
    \App\Tablet\Middleware\ApiMiddleware::class . '::apiAuth',
    \App\Tablet\Middleware\OrderHelpRequestMiddleware::class . '::validate',
    \App\Tablet\Controllers\OrderController::class . ':requestHelp'
);

/*
 * Jira Ticket - https://airportsherpa.atlassian.net/browse/MVP-1271
 * Retailers confirm the order from Consumer
 * Can be call only by tablet user
 */
$app->get('/order/confirm/a/:apikey/e/:epoch/u/:sessionToken/orderId/:orderId',
    \App\Tablet\Middleware\ApiMiddleware::class . '::apiAuth',
    \App\Tablet\Controllers\OrderController::class . ':confirm'
);



/*
* Jira Ticket - https://airportsherpa.atlassian.net/browse/RET-60
* Close business
*/
$app->get('/user/closeBusiness/a/:apikey/e/:epoch/u/:sessionToken',
        \App\Tablet\Middleware\ApiMiddleware::class . '::apiAuth',
   \App\Tablet\Controllers\UserController::class . ':closeBusiness'
);

/*
* Jira Ticket - https://airportsherpa.atlassian.net/browse/RET-60
* Close business
*/
$app->get('/user/reopenBusiness/a/:apikey/e/:epoch/u/:sessionToken',
     \App\Tablet\Middleware\ApiMiddleware::class . '::apiAuth',
   \App\Tablet\Controllers\UserController::class . ':reopenBusiness'
);


$app->notFound(function () {
    (new Response(null, null, new IncorrectApiCallError(
        ErrorPrefix::APPLICATION_TABLET . ErrorPrefix::CONTROLLER_MIDDLEWARE
    )))->returnJson();
});
$app->run();

?>
