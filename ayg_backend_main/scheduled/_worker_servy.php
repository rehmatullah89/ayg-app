<?php

use App\Consumer\Dto\PartnerIntegration\CartItemList;
use App\Consumer\Helpers\DateTimeHelper;

$_SERVER['REQUEST_METHOD'] = '';
$_SERVER['REMOTE_ADDR'] = '';
$_SERVER['REQUEST_URI'] = '';
$_SERVER['SERVER_NAME'] = '';



ini_set("memory_limit","384M"); // Max 512M

define("WORKER", true);
define("QUEUE", true);

require_once 'dirpath.php';
require_once $dirpath . 'lib/initiate.inc.php';
require_once $dirpath . 'lib/errorhandlers_scheduled.php';
require_once $dirpath . 'scheduled/_process_orders.php';
require_once $dirpath . 'scheduled/_confirm_print_orders.php';

require_once $dirpath . 'scheduled/_confirm_pos_orders.php';
require_once $dirpath . 'scheduled/_confirm_tablet_orders.php';
require_once $dirpath . 'scheduled/_send_order_receipt.php';
require_once $dirpath . 'scheduled/_process_delivery.php';
require_once $dirpath . 'scheduled/_send_email.php';
require_once $dirpath . 'scheduled/_create_onesignal_device.php';
require_once $dirpath . 'scheduled/_queue_functions.php';
require_once $dirpath . 'scheduled/_ping_retailers.php';
require_once $dirpath . 'scheduled/_ping_slack_delivery.php';
require_once $dirpath . 'scheduled/_process_delivery_slack.php';
require_once $dirpath . 'scheduled/_worker_functions.php';
require_once $dirpath . 'scheduled/_send_user_communication.php';
require_once $dirpath . 'scheduled/_process_flight.php';

$orderId = 'dd9zEX9fo3';


$orderObject = parseExecuteQuery(array(
    "objectId" => $orderId,
    "status" => listStatusesForCart()
), "Order", "", "", array(
    "retailer",
    "retailer.location",
    "retailer.retailerType",
    "deliveryLocation",
    "coupon",
    "coupon.applicableUser",
    "user"
), 1);

$retailerPartnerServiceFactory = new  \App\Consumer\Services\PartnerIntegrationServiceFactory(
    new \App\Consumer\Repositories\RetailerPartnerCacheRepository(
        new \App\Consumer\Repositories\RetailerPartnerParseRepository(),
        \App\Consumer\Services\CacheServiceFactory::create()
    )
);
$retailerPartnerService = $retailerPartnerServiceFactory->createByRetailerUniqueId($orderObject->get('retailer')->get('uniqueId'));

$partnerRetailer = $retailerPartnerService->getPartnerIdByRetailerUniqueId($orderObject->get('retailer')->get('uniqueId'));
$airportTimeZone = fetchAirportTimeZone($orderObject->get('retailer')->get('airportIataCode'));

$fullfillmentType = 'd';
list($orderSummaryArray, $retailerTotals) = getOrderSummary($orderObject, 0, true, 0, true, $fullfillmentType);

$cart = new \App\Consumer\Dto\PartnerIntegration\Cart(
    new \App\Consumer\Dto\PartnerIntegration\CartUserDetails(
        'test',
        'surname'
    ),
    $partnerRetailer->getPartnerId(),
    CartItemList::createFromGetOrderSummaryItemListResult(
        $orderSummaryArray["items"]
    ),
    new \DateTimeZone($airportTimeZone),
    $orderSummaryArray['totals']['subTotal'],
    false,
    true
);

$cartTotals = $retailerPartnerService->getCartTotals($cart);
//$saveOrderResultArray = $retailerPartnerService->submitOrder($cart, $cartTotals);

try {
    $saveOrderResultArray = $retailerPartnerService->submitOrderAsGuest($cart, $cartTotals);
    $saveOrderResult = \App\Consumer\Entities\Partners\Grab\SaveOrderResult::createFromApiResult($saveOrderResultArray);
    $partnerId = $saveOrderResult->getOrderID();
}catch (\App\Consumer\Exceptions\Partners\OrderCanNotBeSaved $exception){
    $partnerId='';

    $slack = createOrderNotificationSlackMessage($orderObject->getObjectId());
    $slack->setText(
        $exception->getMessage().PHP_EOL.
        'REQUEST: ' .$exception->getInputPayload().PHP_EOL.
        'URL: ' .$exception->getUrl().PHP_EOL
    );
    $slack->send();


    decrementSubmissionAttempt($orderObject);
    json_error("AS_896", "We are sorry, but the retailer is currently not accepting orders.",
        "Failed to get totals from Grab, Order Id: " . $orderId . " - " . $exception->getMessage(), 1);
}
