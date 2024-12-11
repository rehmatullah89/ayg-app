<?php
namespace App\Consumer\Entities;

/**
 * Class OrderRating
 * @package App\Consumer\Entities
 */
class OrderRating extends Entity implements \JsonSerializable
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var Order
     */
    private $order;

    /**
     * @var float
     */
    private $overAllRating;

    /**
     * @var User
     */
    private $user;

    /**
     * @var string
     */
    private $feedback;

    /**
     * @var \DateTime
     */
    private $createdAt;

    /**
     * User constructor.
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->id = $data['id'];
        $this->order = $data['order'];
        $this->overAllRating = $data['overAllRating'];
        $this->user = $data['user'];
        $this->feedback = $data['feedback'];
        $this->createdAt = $data['createdAt'];
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    // function called when encoded with json_encode

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return get_object_vars($this);
    }

    /**
     * @return Order
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * @param Order $order
     */
    public function setOrder($order)
    {
        $this->order = $order;
    }

    /**
     * @return float
     */
    public function getOverAllRating()
    {
        return $this->overAllRating;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param User $user
     */
    public function setUser($user)
    {
        $this->user = $user;
    }


    /**
     * @param float $overAllRating
     */
    public function setOverAllRating($overAllRating)
    {
        $this->overAllRating = $overAllRating;
    }

    /**
     * @return string
     */
    public function getFeedback()
    {
        return $this->feedback;
    }

    /**
     * @param string $feedback
     */
    public function setFeedback($feedback)
    {
        $this->feedback = $feedback;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }


}