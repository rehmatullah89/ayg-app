<?php

namespace App\Delivery\Validation\Rules;

use App\Delivery\Helpers\EncryptionHelper;
use App\Delivery\Helpers\SanitizeHelper;
use Respect\Validation\Rules\AbstractRule;

class DeviceArrayRequirementsRule extends AbstractRule
{
    /**
     * checks if password is in the correct form
     * @param string $input
     * @return bool
     */
    public function validate($input)
    {
        // can be not filled
        if ($input==''){
            return true;
        }

        // try to decode
        try {
            $input = EncryptionHelper::decodeDeviceArray($input);
            if (!is_array($input)) {
                return false;
            } else {
                $deviceArray = SanitizeHelper::sanitizeArray($input);
            }
        } catch (\Exception $e) {
            return false;
        }
        return isValidDeviceArray($deviceArray);
    }
}
