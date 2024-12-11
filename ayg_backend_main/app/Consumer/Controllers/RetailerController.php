<?php

namespace App\Consumer\Controllers;

use App\Consumer\Services\RetailerService;
use App\Consumer\Services\RetailerServiceFactory;

/**
 * Class RetailerController
 * @package App\Consumer\Controllers
 */
class RetailerController extends Controller
{
    /**
     * @var RetailerService
     */
    private $retailerService;

    /**
     * RetailerController constructor.
     */
    public function __construct()
    {
        try {
            parent::__construct();
            $this->retailerService = RetailerServiceFactory::create($this->cacheService);
        } catch (\Exception $e) {
            $this->response->setErrorFromException(ErrorPrefix::APPLICATION_TABLET . ErrorPrefix::CONTROLLER_ORDER,
                $e)->returnJson();
        }
    }

    public function getFullfillmentInfo($airportIataCode, $locationId, $retailerId)
    {
        getRouteCache();

        $responseArray = $this->retailerService->getFullfillmentTimes($airportIataCode, $locationId, $retailerId);

        // Cache for 5 mins
        json_echo(
            setRouteCache([
                "jsonEncodedString" => json_encode($responseArray),
                "expireInSeconds" => 5*60
            ])
        );
    }

}
