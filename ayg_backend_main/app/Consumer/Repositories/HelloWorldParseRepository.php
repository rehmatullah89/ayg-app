<?php
namespace App\Consumer\Repositories;

/**
 * Class HelloWorldParseRepository
 * @package App\Consumer\Repositories
 *
 *
 * This class is the demo of structure to show the working of Parse in the form of function repository
 *
 * This class all the functions related to the Parse Class that needed to be executed by the respective service
 */
class HelloWorldParseRepository extends ParseRepository implements HelloWorldRepositoryInterface
{
    /**
     * @param $id
     * @return string
     */
    public function getById($id)
    {
        // normally here is a call to database

        // then parse values need to be mapped to Entities

        // for test purpose we will use just return string

        return 'Hello world, your user ID is ' . $id;
    }
}