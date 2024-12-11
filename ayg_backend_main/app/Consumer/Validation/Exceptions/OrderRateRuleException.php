<?php
namespace App\Consumer\Validation\Exceptions;

use Respect\Validation\Exceptions\ValidationException;

class OrderRateRuleException extends ValidationException
{
    public static $defaultTemplates = [
        self::MODE_DEFAULT => [
            self::STANDARD => 'The rating must be between 1 and 5 stars',
        ],
        self::MODE_NEGATIVE => [
            self::STANDARD => 'The rating must be between 1 and 5 stars',
        ],
    ];
}