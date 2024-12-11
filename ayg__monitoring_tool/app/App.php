<?php
namespace App;

use App\Services\GenerateUrlService;
use App\Services\NotificationService;
use App\Services\QueueService;
use App\Services\ParseService;
use App\Services\SlackService;
use App\Services\TwilioService;
use App\Services\WebsiteUpService;
use App\Services\WebsiteResponseService;

class App
{
    private $websiteResponseService;
    private $websiteUpService;
    private $queueService;
    private $parseService;
    private $queueMidPriorityService;
    private $queueDeadLetterService;
    private $notificationService;

    public function initiate($config)
    {
        $this->websiteResponseService = new WebsiteResponseService(
            $config['angularWebUrl'],
            $config['wordpressWebUrl']
        );

        $this->websiteUpService = new WebsiteUpService(
            new GenerateUrlService(
                $config['WebsiteUpUrl'],
                $config['WebsiteUpApiKey']
            ),
            $config['WebsiteUpExptectedResultRawBody'],
            $config['WebsiteUpExptectedResultCode']
        );

        $this->notificationService = new NotificationService(
            new SlackService($config['slackStatusWebhookUrl']),
            new SlackService($config['slackErrorWebhookUrl']),
            new TwilioService(
                $config['twilioSID'],
                $config['twilioToken'],
                $config['twilioFromPhoneNumber']
            ),
            $config['twilioInformNotMoreOftenThenXMin'],
            $config['twilioPhoneNumbers']
        );

        $this->queueService = new QueueService(
            $config['queueConfig'],
            $config['queueQueueName']
        );

        $this->queueMidPriorityService = new QueueService(
            $config['queueMidPriorityConfig'],
            $config['queueMidPriorityQueueName']
        );

        $this->queueDeadLetterService = new QueueService(
            $config['queueDeadLetterConfig'],
            $config['queueDeadLetterQueueName']
        );

        $this->parseService = new ParseService(
            $config['configParseServerURL'],
            $config['configParseMount'],
            $config['configParseApplicationId'],
            $config['configParseRestAPIKey'],
            $config['configParseMasterKey']
        );
    }

    public function getWebsiteUpService(): WebsiteUpService
    {
        return $this->websiteUpService;
    }

    public function getParseService(): ParseService
    {
        return $this->parseService;
    }

    public function getWebsiteResponseService(): WebsiteResponseService
    {
        return $this->websiteResponseService;
    }

    public function getQueueService(): QueueService
    {
        return $this->queueService;
    }

    public function getQueueMidPriorityService(): QueueService
    {
        return $this->queueMidPriorityService;
    }

    public function getQueueDeadLetterService(): QueueService
    {
        return $this->queueDeadLetterService;
    }

    public function getNotificationService(): NotificationService
    {
        return $this->notificationService;
    }
}
