<?php

namespace App\Tablet\Errors;

/**
 * Class Error
 * @package App\Tablet\Errors
 */
class Error implements \JsonSerializable
{
    /**
     * error code - will be overwritten by class that extends this base class
     */
    const CODE = 'AS_000';

    /**
     * error message - will be overwritten by class that extends this base class
     */
    const MESSAGE = '';

    /**
     * @var string[]
     */
    protected $additionalData;
    /**
     * @var string
     */
    private $displayMessage;

    /**
     * Error constructor.
     * @param $errorCodePrefix
     * @param array $additionalData
     * @param $displayMessage
     */
    public function __construct($errorCodePrefix, $additionalData = [], $displayMessage = '')
    {
        //$this->errorCodeDisplay = $errorCodePrefix . static::CODE;
        $this->additionalData = $additionalData;
        if ($displayMessage != '') {
            $this->displayMessage = $displayMessage;
        } else {
            $this->displayMessage = null;
        }
    }

    // function called when encoded with json_encode
    public function jsonSerialize()
    {
        // if error codes pass in additional data just use them
        if (isset($this->additionalData['error_code'])){
            $return = new \stdClass();
            $return->error_code = $this->additionalData['error_code'];
            $return->error_description = 'Something went wrong. We are working on fixing the problem.';
            return $return;
        }

        $return = new \stdClass();
        $return->error_code = static::CODE;

        if ($this->displayMessage != null) {
            $return->error_description = $this->displayMessage;
        } else {
            $return->error_description = static::MESSAGE;
        }
        //$return->code = $this->errorCodeDisplay;
        //$return->message = static::MESSAGE;
        if (!empty($this->additionalData)) {
            $return->additionalData = $this->additionalData;
        }
        return $return;
    }
}