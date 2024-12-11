<?php
namespace App\Consumer\Responses;

use App\Consumer\Errors\Error;
use App\Consumer\Errors\ErrorPrefix;
use App\Consumer\Exceptions\Exception;
use App\Consumer\Errors\OtherApplicationError;
use App\Consumer\Helpers\ConfigHelper;

/**
 * Class Response
 * @package App\Consumer\Responses
 *
 * Response is the object that is json_encoded and send in http response,
 * it contains:
 * - success (bool value that indicates if call was successful)
 * - data (Response object or null)
 * - error (Error object or null
 * - dateTime (when the response was created)
 * - pagination (Pagination object if pagination is required in a given endpoint)
 */
class Response implements \JsonSerializable
{
    const STANDARD_SUCCESS_RESPONSE_VALUE = 'success';

    /**
     * @var
     */
    private $data;
    /**
     * @var
     */
    private $pagination;

    /**
     * @var bool
     */
    private $success;

    /**
     * @var Error|null
     */
    private $error;

    /**
     * @var \DateTime
     */
    private $dateTime;

    /**
     * @param $data
     * @param $pagination
     * @param Error|null $error
     */
    public function __construct($data, $pagination, $error)
    {
        $this->data = $data;
        $this->pagination = $pagination;
        $this->error = $error;
        $this->success = false;

        if ($error === null) {
            $this->success = true;
        }

        $this->dateTime = new \DateTime();
    }


    /**
     * @param $responseData
     * @return Response
     */
    public function setSuccess($responseData)
    {
        $this->data = $responseData;
        $this->pagination = null;
        $this->error = null;
        $this->dateTime = new \DateTime();
        $this->success = true;
        return $this;
    }

    /**
     * @param Error $error
     * @return $this
     */
    public function setError(Error $error)
    {
        $this->error = $error;
        return $this;
    }


    /**
     * @return void
     */
    public function returnJson()
    {
        logResponse(json_encode($this));
        header('Content-Type: application/json');
        echo(json_encode($this));
        exit();
    }

    // function called when encoded with json_encode
    public function jsonSerialize()
    {
        if ($this->data !== null) {
            return $this->data;
        } else {
            return $this->error;
        }

        /*
        $return->success = $this->success;
        $return->data = $this->data;
        if ($this->pagination !== null) {
            $return->pagination = $this->pagination;
        }
        $return->error = $this->error;
        $return->dateTime = $this->dateTime;
        return $return;
         */
    }


    /**
     * @param $errorCodePrefix
     * @param \Exception $exception
     * @return Response
     */
    public function setErrorFromException($errorCodePrefix, \Exception $exception)
    {
        $exceptionClassName = get_class($exception);
        $errorName = $this->getErrorNameFromExceptionName($exceptionClassName);

        $this->data = null;
        $this->pagination = null;
        $this->dateTime = new \DateTime();
        $this->success = false;

        try {
            $environmentDisplayCode = ConfigHelper::get('env_EnvironmentDisplayCode');
        } catch (\Exception $e) {
            $environmentDisplayCode = '';
        }

        if (in_array($environmentDisplayCode, ['DEV', 'DEV_LOCAL','TEST'])) {
            $backtraceArray = [
                $exception->getMessage(),
                $exception->getFile(),
                $exception->getLine(),
                $exception->getTraceAsString(),
            ];
        } else {
            $backtraceArray = [];
        }

        if (class_exists($errorName) && $errorName !== Error::class) {
            $this->error = new $errorName($backtraceArray);
            json_error($errorName::CODE, $errorName::MESSAGE, $exception->getMessage(), '', $errorName::LEVEL);
            return $this;
        }

        $this->error = new OtherApplicationError($backtraceArray);
        json_error(OtherApplicationError::CODE, OtherApplicationError::MESSAGE, $exception->getMessage(), '', Error::LEVEL_ERROR);
        return $this;
    }

    /**
     * @param $exceptionName
     * @return string
     */
    private function getErrorNameFromExceptionName($exceptionName)
    {
        $baseExceptionClassName = Exception::class;
        $basePathForInternalExceptions = substr($baseExceptionClassName, 0, -1 * strlen('Exception'));

        // exception is our internal - starts with App\Consumer\Exceptions
        if (substr($exceptionName, 0, strlen($basePathForInternalExceptions)) === $basePathForInternalExceptions) {
            $className = explode('\\', $exceptionName);

            $className = $className[count_like_php5($className) - 1];
        } else {
            $className = str_replace('\\', '_', $exceptionName);
        }

        $mainErrorClass = Error::class;
        $errorClassBase = substr($mainErrorClass, 0, -1 * strlen('Error'));

        return $errorClassBase . str_replace('Exception', 'Error', $className);
    }
}
