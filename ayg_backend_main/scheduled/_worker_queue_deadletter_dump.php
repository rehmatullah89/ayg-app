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

use Parse\ParseClient;
use Parse\ParseQuery;
use Parse\ParseObject;
use Parse\ParseUser;
use Parse\ParseFile;
use Httpful\Request;
use PhpAmqpLib\Connection\AMQPStreamConnection;

$connection = new AMQPStreamConnection('shrimp.rmq.cloudamqp.com', 5672, 'iqeakqaf', '64KQD_yuHvceM5YLyTWmwxL5I4XTmzZL',
    'iqeakqaf');

$channel = $connection->channel();


$i = 0;

$callback = function ($msg) {

    global $i;
    $id = time() . '_' . $i;
    $i++;
    echo ' [x] Received ', $id, "\n";


	if (!file_exists(__DIR__ . '/../storage/queue_deadletter')) {
        mkdir(__DIR__ . '/../storage/queue_deadletter');
    }
	file_put_contents(__DIR__ . '/../storage/queue_deadletter/' . $id . '.json', $msg->body);

	echo ' [x] Received ', $msg->body, "\n";
};

$channel->basic_consume('prim-rabbitmq-consumer-deadletter', '', false, true, false, false, $callback);


while ($channel->is_open()) {
    $channel->wait();
}


?>
