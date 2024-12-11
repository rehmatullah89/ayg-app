<?php

namespace App\Tablet\Responses;

use App\Tablet\Errors\Error;
use App\Tablet\Errors\ErrorPrefix;
use App\Tablet\Errors\OtherApplicationError;
use App\Tablet\Exceptions\Exception;
use App\Tablet\Helpers\ConfigHelper;

/**
 * Class Response
 * @package App\Tablet\Responses
 */
class Response implements \JsonSerializable
{
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
     * @param $responseData
     * @param $pagination
     * @return Response
     */
    public function setPaginatedSuccess($responseData, $pagination)
    {
        $this->data = $responseData;
        $this->pagination = $pagination;
        $this->error = null;
        $this->dateTime = new \DateTime();
        $this->success = true;
        return $this;
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

        if (in_array($environmentDisplayCode, ['DEV', 'DEV_LOCAL', 'TEST'])) {
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
            $this->error = new $errorName($errorCodePrefix, $backtraceArray);
            json_error(ErrorPrefix::APPLICATION_TABLET . ErrorPrefix::CONTROLLER_ORDER . $errorName::CODE, $errorName::MESSAGE, $exception->getMessage(), '', 1);
            return $this;
        }

        $this->error = new OtherApplicationError($errorCodePrefix, $backtraceArray);
        json_error(ErrorPrefix::APPLICATION_TABLET . ErrorPrefix::CONTROLLER_ORDER . OtherApplicationError::CODE, OtherApplicationError::MESSAGE, $exception->getMessage(), '', 1);
        return $this;
    }

    /**
     * @return void
     */
    public function returnJson()
    {
        if ($this->data === null){
            header('HTTP/1.0 400 Forbidden');
        }
        header('Content-Type: application/json');

        $this->logResponse(json_encode($this));


        echo(json_encode($this));
        exit();
    }

    /**
     * @return void
     */
    public function returnAccessInternalServerErrorJson()
    {
        header('HTTP/1.0 500 Internal Server Error');
        header('Content-Type: application/json');
        echo(json_encode($this));
        exit();
    }

    /**
     * @return void
     */
    public function returnAccessForbiddenJson()
    {
        $this->logResponse(json_encode($this),'403');

        header('HTTP/1.0 403 Forbidden');
        header('Content-Type: application/json');
        echo(json_encode($this));
        exit();
    }

    /**
     * @return void
     */
    public function returnAccessUnauthorizedJson()
    {
        $this->logResponse(json_encode($this),'401');

        header('HTTP/1.0 401 Unauthorized');
        header('Content-Type: application/json');
        echo(json_encode($this));
        exit();
    }

    // function called when encoded with json_encode
    public function jsonSerialize()
    {
        if ($this->data !== null) {
            $return = json_decode(json_encode($this->data));
            if ($this->pagination !== null) {
                $return->pagination = $this->pagination;
            }
            return $return;
        } else {
            return $this->error;
        }
        /*
        $return = new \stdClass();
        $return->success = $this->success;

        if ($this->data !== null) {
            $return->data = $this->data;
        }

        if ($this->error !== null) {
            $return->error = $this->error;
        }

        if ($this->pagination !== null) {
            $return->pagination = $this->pagination;
        }

        $return->dateTime = $this->dateTime;

        return $return;
        */
    }

    /**
     * @param $exceptionName
     * @return string
     */
    private function getErrorNameFromExceptionName($exceptionName)
    {
        $baseExceptionClassName = Exception::class;
        $basePathForInternalExceptions = substr($baseExceptionClassName, 0, -1 * strlen('Exception'));

        // exception is our internal - starts with App\Tablet\Exceptions
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


    private function logResponse($json, $code=''){
        if (getenv('env_EnvironmentDisplayCode')=='PROD'){
            return true;
        }

        $logFile = __DIR__.'/../../../storage/logs/api_call.php';
        if (!file_exists($logFile)){
            $fHandle = fopen ($logFile,'w');
            fwrite($fHandle,'<?php'."\n");
        }else{
            $fHandle = fopen ($logFile,'a');
        }

        if (empty($code)){
            $code = '_not_set_';
        }

        $json = json_encode(json_decode($json), JSON_PRETTY_PRINT);
        fwrite(
            $fHandle,
            date('Y-m-d H:i:s').' httpCode:'.$code.' '.$_SERVER['REMOTE_ADDR'].' RESPONSE - '.$_SERVER['REQUEST_METHOD'].' '.$_SERVER['REQUEST_URI'].PHP_EOL.var_export($json, TRUE).PHP_EOL);
        fclose($fHandle);
    }

}
