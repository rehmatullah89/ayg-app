<?php
namespace App\Background\Repositories;

/**
 * Interface UserActionLogRepositoryInterface
 * @package App\Background\Repositories
 */
interface UserActionLogRepositoryInterface
{

    /**
     * @param $objectId
     * @param $action
     * @param $data
     * @param $location
     * @param $timestamp
     */
    public function logUserAction(string $objectId, string $action, string $data, array $location, int $timestamp): void;
}