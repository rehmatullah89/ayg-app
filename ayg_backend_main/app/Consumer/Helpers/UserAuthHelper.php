<?php
namespace App\Consumer\Helpers;


use App\Consumer\Exceptions\UserDeviceArrayIncorrectException;

class UserAuthHelper
{
    public static function getDeviceIdentifierFromDeviceArray(array $decodedDeviceArray): string
    {
        if (!isset($decodedDeviceArray['deviceId']) || !isset($decodedDeviceArray['deviceModel'])) {
            throw new UserDeviceArrayIncorrectException(json_encode($decodedDeviceArray));
        }

        return $decodedDeviceArray['deviceId'] . $decodedDeviceArray['deviceModel'];
    }


    public static function isSessionTokenFromCustomSessionManagement(string $sessionToken): bool
    {
        return self::pregMatchCustomSessionManagementWithoutTypeIndicator($sessionToken, '\-c');
    }

    public static function isSessionTokenFromCustomSessionManagementWithoutTypeIndicator(string $sessionToken): bool
    {
        return self::pregMatchCustomSessionManagementWithoutTypeIndicator($sessionToken);
    }

    private static function pregMatchCustomSessionManagementWithoutTypeIndicator(
        string $sessionToken,
        string $postfix = ''
    ): bool {
        $reg = '[a-zA-Z0-9]{42}\.[a-zA-Z0-9]{14}\.[a-zA-Z0-9]{8}' . $postfix;
        $result = preg_match('#^' . $reg . '$#', $sessionToken);
        if ($result) {
            return true;
        }
        return false;
    }

    public static function getConsumerApiKeySalts(): array
    {
        return [
            'Ios' => $GLOBALS['env_AppRestAPIKeySaltIos'],
            'Android' => $GLOBALS['env_AppRestAPIKeySaltAndroid'],
            'Website' => $GLOBALS['env_AppRestAPIKeySaltWebsite'],
        ];
    }
}
