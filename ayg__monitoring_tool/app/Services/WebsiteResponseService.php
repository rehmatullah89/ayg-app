<?php
namespace App\Services;

class WebsiteResponseService
{
	private $angularWebUrl;
	private $wordpressWebUrl;
	    
    public function __construct($angularWebUrl, $wordpressWebUrl)
    {
        $this->angularWebUrl = $angularWebUrl;
        $this->wordpressWebUrl = $wordpressWebUrl;
    }

    function getWebsiteResponse()
    {
        if($this->get_http_response_code($this->angularWebUrl) == 200 && $this->get_http_response_code($this->wordpressWebUrl) == 200){
            return true;
        }
        return false;
    }

    private function get_http_response_code($domain) {
        $headers = get_headers($domain);
        return substr($headers[0], 9, 3);
    }
}
