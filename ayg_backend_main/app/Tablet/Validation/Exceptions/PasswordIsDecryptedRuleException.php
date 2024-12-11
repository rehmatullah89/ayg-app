<?php
namespace App\Tablet\Validation\Exceptions;

use Respect\Validation\Exceptions\ValidationException;

class PasswordIsDecryptedRuleException extends ValidationException
{
    public static $defaultTemplates = [
        self::MODE_DEFAULT => [
            self::STANDARD => 'Password should be encrypted.',
        ],
        self::MODE_NEGATIVE => [
            self::STANDARD => 'Password should be encrypted.',
        ],
    ];
}