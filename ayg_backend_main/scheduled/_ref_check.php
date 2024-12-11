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


$userId = '6PGvBRS1f4';
//Referral Reward - YYMLOtoIUe -
$q = new \Parse\ParseQuery('UserCredits');
$q->equalTo('user', new \Parse\ParseObject('_User', '6PGvBRS1f4'));
$q->startsWith('reasonForCredit', 'Referral Reward');
$q->includeKey('fromOrder');
$r = $q->find(true);
$iii = 1;
foreach ($r as $i) {
    echo $iii . ',';

    $id = $i->get('reasonForCredit');
    $id = str_replace('Referral Reward - ', '', $id);
    $id = str_replace(' -', '', $id);
    $id = trim($id);


    echo $id;
    echo ',';

    $qq = new \Parse\ParseQuery('SessionDevices');
    $qq->equalTo('user', new \Parse\ParseObject('_User', $id));
    $qq->descending('createdAt');
    $re = $qq->first(true);
    if (!empty($re)) {
        echo str_replace('~ ', '', $re->get('IPAddress'));

        echo ',';

        $qqq = new \Parse\ParseQuery('Order');
        $qqq->equalTo('objectId', $i->get('fromOrder')->getObjectId());
        $qqq->includeKey('coupon');

        $ee = $qqq->first(true);


        if (!empty($ee)) {
            $ee->fetch();
            if ($ee->get('coupon')!==null){
                $ee->get('coupon')->fetch();
                $couponCode = $ee->get('coupon')->get('couponCode');
                $couponCode = trim($couponCode,'_');
                $couponCode = trim($couponCode,'_');
                $couponCode = trim($couponCode,' ');
                echo $couponCode;
            }
        } else {
        }

    } else {
        echo ',';
    }

    echo PHP_EOL;
    $iii++;
}
