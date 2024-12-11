<?php
namespace App\Tablet\Entities;

/**
 * Class OrderTabletHelpRequest
 * @package App\Tablet\Entities
 */
class OrderTabletHelpRequest extends Entity implements \JsonSerializable
{
    /**
     * @var string
     */
    private $id;
    /**
     * @var Order|null
     */
    private $order;
    /**
     * @var string
     */
    private $content;

    /**
     * OrderTabletHelpRequest constructor.
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->id = $data['id'];
        $this->order = $data['order'];
        $this->content = $data['content'];
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Order|null
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param Order|null $order
     */
    public function setOrder($order)
    {
        $this->order = $order;
    }



    // function called when encoded with json_encode
    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}