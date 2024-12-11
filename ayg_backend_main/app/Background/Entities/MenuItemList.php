<?php
namespace App\Background\Entities;

class MenuItemList extends Entity
{
    private $list;

    public function __construct()
    {
        $this->list = [];
    }

    public function addItem(MenuItem $retailerUniqueIdLoadData)
    {
        $this->list[] = $retailerUniqueIdLoadData;
    }

}
