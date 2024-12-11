<?php

use App\Consumer\Repositories\HelloWorldParseRepository;

/**
 * Class HelloWorldRepositoryTest
 *
 * Integration tests calls method that works on the database and then check directly in the database if there was
 * an action
 */
class HelloWorldRepositoryTest extends \PHPUnit_Framework_TestCase
{
    public function testHelloWorlParsedRepositoryCanGetById()
    {
        $helloWorldParseRepository = new HelloWorldParseRepository();
        $resultOfHelloWorldParseRepositoryGetById = $helloWorldParseRepository->getById(1);


        // this is very simple repository method,
        // normally it calls database (get, update, delete),
        // scenario for that should be created (prepare database)
        // then call the method, and check result is it correct
        $this->assertEquals('Hello world, your user ID is 1', $resultOfHelloWorldParseRepositoryGetById);
    }
}