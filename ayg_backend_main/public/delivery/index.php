<?php

$corsPolicyHasBeenSet = false;
$allowedOrigins = [
    "https://ayg.ssasoft.com",
    "http://ayg-deb.test"
];
if (isset($_SERVER["HTTP_REFERER"]) && in_array(trim($_SERVER["HTTP_REFERER"], '/'), $allowedOrigins)) {
    header("Access-Control-Allow-Origin: " . trim($_SERVER["HTTP_REFERER"], '/'));
    $corsPolicyHasBeenSet = true;
}

if (!$corsPolicyHasBeenSet) {
    if (isset($_SERVER["HTTP_ORIGIN"]) && $_SERVER["HTTP_ORIGIN"]=='capacitor://localhost'){
        header("Access-Control-Allow-Origin: capacitor://localhost");
    }
}


use App\Delivery\Errors\ErrorPrefix;
use App\Delivery\Errors\IncorrectApiCallError;
use App\Delivery\Responses\Response;

require 'dirpath.php';

require __DIR__ . '/../../lib/initiate.inc.php';
require __DIR__ . '/../../lib/errorhandlers.php';



$app->post('/user/signin/a/:apikey/e/:epoch/u/:sessionToken',
    \App\Delivery\Middleware\ApiMiddleware::class . '::apiAuthWithoutSession',
    \App\Delivery\Middleware\UserSignInMiddleware::class . '::validate',
    \App\Delivery\Controllers\UserController::class . ':signIn'
);

$app->post('/user/signout/a/:apikey/e/:epoch/u/:sessionToken',
    \App\Delivery\Middleware\ApiMiddleware::class . '::apiAuth',
    \App\Delivery\Controllers\UserController::class . ':signOut'
);

$app->get('/order/active/a/:apikey/e/:epoch/u/:sessionToken/page/:page/limit/:limit',
    \App\Delivery\Middleware\ApiMiddleware::class . '::apiAuth',
    \App\Delivery\Controllers\OrderController::class . ':getActiveOrders'
);

$app->get('/order/completed/a/:apikey/e/:epoch/u/:sessionToken/page/:page/limit/:limit',
    \App\Delivery\Middleware\ApiMiddleware::class . '::apiAuth',
    \App\Delivery\Controllers\OrderController::class . ':getCompletedOrders'
);

$app->get('/order/:orderId/a/:apikey/e/:epoch/u/:sessionToken',
    \App\Delivery\Middleware\ApiMiddleware::class . '::apiAuth',
    \App\Delivery\Controllers\OrderController::class . ':getOrderDetails'
);

$app->post('/order/:orderId/addComment/a/:apikey/e/:epoch/u/:sessionToken',
    \App\Delivery\Middleware\ApiMiddleware::class . '::apiAuth',
    \App\Delivery\Middleware\DeliveryAddCommentMiddleware::class . '::validate',
    \App\Delivery\Controllers\OrderController::class . ':addComment'
);

$app->post('/order/:orderId/changeStatus/a/:apikey/e/:epoch/u/:sessionToken',
    \App\Delivery\Middleware\ApiMiddleware::class . '::apiAuth',
    \App\Delivery\Middleware\DeliveryStatusChangeMiddleware::class . '::validate',
    \App\Delivery\Controllers\OrderController::class . ':changeStatus'
);


$app->notFound(function () {
    (new Response(null, null, new IncorrectApiCallError(
        ErrorPrefix::APPLICATION_DELIVERY . ErrorPrefix::CONTROLLER_MIDDLEWARE
    )))->returnJson();
});
$app->run();

?>
