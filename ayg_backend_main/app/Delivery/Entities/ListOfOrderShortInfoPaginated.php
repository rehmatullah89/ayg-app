<?php
namespace App\Delivery\Entities;


class ListOfOrderShortInfoPaginated extends Entity implements \JsonSerializable
{
    /**
     * @var OrderShortInfoList
     */
    private $orderShortInfoList;
    /**
     * @var Pagination
     */
    private $pagination;

    public function __construct(
        OrderShortInfoList $orderShortInfoList,
        Pagination $pagination
    ) {
        $this->orderShortInfoList = $orderShortInfoList;
        $this->pagination = $pagination;
    }

    /**
     * @return OrderShortInfoList
     */
    public function getOrderShortInfoList(): OrderShortInfoList
    {
        return $this->orderShortInfoList;
    }

    /**
     * @return Pagination
     */
    public function getPagination(): Pagination
    {
        return $this->pagination;
    }


    // function called when encoded with json_encode
    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}
