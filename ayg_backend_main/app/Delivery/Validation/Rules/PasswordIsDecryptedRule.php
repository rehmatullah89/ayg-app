<?php

namespace App\Delivery\Validation\Rules;

use App\Delivery\Helpers\EncryptionHelper;
use Respect\Validation\Rules\AbstractRule;

class PasswordIsDecryptedRule extends AbstractRule
{
    /**
     * tries to decrypt password
     * if fails then it is not in encrypted form
     * @param string $input
     * @return bool
     */
    public function validate($input)
    {
        try {
            $decrypted = EncryptionHelper::decryptStringInMotion($input);
        } catch (\Exception $e) {
            return false;
        }
        if ($decrypted === false) {
            return false;
        }
        return true;
    }
}
