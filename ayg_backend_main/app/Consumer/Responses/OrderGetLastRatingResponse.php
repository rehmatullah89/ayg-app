<?php

namespace App\Consumer\Responses;

use App\Consumer\Entities\OrderRatingWithDelivery;

/**
 * Class OrderRateResponse
 */
class OrderGetLastRatingResponse extends ControllerResponse implements \JsonSerializable
{
    /**
     * @var int
     */
    private $rating;
    /**
     * @var \DateTime
     */
    private $ratingCreatedAt;
    /**
     * @var string
     */
    private $feedback;
    /**
     * @var string
     */
    private $orderFullfillmentType;
    /**
     * @var string
     */
    private $deliveryFirstName;

    /**
     * OrderRateResponse constructor.
     * @param $rating
     * @param $ratingCreatedAt
     * @param $feedback
     * @param $orderFullfillmentType
     * @param $deliveryFirstName
     */
    public function __construct($rating, $ratingCreatedAt, $feedback, $orderFullfillmentType, $deliveryFirstName)
    {
        $this->rating = $rating;
        $this->ratingCreatedAt = $ratingCreatedAt;
        $this->feedback = $feedback;
        $this->orderFullfillmentType = $orderFullfillmentType;
        $this->deliveryFirstName = $deliveryFirstName;
    }

    /**
     * @return OrderGetLastRatingResponse
     *
     * for not rated orders empty response
     */
    public static function createEmpty()
    {
        return new OrderGetLastRatingResponse(
            0,
            null,
            null,
            null,
            null
        );
    }

    /**
     * @param OrderRatingWithDelivery $orderRatingWithDelivery
     * @return OrderGetLastRatingResponse
     */
    public static function createFromOrderRatingWithDelivery(OrderRatingWithDelivery $orderRatingWithDelivery)
    {
        if ($orderRatingWithDelivery->getDelivery() !== null) {
            $deliveryFirstName = $orderRatingWithDelivery->getDelivery()->getFirstName();
        } else {
            $deliveryFirstName = null;
        }

        return new OrderGetLastRatingResponse(
            $orderRatingWithDelivery->getOrderRating()->getOverAllRating(),
            $orderRatingWithDelivery->getOrderRating()->getCreatedAt(),
            $orderRatingWithDelivery->getOrderRating()->getFeedback(),
            $orderRatingWithDelivery->getOrderRating()->getOrder()->getFullfillmentType(),
            $deliveryFirstName
        );
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}