<?php
namespace tests\unit\Consumer\Services;

use App\Consumer\Repositories\OrderRepositoryInterface;
use App\Consumer\Services\OrderService;
use App\Consumer\Services\OrderServiceFactory;
use \Mockery as M;

class OrderServiceTest extends \PHPUnit_Framework_TestCase
{
    public function testOrderServiceCanBeCreatedByFactory()
    {
        $orderService = OrderServiceFactory::create();
        $this->assertInstanceOf(OrderService::class, $orderService);
    }

    public function testOrderServiceAddOrderRatingWithFeedback()
    {
        $orderRepositoryMock = M::mock(OrderRepositoryInterface::class);
        $resultByMock = true;
        $orderRepositoryMock->shouldReceive('addOrderRatingWithFeedback')->andReturn($resultByMock);

        $orderService = new OrderService($orderRepositoryMock);
        $result = $orderService->addOrderRatingWithFeedback(123, 1, "this is test");

        // service only calls repository,
        // result of service method should be the same like repository's method
        $this->assertEquals($resultByMock, $result);
    }
}