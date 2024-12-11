<?php

namespace App\Delivery\Validation\Rules;

use Respect\Validation\Rules\AbstractRule;

class DeliveryTypeRule extends AbstractRule
{
    /**
     * check if user type is correct, it can be:
     * d - for delivery
     * @param string $input
     * @return bool
     */
    public function validate($input)
    {
        // we accept only tablet
        if (in_array($input,['d'])){
            return true;
        }
        return false;
    }
}
