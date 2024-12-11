<?php
namespace App\Consumer\Exceptions\Partners;

use App\Consumer\Exceptions\Exception;

class OrderCanNotBeSaved extends Exception
{
    /**
     * @var
     */
    private $inputPayload;
    /**
     * @var
     */
    private $outputJson;
    /**
     * @var
     */
    private $url;

    public function __construct(
        $message = "",
        $code = 0,
        \Exception $previous = null,
        $inputPayload,
        $outputJson,
        $url
    ) {
        $this->inputPayload = $inputPayload;
        $this->outputJson = $outputJson;
        $this->url = $url;
        \Exception::__construct($message, $code, $previous);
    }

    /**
     * @return mixed
     */
    public function getInputPayload()
    {
        return $this->inputPayload;
    }

    /**
     * @return mixed
     */
    public function getOutputJson()
    {
        return $this->outputJson;
    }

    /**
     * @return mixed
     */
    public function getUrl()
    {
        return $this->url;
    }
}
