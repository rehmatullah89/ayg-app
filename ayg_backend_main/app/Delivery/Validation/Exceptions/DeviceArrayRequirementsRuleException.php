<?php
namespace App\Delivery\Validation\Exceptions;

use Respect\Validation\Exceptions\ValidationException;

class DeviceArrayRequirementsRuleException extends ValidationException
{
    const STANDARD = 0;
    const NAMED = 1;

    public static $defaultTemplates = [
        self::MODE_DEFAULT => [
            self::STANDARD => 'Device Array must be in correct format.',
        ],
        self::MODE_NEGATIVE => [
            self::STANDARD => 'Device Array must be in correct format.',
        ],
    ];
}
