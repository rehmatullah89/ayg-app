<?php
namespace App\Background\Repositories;


/**
 * Interface DeliveryLogRepositoryInterface
 * @package App\Background\Repositories
 */
interface DeliveryLogRepositoryInterface
{
    /**
     * @param $airportIataCode
     * @param $action
     * @param $timeStamp
     * @return void
     */
    public function logDeliveryStatusChangedToActive(string $airportIataCode, string $action, int  $timeStamp): void;

    /**
     * @param $airportIataCode
     * @param $action
     * @param $timeStamp
     * @return void
     */
    public function logDeliveryStatusChangedToInactive(string $airportIataCode, string $action, int  $timeStamp): void;

    /**
     * @param $airportIataCode
     * @param $action
     * @param $timeStamp
     * @param $orderSequenceId
     * @return void
     */
    public function logOrderDeliveryStatus(string $airportIataCode, string $action, int $timeStamp, string $orderSequenceId): void;
}
