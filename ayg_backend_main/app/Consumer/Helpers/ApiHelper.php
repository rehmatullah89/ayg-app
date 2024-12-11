<?php
namespace App\Consumer\Helpers;

class ApiHelper
{
    public static function isApiFormationCorrect(string $apikey, string $epoch, array $restAPIKeySalts): bool
    {
        $error_array = checkAPIFormation($apikey, $epoch, $restAPIKeySalts);
        if (!$error_array["isReadyForUse"]) {
            return false;
        }
        return true;
    }
}
