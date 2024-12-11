<?php

ini_set("memory_limit", "384M"); // Max 512M

define("WORKER", true);
define("QUEUE", true);
define("QUEUE_WORKER", true);

require_once 'dirpath.php';
require_once $dirpath . 'lib/initiate.inc.php';
require_once $dirpath . 'lib/errorhandlers_scheduled.php';
require_once $dirpath . 'lib/initiate.mysql_logs.php';

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
require_once $dirpath . 'lib/functions_order_ops.php';

use App\Consumer\Helpers\ConfigHelper;
use App\Consumer\Services\CacheService;
use App\Consumer\Services\UserCreditServiceFactory;
use Predis\Client;

$message = [
    "action" => "order_ops_cancel_admin_request",
    "content" => [
        "orderId" => "lQQMEnmUMh",
        "cancelOptions" => json_encode([
            "cancelReasonCode" => "101",
            "cancelReason" => "Delivery, Concession out of items to complete order",
            "refundType" => "source",
            "partialRefundAmount" => "0",
            "refundRetailer" => "0"
        ])
    ]
];
$workerQueue = null;

queue__order_ops_cancel_admin_request($message, $workerQueue);
