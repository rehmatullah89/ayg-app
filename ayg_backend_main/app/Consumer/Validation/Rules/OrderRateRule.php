<?php

namespace App\Consumer\Validation\Rules;

use Respect\Validation\Rules\AbstractRule;

class OrderRateRule extends AbstractRule
{
    /**
     * check if user type is correct, it can be:
     * t - for tablet
     * @param string $input
     * @return bool
     */
    public function validate($input)
    {
        if($input != intval($input)) {
            return false;
        }
        // we accept only tablet
        if ($input < -1 || $input == 0 || $input >5 ){
            return false;
        }
        return true;
    }
}