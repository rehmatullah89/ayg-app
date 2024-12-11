<?php
namespace App\Delivery\Validation\Exceptions;

use Respect\Validation\Exceptions\ValidationException;

class DeliveryStatusRuleException extends ValidationException
{
    public static $defaultTemplates = [
        self::MODE_DEFAULT => [
            self::STANDARD => 'Delivery Status is invalid',
        ],
        self::MODE_NEGATIVE => [
            self::STANDARD => 'Delivery Status is invalid',
        ],
    ];
}
