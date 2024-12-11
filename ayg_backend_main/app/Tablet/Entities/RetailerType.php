<?php
namespace App\Tablet\Entities;

/**
 * Class RetailerType
 * @package App\Tablet\Entities
 */
class RetailerType extends Entity implements \JsonSerializable
{
    /**
     * @var mixed
     */
    private $id;
    /**
     * @var mixed
     */
    private $createdAt;
    /**
     * @var mixed
     */
    private $updateAt;
    /**
     * @var mixed
     */
    private $displayOrder;
    /**
     * @var mixed
     */
    private $iconCode;
    /**
     * @var mixed
     */
    private $retailerType;
    /**
     * @var mixed
     */
    private $uniqueId;

    /**
     * RetailerType constructor.
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->id = $data['id'];
        $this->createdAt = $data['createdAt'];
        $this->updateAt = $data['updateAt'];
        $this->displayOrder = $data['displayOrder'];
        $this->iconCode = $data['iconCode'];
        $this->retailerType = $data['retailerType'];
        $this->uniqueId = $data['uniqueId'];
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @return mixed
     */
    public function getUpdateAt()
    {
        return $this->updateAt;
    }

    /**
     * @return mixed
     */
    public function getDisplayOrder()
    {
        return $this->displayOrder;
    }

    /**
     * @return mixed
     */
    public function getIconCode()
    {
        return $this->iconCode;
    }

    /**
     * @return mixed
     */
    public function getRetailerType()
    {
        return $this->retailerType;
    }

    /**
     * @return mixed
     */
    public function getUniqueId()
    {
        return $this->uniqueId;
    }

    /**
     * function called when encoded with json_encode
     *
     * @return array
     */
    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}