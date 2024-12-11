<?php

namespace App\Consumer\Repositories;

/**
 * Interface HelloWorldRepositoryInterface
 * @package App\Consumer\Repositories
 *
 * * This class is the demo of structure to show the working of Interfacing Cache and Parse with respective Service
 *
 * This interface provides the necessary function definition for the Cache and Parse Repository to work on
 */
interface HelloWorldRepositoryInterface
{
    /**
     * @param $id
     * @return string
     *
     * Repository interface method, gets greetings with User Id,
     * this is for a demo purpose, in normal situation
     * repositories are created to communication with database,
     * so usually it calls database and returns value (Entity or list of entities)
     */
    public function getById($id);

}