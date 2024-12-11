<?php
namespace App\Dashboard\Repositories;

//use App\Dashboard\Entities\Items;
//use App\Dashboard\Entities\ItemsList;

interface DashboardRepositoryInterface
{
    public function getAllCachedMenuItems(): array;
}
