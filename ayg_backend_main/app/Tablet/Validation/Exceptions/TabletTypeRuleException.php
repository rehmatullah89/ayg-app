<?php
namespace App\Tablet\Validation\Exceptions;

use Respect\Validation\Exceptions\ValidationException;

class TabletTypeRuleException extends ValidationException
{
    public static $defaultTemplates = [
        self::MODE_DEFAULT => [
            self::STANDARD => 'Tablet user type must be "t".',
        ],
        self::MODE_NEGATIVE => [
            self::STANDARD => 'Tablet user type must be "t".',
        ],
    ];
}