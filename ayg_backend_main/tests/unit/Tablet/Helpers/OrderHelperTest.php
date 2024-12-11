<?php
namespace tests\unit\Tablet\Services;

include __DIR__.'/../../../../lib/functions_orders.php';

use App\Tablet\Entities\Order;
use App\Tablet\Helpers\OrderHelper;
use \Mockery as M;

class IronMQServiceServiceTest extends \PHPUnit_Framework_TestCase
{

    public function testGetProperStatusName()
    {
        // status 4 pickup - Pending
        $status = OrderHelper::getTabletOrderStatusDisplay($this->getOrder(4, 0, 'p'));
        $this->assertEquals('Pending',$status);

        // status 5 pickup - Preparing
        $status = OrderHelper::getTabletOrderStatusDisplay($this->getOrder(5, 0, 'p'));
        $this->assertEquals('Preparing',$status);

        // status 10 pickup - Completed
        $status = OrderHelper::getTabletOrderStatusDisplay($this->getOrder(10, 0, 'p'));
        $this->assertEquals('Completed',$status);

        // status 6 pickup - Canceled
        $status = OrderHelper::getTabletOrderStatusDisplay($this->getOrder(6, 0, 'p'));
        $this->assertEquals('Canceled',$status);

        // status 4 delivery - Pending
        $status = OrderHelper::getTabletOrderStatusDisplay($this->getOrder(4, 0, 'd'));
        $this->assertEquals('Pending',$status);

        // status 5 delivery, delivery status 0 - Preparing
        $status = OrderHelper::getTabletOrderStatusDisplay($this->getOrder(5, 0, 'd'));
        $this->assertEquals('Preparing',$status);

        // status 5 delivery, delivery status 1 - Preparing
        $status = OrderHelper::getTabletOrderStatusDisplay($this->getOrder(5, 1, 'd'));
        $this->assertEquals('Preparing',$status);

        // status 5 delivery, delivery status 2 - Preparing
        $status = OrderHelper::getTabletOrderStatusDisplay($this->getOrder(5, 2, 'd'));
        $this->assertEquals('Preparing',$status);

        // status 5 delivery, delivery status 3 - Preparing
        $status = OrderHelper::getTabletOrderStatusDisplay($this->getOrder(5, 3, 'd'));
        $this->assertEquals('Preparing',$status);

        // status 5 delivery, delivery status 4 - Preparing
        $status = OrderHelper::getTabletOrderStatusDisplay($this->getOrder(5, 4, 'd'));
        $this->assertEquals('Preparing',$status);

        // status 5 delivery, delivery status 5 - Completed
        $status = OrderHelper::getTabletOrderStatusDisplay($this->getOrder(5, 5, 'd'));
        $this->assertEquals('Completed',$status);

        // status 5 delivery, delivery status 6 - Completed
        $status = OrderHelper::getTabletOrderStatusDisplay($this->getOrder(5, 6, 'd'));
        $this->assertEquals('Completed',$status);

        // status 6 delivery, delivery status 6 - Completed
        $status = OrderHelper::getTabletOrderStatusDisplay($this->getOrder(10, 10, 'd'));
        $this->assertEquals('Completed',$status);

        // status 10 delivery, delivery status 6 - Canceled
        $status = OrderHelper::getTabletOrderStatusDisplay($this->getOrder(6, 7, 'd'));
        $this->assertEquals('Canceled',$status);
    }


    public function testGetProperCategoryCodes()
    {
        /*
        const ORDER_STATUS_CATEGORY_RETAILER_PENDING = 100;
        const ORDER_STATUS_CATEGORY_RETAILER_ACCEPTED = 200;
        const ORDER_STATUS_CATEGORY_RETAILER_COMPLETED = 400;
        const ORDER_STATUS_CATEGORY_CANCELED = 600;
        const ORDER_STATUS_CATEGORY_OTHER = 900;
        */

        // status 4 pickup - Pending 100
        $status = OrderHelper::getOrderStatusCategoryCode($this->getOrder(4, 0, 'p'));
        $this->assertEquals('100',$status);

        // status 5 pickup - Preparing 200
        $status = OrderHelper::getOrderStatusCategoryCode($this->getOrder(5, 0, 'p'));
        $this->assertEquals('200',$status);

        // status 10 pickup - Completed 400
        $status = OrderHelper::getOrderStatusCategoryCode($this->getOrder(10, 0, 'p'));
        $this->assertEquals('400',$status);

        // status 6 pickup - Canceled 600
        $status = OrderHelper::getOrderStatusCategoryCode($this->getOrder(6, 0, 'p'));
        $this->assertEquals('600',$status);

        // status 4 delivery - Pending 100
        $status = OrderHelper::getOrderStatusCategoryCode($this->getOrder(4, 0, 'd'));
        $this->assertEquals('100',$status);

        // status 5 delivery, delivery status 0 - Preparing 200
        $status = OrderHelper::getOrderStatusCategoryCode($this->getOrder(5, 0, 'd'));
        $this->assertEquals('200',$status);

        // status 5 delivery, delivery status 1 - Preparing 200
        $status = OrderHelper::getOrderStatusCategoryCode($this->getOrder(5, 1, 'd'));
        $this->assertEquals('200',$status);

        // status 5 delivery, delivery status 2 - Preparing 200
        $status = OrderHelper::getOrderStatusCategoryCode($this->getOrder(5, 2, 'd'));
        $this->assertEquals('200',$status);

        // status 5 delivery, delivery status 3 - Preparing 200
        $status = OrderHelper::getOrderStatusCategoryCode($this->getOrder(5, 3, 'd'));
        $this->assertEquals('200',$status);

        // status 5 delivery, delivery status 4 - Preparing 200
        $status = OrderHelper::getOrderStatusCategoryCode($this->getOrder(5, 4, 'd'));
        $this->assertEquals('200',$status);

        // status 5 delivery, delivery status 5 - Completed 400
        $status = OrderHelper::getOrderStatusCategoryCode($this->getOrder(5, 5, 'd'));
        $this->assertEquals('400',$status);

        // status 5 delivery, delivery status 6 - Completed 400
        $status = OrderHelper::getOrderStatusCategoryCode($this->getOrder(5, 6, 'd'));
        $this->assertEquals('400',$status);

        // status 6 delivery, delivery status 6 - Completed 400
        $status = OrderHelper::getOrderStatusCategoryCode($this->getOrder(10, 10, 'd'));
        $this->assertEquals('400',$status);

        // status 10 delivery, delivery status 6 - Canceled 600
        $status = OrderHelper::getOrderStatusCategoryCode($this->getOrder(6, 7, 'd'));
        $this->assertEquals('600',$status);
    }

    private function getOrder($status, $deliveryStatus, $type)
    {
        return new Order([
            'id' => 'someOrderId',
            'interimOrderStatus' => '',
            'paymentType' => '',
            'paymentId' => '',
            'submissionAttempt' => '',
            'orderPOSId' => '',
            'totalsWithFees' => '',
            'etaTimestamp' => '',
            'coupon' => '',
            'statusDelivery' => $deliveryStatus,
            'tipPct' => '',
            'cancelReason' => '',
            'quotedFullfillmentFeeTimestamp' => '',
            'fullfillmentType' => $type,
            'ACL' => '',
            'invoicePDFURL' => '',
            'orderSequenceId' => '',
            'totalsForRetailer' => '',
            'paymentTypeName' => '',
            'fullfillmentProcessTimeInSeconds' => '',
            'updatedAt' => '',
            'quotedFullfillmentPickupFee' => '',
            'status' => $status,
            'fullfillmentFee' => '',
            'requestedFullFillmentTimestamp' => '',
            'orderPrintJobId' => '',
            'deliveryInstructions' => '',
            'quotedFullfillmentDeliveryFee' => '',
            'createdAt' => '',
            'totalsFromPOS' => '',
            'paymentTypeId' => '',
            'submitTimestamp' => '',
            'comment' => '',
            'retailer' => '',
        ]);
    }
}