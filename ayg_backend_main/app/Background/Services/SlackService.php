<?php
namespace App\Background\Services;

use Httpful\Request;

class SlackService
{
    private $webbookUrl;

    public function __construct($webbookUrl)
    {
        $this->webbookUrl = $webbookUrl;
    }

    function sendMessage($text)
    {
        $responseArray["text"] = $text;

        $response = Request::post($this->webbookUrl)
            ->body(json_encode($responseArray))
            ->send();

        if ($response->code != 200) {
            throw new \Exception("Message send failed with code " . $response->code);
        }
    }
}
