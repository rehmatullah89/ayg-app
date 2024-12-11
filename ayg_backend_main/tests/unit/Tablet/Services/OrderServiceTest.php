<?php


namespace tests\unit\Tablet\Services;


use App\Tablet\Entities\Order;
use App\Tablet\Entities\OrderShortInfo;
use App\Tablet\Entities\OrderTabletHelpRequest;
use App\Tablet\Entities\Retailer;
use App\Tablet\Entities\TerminalGateMap;
use App\Tablet\Exceptions\Exception;
use App\Tablet\Repositories\OrderModifierRepositoryInterface;
use App\Tablet\Repositories\OrderRepositoryInterface;
use App\Tablet\Repositories\OrderTabletHelpRequestsRepositoryInterface;
use App\Tablet\Repositories\RetailerItemModifierOptionRepositoryInterface;
use App\Tablet\Repositories\RetailerItemModifierRepositoryInterface;
use App\Tablet\Repositories\RetailerRepositoryInterface;
use App\Tablet\Services\CacheService;
use App\Tablet\Services\LoggingService;
use App\Tablet\Services\OrderService;
use App\Tablet\Services\OrderServiceFactory;
use App\Tablet\Services\QueueServiceInterface;
use App\Tablet\Services\SlackOrderHelpRequestService;
use \Mockery as M;

require_once __DIR__ . '/../../../../putenv.php';


date_default_timezone_set('America/New_York');


class OrderServiceTest extends \PHPUnit_Framework_TestCase
{
    public function testOrderServiceCanBeCreatedByFactory()
    {
        $cacheServiceMock = M::mock(CacheService::class);
        $infoService = OrderServiceFactory::create($cacheServiceMock);
        $this->assertInstanceOf(OrderService::class, $infoService);
    }

    /**
     * @covers OrderService::confirm()
     */
    public function testOrderServiceCanConfirm()
    {
        // not testable due to
        // parseExecuteQuery in functions_directions.php on line 147

        /*
        $retailerRepositoryMock = M::mock(RetailerRepositoryInterface::class);
        $retailer = $this->createEmptyRetailer();
        $retailerRepositoryMock->shouldReceive('getByUserId')->andReturn([$retailer, $retailer]);


        $retailerItemModifierOptionRepositoryMock = M::mock(RetailerItemModifierOptionRepositoryInterface::class);
        $orderRepositoryMock = M::mock(OrderRepositoryInterface::class);
        $order = $this->createEmptyOrder();
        $order->setStatus(4);
        $orderRepositoryMock->shouldReceive('getOrderWithUserRetailerAndLocationByIdAndRetailerIdList')->andReturn($order);
        $orderRepositoryMock->shouldReceive('changeStatusToAcceptedByRetailer')->andReturn($order);

        $orderModifierRepositoryMock = M::mock(OrderModifierRepositoryInterface::class);
        $orderTabletHelpRequestsRepositoryMock = M::mock(OrderTabletHelpRequestsRepositoryInterface::class);
        $slackOrderHelpRequestServiceMock = M::mock(SlackOrderHelpRequestService::class);
        $queueServiceInterfaceMock = M::mock(QueueServiceInterface::class);
        $queueServiceInterfaceMock->shouldReceive('sendMessage')->andReturn(true);


        $orderService = new OrderService(
            $retailerItemModifierOptionRepositoryMock,
            $orderRepositoryMock,
            $orderModifierRepositoryMock,
            $orderTabletHelpRequestsRepositoryMock,
            $slackOrderHelpRequestServiceMock,
            $queueServiceInterfaceMock
        );

        $result = $orderService->confirm([$retailer], 'orderId');

        $this->assertInstanceOf(OrderShortInfo::class, $result);
        */
    }


    /**
     * @covers OrderService::saveHelpRequest()
     */
    public function testOrderServiceCanSaveHelpRequest()
    {
        // not testable due to
        // parseExecuteQuery in functions_directions.php on line 147

        /*

        $retailerRepositoryMock = M::mock(RetailerRepositoryInterface::class);
        $retailer = $this->createEmptyRetailer();
        $retailerRepositoryMock->shouldReceive('getByUserId')->andReturn([$retailer, $retailer]);


        $retailerItemModifierOptionRepositoryMock = M::mock(RetailerItemModifierOptionRepositoryInterface::class);

        $orderRepositoryMock = M::mock(OrderRepositoryInterface::class);
        $orderRepositoryMock->shouldReceive('getOrderWithUserRetailerAndLocationByIdAndRetailerIdList')->andReturn($this->createEmptyOrder());

        $orderModifierRepositoryMock = M::mock(OrderModifierRepositoryInterface::class);

        $orderTabletHelpRequestsRepositoryMock = M::mock(OrderTabletHelpRequestsRepositoryInterface::class);
        $orderTabletHelpRequest = $this->createEmptyOrderHelpRequest();
        $orderTabletHelpRequestsRepositoryMock->shouldReceive('add')->andReturn($orderTabletHelpRequest);


        $slackOrderHelpRequestServiceMock = M::mock(SlackOrderHelpRequestService::class);
        $slackOrderHelpRequestServiceMock->shouldReceive('sendSlackMessage')->andReturn(true);

        $queueServiceInterfaceMock = M::mock(QueueServiceInterface::class);


        $orderService = new OrderService(
            $retailerItemModifierOptionRepositoryMock,
            $orderRepositoryMock,
            $orderModifierRepositoryMock,
            $orderTabletHelpRequestsRepositoryMock,
            $slackOrderHelpRequestServiceMock,
            $queueServiceInterfaceMock
        );

        $result = $orderService->saveHelpRequest([$retailer], 'orderId', 'some comment');

        $this->assertTrue($result);

        */
    }

    /**
     * @covers OrderService::saveHelpRequest()
     * @expectedException     Exception
     */
    public function testOrderServiceCanNotSaveHelpRequestForBadOrderId()
    {
        $retailerItemModifierRepositoryMock = M::mock(RetailerItemModifierRepositoryInterface::class);

        $retailerItemModifierOptionRepositoryMock = M::mock(RetailerItemModifierOptionRepositoryInterface::class);

        $retailerRepositoryMock = M::mock(RetailerRepositoryInterface::class);
        $retailer = $this->createEmptyRetailer();
        $retailerRepositoryMock->shouldReceive('getByUserId')->andReturn([$retailer, $retailer]);

        $orderRepositoryMock = M::mock(OrderRepositoryInterface::class);

        $orderModifierRepositoryMock = M::mock(OrderModifierRepositoryInterface::class);

        $orderTabletHelpRequestsRepositoryMock = M::mock(OrderTabletHelpRequestsRepositoryInterface::class);
        $orderTabletHelpRequest = $this->createEmptyOrderHelpRequest();
        $orderTabletHelpRequestsRepositoryMock->shouldReceive('add')->andReturn($orderTabletHelpRequest);


        $slackOrderHelpRequestServiceMock = M::mock(SlackOrderHelpRequestService::class);
        $slackOrderHelpRequestServiceMock->shouldReceive('sendSlackMessage')->andReturn(true);

        $queueServiceInterfaceMock = M::mock(QueueServiceInterface::class);

        $cacheServiceMock = M::mock(CacheService::class);

        $loggingServiceMock = M::mock(LoggingService::class);

        $orderService = new OrderService(
            $retailerItemModifierRepositoryMock,
            $retailerItemModifierOptionRepositoryMock,
            $orderRepositoryMock,
            $orderModifierRepositoryMock,
            $orderTabletHelpRequestsRepositoryMock,
            $slackOrderHelpRequestServiceMock,
            $queueServiceInterfaceMock,
            $cacheServiceMock,
            $loggingServiceMock
        );
        $result = $orderService->saveHelpRequest([$retailer], 'orderId', 'some comment');

        $this->assertInstanceOf(OrderShortInfo::class, $result);
    }

    public function testOrderServiceCanGetActiveOrdersPaginatedByTabletUserId()
    {

        // not testable due to
        // parseExecuteQuery in functions_directions.php on line 147
        /*
        $retailerRepositoryMock = M::mock(RetailerRepositoryInterface::class);
        $retailer = $this->createEmptyRetailer();
        $retailerRepositoryMock->shouldReceive('getByUserId')->andReturn([$retailer,$retailer]);

        $retailerItemModifierOptionRepositoryMock = M::mock(RetailerItemModifierOptionRepositoryInterface::class);
        $orderRepositoryMock = M::mock(OrderRepositoryInterface::class);
        $order = $this->createEmptyOrder();
        $orderRepositoryMock->shouldReceive('getActiveOrdersCountByRetailerIdList')->andReturn(5);
        $orderRepositoryMock->shouldReceive('getActiveOrdersListByRetailerIdListPaginated')->andReturn([$order]);


        $orderModifierRepositoryMock = M::mock(OrderModifierRepositoryInterface::class);

        $orderService = new OrderService(
            $retailerRepositoryMock,
            $retailerItemModifierOptionRepositoryMock,
            $orderRepositoryMock,
            $orderModifierRepositoryMock
        );

        $result = $orderService->getActiveOrdersPaginatedByTabletUserId('someId', 2, 1);

        // service only calls repository,
        // result of service method should be the same like repository's method
        //$this->assertEquals($resultOfHelloWorldRepositoryGetById, $result);
        */
    }

    private function createEmptyOrder()
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
            'statusDelivery' => '',
            'tipPct' => '',
            'cancelReason' => '',
            'quotedFullfillmentFeeTimestamp' => '',
            'fullfillmentType' => '',
            'ACL' => '',
            'invoicePDFURL' => '',
            'orderSequenceId' => '',
            'totalsForRetailer' => '',
            'paymentTypeName' => '',
            'fullfillmentProcessTimeInSeconds' => '',
            'updatedAt' => '',
            'quotedFullfillmentPickupFee' => '',
            'status' => '',
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
            'retailer' => $this->createEmptyRetailer(),
        ]);
    }

    private function createEmptyRetailer()
    {
        return new Retailer([
            'id' => 'SomeRetailerId',
            'retailerType' => '',
            'retailerPriceCategory' => '',
            'locationId' => '',
            'searchTags' => '',
            'imageLogo' => '',
            'closeTimesSaturday' => '',
            'closeTimesThursday' => '',
            'closeTimesWednesday' => '',
            'imageBackground' => '',
            'retailerFoodSeatingType' => '',
            'ACL' => '',
            'openTimesSunday' => '',
            'openTimesMonday' => '',
            'closeTimesFriday' => '',
            'hasDelivery' => '',
            'retailerCategory' => '',
            'updatedAt' => '',
            'isActive' => true,
            'openTimesTuesday' => '',
            'openTimesSaturday' => '',
            'openTimesThursday' => '',
            'uniqueId' => '',
            'hasPickup' => '',
            'isChain' => '',
            'openTimesWednesday' => '',
            'createdAt' => '',
            'retailerName' => '',
            'openTimesFriday' => '',
            'description' => '',
            'airportIataCode' => '',
            'closeTimesMonday' => '',
            'closeTimesSunday' => '',
            'closeTimesTuesday' => '',
            'lastPing' => '',
            'location' => new TerminalGateMap([
                'id' => '',
                'createdAt' => '',
                'updatedAt' => '',
                'airportIataCode' => '',
                'concourse' => '',
                'displaySequence' => '',
                'gate' => '',
                'geoPointLocation' => '',
                'locationDisplayName' => '',
                'terminal' => '',
                'uniqueId' => '',
                'gateDisplayName' => '',
                'isDefaultLocation' => '',
            ])
        ]);
    }


    private function createEmptyOrderHelpRequest()
    {
        return new OrderTabletHelpRequest([
            'id' => 'SomeRetailerId',
            'order' => 'someOrder',
            'content' => 'some content',
        ]);
    }
}