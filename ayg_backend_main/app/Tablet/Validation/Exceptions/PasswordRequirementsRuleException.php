<?php
namespace App\Tablet\Validation\Exceptions;

use Respect\Validation\Exceptions\ValidationException;

class PasswordRequirementsRuleException extends ValidationException
{
    public static $defaultTemplates = [
        self::MODE_DEFAULT => [
            self::STANDARD => 'Password requirements not met.',
        ],
        self::MODE_NEGATIVE => [
            self::STANDARD => 'Password requirements not met.',
        ],
    ];
}