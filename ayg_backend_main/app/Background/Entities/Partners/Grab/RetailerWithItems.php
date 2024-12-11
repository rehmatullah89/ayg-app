<?php
namespace App\Background\Entities\Partners\Grab;

use App\Background\Entities\Entity;

class RetailerWithItems extends Entity implements \JsonSerializable
{
    /**
     * @var Retailer
     */
    private $retailer;
    /**
     * @var RetailerItemList
     */
    private $retailerItemList;

    public function __construct(
        Retailer $retailer,
        RetailerItemList $retailerItemList
    ) {
        $this->retailer = $retailer;
        $this->retailerItemList = $retailerItemList;
    }

    /**
     * @return Retailer
     */
    public function getRetailer(): Retailer
    {
        return $this->retailer;
    }

    /**
     * @return RetailerItemList
     */
    public function getRetailerItemList(): RetailerItemList
    {
        return $this->retailerItemList;
    }

    function jsonSerialize()
    {
        return get_object_vars($this);
    }
}
