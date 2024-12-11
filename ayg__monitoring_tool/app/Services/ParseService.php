<?php
namespace App\Services;

use Parse\ParseClient;
use Parse\ParseQuery;

class ParseService
{
    public function __construct($ParseServerURL, $ParseMount, $ParseApplicationId, $ParseRestAPIKey, $ParseMasterKey)
    {
        ParseClient::setServerURL($ParseServerURL, $ParseMount);
        ParseClient::initialize($ParseApplicationId, $ParseRestAPIKey, $ParseMasterKey);
    }

    public function getParseConnectTime()
    {
        $start = microtime(true);

        $airports = new ParseQuery('Airports');
        $airports->equalTo("isReady", true);
        $airports->find(true);

        $time_elapsed_secs = microtime(true) - $start;

        if(!is_null($airports)){
            return number_format($time_elapsed_secs,2);
        }

        return $airports;
    }

}
