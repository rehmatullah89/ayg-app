<?php

namespace App\Consumer\Services;

use App\Consumer\Repositories\RetailerRepositoryInterface;
//use App\Consumer\Helpers\QueueMessageHelper;
//use App\Consumer\Services\QueueServiceInterface;

class RetailerService extends Service
{
    /**
     * @var RetailerRepositoryInterface
     */
    private $retailerParseRepository;

    public function __construct(
        RetailerRepositoryInterface $retailerParseRepository
        //QueueServiceInterface $queueService
    ) {
        $this->retailerParseRepository = $retailerParseRepository;
        //$this->queueService = $queueService;
    }

    public function getFullfillmentTimes($airportIataCode, $locationId, $retailerId)
    {
        return $this->retailerParseRepository->getFulfillmentTimesInfo($airportIataCode, $locationId, $retailerId);
    }
}
