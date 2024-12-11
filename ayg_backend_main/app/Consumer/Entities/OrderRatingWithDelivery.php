<?php
namespace App\Consumer\Entities;

use App\Consumer\Services\OrderService;

/**
 * Class OrderRatingWithDelivery
 * @package App\Consumer\Entities
 *
 * Entity used to return both data - rating data, and delivery man data
 * @see OrderService::getLastOrderRatingWithDelivery()
 */
class OrderRatingWithDelivery extends Entity implements \JsonSerializable
{
    /**
     * @var OrderRating|null
     */
    private $orderRating;
    /**
     * @var User|null
     */
    private $delivery;

    public function __construct($orderRating, $delivery)
    {
        $this->orderRating = $orderRating;
        $this->delivery = $delivery;
    }

    /**
     * @return OrderRating|null
     */
    public function getOrderRating()
    {
        return $this->orderRating;
    }

    /**
     * @return User|null
     */
    public function getDelivery()
    {
        return $this->delivery;
    }



    // function called when encoded with json_encode
    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}