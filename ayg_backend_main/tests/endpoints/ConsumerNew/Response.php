<?php

namespace tests\endpoints\ConsumerNew;

/**
 * Class Response
 * Class used in post and get method to return value of the test request
 */
class Response
{
    /**
     * @var string
     */
    private $body;
    /**
     * @var int
     */
    private $httpStatusCode;

    /**
     * TestResponse constructor.
     * @param $body
     * @param $httpStatusCode
     */
    public function __construct($body, $httpStatusCode)
    {
        $this->body = $body;
        $this->httpStatusCode = $httpStatusCode;
    }

    /**
     * @return bool
     */
    public function isHttpResponseCorrect()
    {
        if ($this->httpStatusCode == 200) {
            return true;
        }
        return false;
    }

    /**
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @param bool $convertToArray
     * @return mixed
     */
    public function getJsonDecodedBody($convertToArray = false)
    {
        return json_decode($this->body, $convertToArray);
    }

    /**
     * @return int
     */
    public function getHttpStatusCode()
    {
        return $this->httpStatusCode;
    }

}