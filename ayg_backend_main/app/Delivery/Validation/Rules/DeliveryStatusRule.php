<?php

namespace App\Delivery\Validation\Rules;

use App\Delivery\Entities\OrderDeliveryStatusFactory;
use Respect\Validation\Rules\AbstractRule;

class DeliveryStatusRule extends AbstractRule
{
    public function validate($input)
    {
        if (in_array($input, OrderDeliveryStatusFactory::getAllPossibleStatusNames())) {
            return true;
        }
        return false;
    }
}
