<?php
namespace App\Delivery\Entities;

class OrderComment extends Entity implements \JsonSerializable
{

    /**
     * @var string
     */
    private $orderId;
    /**
     * @var string
     */
    private $author;
    /**
     * @var string
     */
    private $comment;
    /**
     * @var \DateTime
     */
    private $createdAt;

    public function __construct(
        string $orderId,
        string $author,
        string $comment,
        \DateTime $createdAt
    ) {
        $this->orderId = $orderId;
        $this->author = $author;
        $this->comment = $comment;
        $this->createdAt = $createdAt;
    }

    /**
     * @return string
     */
    public function getOrderId(): string
    {
        return $this->orderId;
    }

    /**
     * @return string
     */
    public function getAuthor(): string
    {
        return $this->author;
    }

    /**
     * @return string
     */
    public function getComment(): string
    {
        return $this->comment;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    // function called when encoded with json_encode
    public function jsonSerialize()
    {
        $array = get_object_vars($this);
        $array['createdAtAirportTimezone'] = $array['createdAt'];
        unset($array['createdAt']);
        unset($array['orderId']);
        return $array;
    }

    public function getCreatedAtUTC(): string
    {
        $createdAtClone = clone($this->getCreatedAt());
        $createdAtClone->setTimezone(new \DateTimeZone('UTC'));
        return $createdAtClone->format('Y-m-d H:i:s');
    }

    public function getCreatedAtAirportTimezone(): string
    {
        return $this->getCreatedAt()->format('Y-m-d H:i:s');
    }
}
