<?php

namespace App\Tablet\Validation\Rules;

use App\Tablet\Helpers\EncryptionHelper;
use Respect\Validation\Rules\AbstractRule;

class PasswordRequirementsRule extends AbstractRule
{
    /**
     * checks if password is in the correct form
     * @param string $input
     * @return bool
     */
    public function validate($input)
    {
        $input = EncryptionHelper::decryptStringInMotion($input);
        return preg_match('/^(?=.*[A-Z])(?=.*[0-9])(?=.*[a-z].*[a-z].*[a-z]).{8,50}$$/', $input);
    }
}