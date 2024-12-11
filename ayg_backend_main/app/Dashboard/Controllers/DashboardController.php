<?php

namespace App\Dashboard\Controllers;

use App\Dashboard\Services\DashboardService;
use App\Dashboard\Services\DashboardServiceFactory;

/**
 * Class DashboardController
 * @package App\Tablet\Controllers
 */
class DashboardController extends Controller
{
    /**
     * @var DashboardService
     */
    private $dashboardService;

    /**
     * DashboardController constructor.
     */
    public function __construct()
    {
        try {
            parent::__construct();
            $this->dashboardService = DashboardServiceFactory::create($this->cacheService);
        } catch (\Exception $e) {
            $this->response->setErrorFromException(ErrorPrefix::APPLICATION_TABLET . ErrorPrefix::CONTROLLER_ORDER,
                $e)->returnJson();
        }
    }

    public function getAll86Items()
    {
        try {
            $itemDetails = $this->dashboardService->getAll86Items();
            json_echo(
                json_encode($itemDetails)
            );
            //$this->response->setSuccess($itemDetails)->returnJson();
        } catch (\Exception $e) {
            $this->response->setErrorFromException(ErrorPrefix::APPLICATION_TABLET . ErrorPrefix::CONTROLLER_ORDER,
                $e)->returnJson();
        }
    }

    public function orderPartialRefund($orderId, $refundType, $inCents, $reason)
    {
        try {
            $responseArray = $this->dashboardService->orderPartialRefund($orderId, $refundType, $inCents, $reason);
            json_echo(
                json_encode($responseArray)
            );
        } catch (\Exception $e) {
            $this->response->setErrorFromException(ErrorPrefix::APPLICATION_TABLET . ErrorPrefix::CONTROLLER_ORDER,
                $e)->returnJson();
        }
    }

}
