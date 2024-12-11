<?php

namespace App\Consumer\Errors;

/**
 * Class Error
 * @package Consumer\App\Errors
 *
 * Base class for all errors
 * Error is object that is returned in the Response by Controller when something goes wrong
 * Every Error has CODE and MESSAGE, and also optionally additionalData
 *
 * CODE is represented by String started with "AS_" and fallowed by integer,
 * all error codes ca be found in the /ERRORCODES.md file
 *
 * MESSAGE is human readable message
 *
 * additionalData can contain any additional information (broken validation info, error track list etc)
 */
class Error implements \JsonSerializable
{
    const LEVEL_INFO = 3;
    const LEVEL_WARNING = 2;
    const LEVEL_ERROR = 1;

    /**
     * error code
     */
    const CODE = 'AS_000';

    /**
     * error message
     */
    const MESSAGE = '';

    /**
     * default error level
     */
    const LEVEL = Error::LEVEL_ERROR;

    /**
     * @var string[]
     */
    protected $additionalData;

    /**
     * Error constructor.
     * @param array $additionalData
     */
    public function __construct($additionalData = [])
    {
        $this->additionalData = $additionalData;
    }

    /**
     * @return \stdClass
     * function that is called when json_encode is called on Error object
     */
    public function jsonSerialize()
    {
        $return = new \stdClass();
        $return->error_code = static::CODE;
        $return->error_description = static::MESSAGE;
        if (!empty($this->additionalData)) {
            $return->additionalData = $this->additionalData;
        }
        return $return;
    }
}
