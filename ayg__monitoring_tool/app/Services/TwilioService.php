<?php
namespace App\Services;

use Twilio\Rest\Client;

class TwilioService
{
    private $sid;
    private $token;
    private $fromPhoneNumber;

    public function __construct(
        $sid,
        $token,
        $fromPhoneNumber
    ) {
        $this->sid = $sid;
        $this->token = $token;
        $this->fromPhoneNumber = $fromPhoneNumber;
    }

    public function sendMessage($phoneNumber, $text)
    {
        $client = new Client($this->sid, $this->token);

        $client->messages->create(
            $phoneNumber,
            [
                'from' => $this->fromPhoneNumber,
                'body' => $text
            ]
        );
    }
}
