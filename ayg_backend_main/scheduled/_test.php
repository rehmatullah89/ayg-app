
<?php

ini_set("memory_limit","384M"); // Max 512M

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




unset($x);
$x['action']="order_ops_cancel_admin_request";
$x['content']['orderId']="XvKHmoxD2j";
$x['content']['cancelOptions']['cancelReasonCode']=110;
$x['content']['cancelOptions']['cancelReason']='delivery, several items prepared incorrectly';
$x['content']['cancelOptions']['refundType']='source';
$x['content']['cancelOptions']['partialRefundAmount']="0";
$x['content']['cancelOptions']['refundRetailer']="0";
$x['content']['cancelOptions'] = json_encode($x['content']['cancelOptions']);
// {"action":"order_ops_cancel_admin_request","content":{"orderId":"Si8VoWfQNh","cancelOptions":"
//{\"cancelReasonCode\":\"190\",\"cancelReason\":\"test order\",\"refundType\":\"source\",\"partialRefundAmount\":\"0\",\"refundRetailer\":\"0\"}"},"__rabbit
var_dump($x);

$z= null;
queue__order_ops_cancel_admin_request($x, $z);

die();
/*

{"action":"order_ops_cancel_admin_request","content":{"orderId":"WPP2pSN5Aq","cancelOptions":"{\"cancelReasonCode\":\"101\",\"cancelReason\":\"Delivery, out of chicken for order\",
\"refundType\":\"source\",\"partialRefundAmount\":\"0\",\"refundRetailer\":\"0\"}"},"_
_rabbitmq__send_timestamp":1639446230485.1501,"__rabbitmq__msg_id":"7b3562e14a85fd0b1bf054cd11296f6c","__rabbitmq__delivery_cnt":8}

*/
die();

$message["content"]["orderId"]='caGg38MvJm';
$z= null;

queue__order_email_receipt($message,$z);

die();


$message["content"]["orderId"]='dStJMLetGD';
$z= null;

queue__order_delivery_assign_delivery($message,$z);


$x['action']="order_ops_cancel_admin_request";
$x['content']['orderId']="Z18hA3t77s";
$x['content']['cancelOptions']['cancelReasonCode']=21;
$x['content']['cancelOptions']['cancelReason']='testesttest';
$x['content']['cancelOptions']['refundType']='fullcredit';
$x['content']['cancelOptions']['partialRefundAmount']="0";
$x['content']['cancelOptions']['refundRetailer']="0";

$x['content']['cancelOptions'] = json_encode($x['content']['cancelOptions']);


var_dump($x);

$z= null;
queue__order_ops_cancel_admin_request($x, $z);
