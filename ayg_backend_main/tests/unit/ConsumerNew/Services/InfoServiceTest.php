<?php
namespace tests\unit\Consumer\Services;

require_once __DIR__ . '/../../../../' . 'putenv.php';

use App\Consumer\Repositories\HelloWorldRepositoryInterface;
use App\Consumer\Services\CacheService;
use App\Consumer\Services\InfoService;
use App\Consumer\Services\InfoServiceFactory;
use \Mockery as M;

/**
 * Class InfoServiceTest
 * @package tests\unit\Consumer\Services
 *
 * Unit tests:
 * created Object (in this case Service)
 * call a method and check if the response is in a form it should be
 * assert (checking) can be by value, by checking if response is the instance of a given class etc
 *
 * when the method has inner jobs done by other object (like in service we have repositories)
 * then those objects actions need to be mocked.
 *
 * Unit tests only check correctness of a given method
 */
class InfoServiceTest extends \PHPUnit_Framework_TestCase
{
    public function testInfoServiceCanBeCreatedByFactory()
    {
        $cacheService = M::mock(CacheService::class);
        $infoService = InfoServiceFactory::create($cacheService);
        $this->assertInstanceOf(InfoService::class, $infoService);
    }

    public function testInfoServiceCanGetHelloWorld()
    {
        $helloWorldRepositoryMock = M::mock(HelloWorldRepositoryInterface::class);
        $resultOfHelloWorldRepositoryGetById = 'string result of getByID';
        $helloWorldRepositoryMock->shouldReceive('getById')->andReturn($resultOfHelloWorldRepositoryGetById);

        $infoService = new InfoService($helloWorldRepositoryMock);
        $result = $infoService->getHelloWorld(1);

        // service only calls repository,
        // result of service method should be the same like repository's method
        $this->assertEquals($resultOfHelloWorldRepositoryGetById, $result);
    }
}