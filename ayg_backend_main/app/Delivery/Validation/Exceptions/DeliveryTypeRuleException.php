<?php
namespace App\Delivery\Validation\Exceptions;

use Respect\Validation\Exceptions\ValidationException;

class DeliveryTypeRuleException extends ValidationException
{
    public static $defaultTemplates = [
        self::MODE_DEFAULT => [
            self::STANDARD => 'Delivery user type must be "d".',
        ],
        self::MODE_NEGATIVE => [
            self::STANDARD => 'Delivery user type must be "d".',
        ],
    ];
}
