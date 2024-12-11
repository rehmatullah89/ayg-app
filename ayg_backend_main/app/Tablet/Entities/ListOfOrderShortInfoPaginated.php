<?php
namespace App\Tablet\Entities;

use App\Tablet\Entities\Pagination;

class ListOfOrderShortInfoPaginated extends Entity implements \JsonSerializable
{
    /**
     * @var OrderShortInfo[]
     */
    private $orderList;
    /**
     * @var Pagination
     */
    private $pagination;

    public function __construct(
        array $orderList,
        $pagination
    )
    {
        $this->orderList = $orderList;
        $this->pagination = $pagination;
    }

    /**
     * @return OrderShortInfo[]
     */
    public function getOrderList()
    {
        return $this->orderList;
    }

    /**
     * @return \App\Tablet\Entities\Pagination
     */
    public function getPagination()
    {
        return $this->pagination;
    }


    // function called when encoded with json_encode
    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}