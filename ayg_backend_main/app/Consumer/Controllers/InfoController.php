<?php

namespace App\Consumer\Controllers;

use App\Consumer\Errors\ErrorPrefix;
use App\Consumer\Helpers\ConfigHelper;
use App\Consumer\Responses\InfoTipsValuesResponse;
use App\Consumer\Services\InfoService;
use App\Consumer\Services\InfoServiceFactory;


class InfoController extends Controller
{
    /**
     * @var InfoService
     */
    private $infoService;

    public function __construct()
    {
        try {
            parent::__construct();
            $this->infoService = InfoServiceFactory::create($this->cacheService);
        } catch (\Exception $e) {
            $this->response->setErrorFromException(ErrorPrefix::APPLICATION_CONSUMER . ErrorPrefix::CONTROLLER_USER,
                $e)->returnJson();
        }
    }

    public function getTipsValues(){
        $tipsValuesInJson = ConfigHelper::get('env_TipsConfig');
        $this->response->setSuccess(InfoTipsValuesResponse::createFromJsonString($tipsValuesInJson))->returnJson();
    }

    public function getAirlineList(){
        // Check if already have cache for this
        getRouteCache();

        $responseArray = $this->infoService->getAirLines();

        json_echo(
            setRouteCache([
                "jsonEncodedString" => json_encode($responseArray),
                "expireInSeconds" => $GLOBALS['parseClassAttributes']['Airports']['ttl']
            ])
        );
    }
}
