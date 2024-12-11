<?php
namespace App\Consumer\Repositories;

//use App\Consumer\Entities\Items;
//use App\Consumer\Entities\ItemsList;

interface RetailerRepositoryInterface
{
    public function getFulfillmentTimesInfo(string $airportIataCode, int $locationId, int $retailerId): array;
}
