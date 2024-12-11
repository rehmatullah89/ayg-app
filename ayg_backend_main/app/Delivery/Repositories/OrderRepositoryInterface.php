<?php
namespace App\Delivery\Repositories;

use App\Delivery\Entities\Order;
use App\Delivery\Entities\OrderList;

interface OrderRepositoryInterface
{
    public function getActiveOrdersCountByAirportIataCode(string $airportIataCode, int $page, int $limit): int;

    public function getCompletedOrdersCountByAirportIataCode(string $airportIataCode, int $page, int $limit): int;

    public function getActiveOrdersByAirportIataCode(string $airportIataCode, int $page, int $limit): OrderList;

    public function getCompletedOrdersByAirportIataCode(string $airportIataCode, int $page, int $limit): OrderList;

    public function getOrderByIdAndAirportIataCode(string $orderId, string $airportIataCode): Order;
}
