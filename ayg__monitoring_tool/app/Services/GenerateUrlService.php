<?php
namespace App\Services;

class GenerateUrlService
{
    private $url;
    private $apiKey;

    public function __construct($url, $apiKey)
    {
        $this->url = $url;
        $this->apiKey = $apiKey;
    }

    function generate()
    {
        $url = $this->url;
        $epoch = microtime(true) * 1000;
        $apiKey = md5($epoch . $this->apiKey);
        $sessionToken = '0';

        $url = str_replace('a/:apikey', 'a/' . $apiKey, $url);
        $url = str_replace('e/:epoch', 'e/' . $epoch, $url);
        $url = str_replace('u/:sessionToken', 'u/' . $sessionToken, $url);

        return $url;
    }
}
