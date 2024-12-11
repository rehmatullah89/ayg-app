<?php
namespace App\Services;

use App\Helpers\MessageFormatter;

class NotificationService
{
    /**
     * @var SlackService
     */
    private $slackStatusInformer;
    /**
     * @var SlackService
     */
    private $slackErrorInformer;
    /**
     * @var TwilioService
     */
    private $twilioErrorInformer;
    private $twilioInformNotMoreOftenThenXMin;
    private $phoneNumbers;

    public function __construct(
        SlackService $slackStatusInformer,
        SlackService $slackErrorInformer,
        TwilioService $twilioErrorInformer,
        $twilioInformNotMoreOftenThenXMin,
        $phoneNumbers
    ) {
        $this->slackStatusInformer = $slackStatusInformer;
        $this->slackErrorInformer = $slackErrorInformer;
        $this->twilioErrorInformer = $twilioErrorInformer;
        $this->twilioInformNotMoreOftenThenXMin = $twilioInformNotMoreOftenThenXMin;
        $this->phoneNumbers = explode(',', $phoneNumbers);
    }

    public function informAboutStatus($text)
    {
        $this->slackStatusInformer->sendMessage($text);
    }

    public function informAboutError($text)
    {
        $this->slackErrorInformer->sendMessage($text);

        $storageDir = __DIR__ . '/../../storage/';
        foreach ($this->phoneNumbers as $phoneNumber) {
            $currentTimestamp = (new \DateTime())->getTimestamp();

            $phoneNumberLastSendTimestampFile = $storageDir . str_replace('+', '00', $phoneNumber) . '.txt';
            $lastTimestamp = 0;
            if (file_exists($phoneNumberLastSendTimestampFile)) {
                $lastTimestamp = file_get_contents($phoneNumberLastSendTimestampFile);
            }
            if ($lastTimestamp + 60 * $this->twilioInformNotMoreOftenThenXMin < $currentTimestamp) {
                $this->twilioErrorInformer->sendMessage($phoneNumber,
                    MessageFormatter::transformSlackMessageIntoTextMessage($text)
                );
                file_put_contents($phoneNumberLastSendTimestampFile, $currentTimestamp);
            }
        }
    }
}
