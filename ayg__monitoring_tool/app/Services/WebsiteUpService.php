<?php
namespace App\Services;

use Httpful\Request;

class WebsiteUpService
{
    private $generateUrlService;
    private $expectedResultRawBody;
    private $expectedResultCode;

    public function __construct(GenerateUrlService $generateUrlService, $expectedResultRawBody, $expectedResultCode)
    {
        $this->generateUrlService = $generateUrlService;
        $this->expectedResultRawBody = $expectedResultRawBody;
        $this->expectedResultCode = $expectedResultCode;
    }

    function check()
    {
        try {
            $response = Request::get($this->generateUrlService->generate())
                ->send();

            if ((!isset($response->raw_body)) || $response->raw_body != $this->expectedResultRawBody) {
                return false;
            }

            if ((!isset($response->code)) || $response->code != $this->expectedResultCode) {
                return false;
            }

            return true;
        } catch (\Exception $exception) {
            return false;
        }
    }
}
