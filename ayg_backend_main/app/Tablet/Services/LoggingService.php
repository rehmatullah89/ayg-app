<?php
namespace App\Tablet\Services;

/**
 * Class LoggingService
 * @package App\Tablet\Services
 */
class LoggingService extends Service
{
    public function construct()
    {

    }

    public function logInfo($error_code, $user_error_description)
    {
        json_error($error_code, $user_error_description, '', 3, 1);
    }
}