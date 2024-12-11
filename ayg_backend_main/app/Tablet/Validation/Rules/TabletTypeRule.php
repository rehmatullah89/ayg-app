<?php

namespace App\Tablet\Validation\Rules;

use Respect\Validation\Rules\AbstractRule;

class TabletTypeRule extends AbstractRule
{
    /**
     * check if user type is correct, it can be:
     * t - for tablet
     * @param string $input
     * @return bool
     */
    public function validate($input)
    {
        // we accept only tablet
        if (in_array($input,['t'])){
            return true;
        }
        return false;
    }
}