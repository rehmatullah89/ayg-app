<?php

namespace App\Dashboard\Services;

use App\Dashboard\Repositories\DashboardRepositoryInterface;
use App\Dashboard\Helpers\QueueMessageHelper;
use App\Dashboard\Services\QueueServiceInterface;

class DashboardService extends Service
{
    /**
     * @var DashboardRepositoryInterface
     */
    private $dashboardCacheRepository;

    public function __construct(
        DashboardRepositoryInterface $dashboardCacheRepository,
        QueueServiceInterface $queueService
    ) {
        $this->dashboardCacheRepository = $dashboardCacheRepository;
        $this->queueService = $queueService;
    }

    public function getAll86Items()
    {
        $responseArray = [];
        $all86Items = $this->dashboardCacheRepository->getAllCachedMenuItems();
        foreach($all86Items as $keyName) {
            $responseArray[] = json_decode($keyName, true);
        }
        return $responseArray;
    }

    public function orderPartialRefund($orderId, $refundType, $inCents, $reason)
    {
        $responseArray = array("json_resp_status" => 1, "json_resp_message" => "Requested!");
        $partialRefundQueue = QueueMessageHelper::getOrderOpsPartialRefundRequestMessage($orderId, $refundType, $inCents, $reason);

        try{
            $this->queueService->sendMessage($partialRefundQueue, 0);
        }catch (Exception $ex) {

            $response = json_decode($ex->getMessage(), true);
            json_error($response["error_code"], "", $response["error_message_log"] . " OrderId - " . $orderId, 1, 1);
            $responseArray = array("json_resp_status" => 0, "json_resp_message" => "Failed!");
        }

        return $responseArray;
    }
}
