<?php
namespace tests\unit\Tablet\Services;

use App\Tablet\Services\IronMQService;
use IronMQ\IronMQ;
use \Mockery as M;

class IronMQServiceServiceTest extends \PHPUnit_Framework_TestCase
{

    public function testIronMqCanSendMessage()
    {
        $ironMQMock = M::mock(IronMQ::class);
        $returnClass=new \stdClass();
        $returnClass->id='someId';
        $ironMQMock->shouldReceive('postMessage')->andReturn($returnClass);

        $ironMqService = new IronMQService($ironMQMock, 'domeUrl');

        $return = $ironMqService->sendMessage(['some', ['array']], 0);

        $this->assertTrue($return);
    }

    /**
     * @expectedException \Exception
     */
    public function testIronMqCanThrowMessage()
    {
        $ironMQMock = M::mock(IronMQ::class);
        $returnClass=new \stdClass();
        $ironMQMock->shouldReceive('postMessage')->andReturn($returnClass);

        $ironMqService = new IronMQService($ironMQMock, 'domeUrl');

        $return = $ironMqService->sendMessage(['some', ['array']], 0);

        $this->assertTrue($return);
    }
}