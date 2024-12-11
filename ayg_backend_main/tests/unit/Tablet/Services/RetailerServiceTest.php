<?php
namespace tests\unit\Tablet\Services;

use App\Tablet\Entities\RetailerPingInfo;
use App\Tablet\Repositories\RetailerPOSConfigRepositoryInterface;
use App\Tablet\Repositories\RetailerRepositoryInterface;
use App\Tablet\Services\CacheService;
use App\Tablet\Services\RetailerService;
use App\Tablet\Services\RetailerServiceFactory;
use \Mockery as M;

class RetailerServiceTest extends \PHPUnit_Framework_TestCase
{
    public function testInfoServiceCanBeCreatedByFactory()
    {
        $cacheServiceMock = M::mock(CacheService::class);
        $retailerService = RetailerServiceFactory::create($cacheServiceMock);
        $this->assertInstanceOf(RetailerService::class, $retailerService);
    }

    /**
     * @covers RetailerService::getRetailerPingInfo()
     */
    public function testRetailerServiceCanGetRetailerPingInfo()
    {
        $retailerRepositoryMock = M::mock(RetailerRepositoryInterface::class);
        $retailerPOSConfigRepositoryMock = M::mock(RetailerPOSConfigRepositoryInterface::class);

        $infoService = new RetailerService(
            $retailerRepositoryMock,
            $retailerPOSConfigRepositoryMock
        );
        $result = $infoService->getRetailerPingInfo();

        $this->assertInstanceOf(RetailerPingInfo::class, $result);
    }
}