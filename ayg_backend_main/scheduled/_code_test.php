<?php

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

use Parse\ParseClient;
use Parse\ParseQuery;
use Parse\ParseObject;
use Parse\ParseUser;
use Parse\ParseFile;
use Httpful\Request;
use App\Delivery\Services\OrderPushNotificationService;
use App\Delivery\Services\OrderPushNotificationServiceFactory;
use App\Tablet\Services\QueueServiceFactory;
///////////////////////////////////////////////////////////////////////////////
// Check if 9001 request came in
///////////////////////////////////////////////////////////////////////////////

$userQry = new ParseQuery('_User');
$userQry->equalTo('objectId', 'bghyFTQJr7');
$user = $userQry->find();

$service = OrderPushNotificationServiceFactory::create();
$service->sendPushNotification('JFK', ["title" => "Hello", "text" => "This is some test message", "data" => ["deepLinkId" => 'push_notification']],false, $user[0]);


function handle9001(){

}

?>
