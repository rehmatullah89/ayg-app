<?php

namespace App\Consumer\Repositories;

use App\Consumer\Entities\Payment;
use Parse\ParseQuery;

class PaymentParseRepository implements PaymentRepositoryInterface
{
    public function getPaymentByUserId(string $userId): ? Payment
    {
        // list sessions for an active user
        $userInnerQuery = new ParseQuery('_User');
        $userInnerQuery->equalTo('objectId', $userId);

        $query = new ParseQuery('Payments');
        $query->matchesQuery('user', $userInnerQuery);
        $paymentsObject = $query->find();

        if (empty($paymentsObject)) {
            return null;
        }

        return new Payment($userId, $paymentsObject[0]->get('externalCustomerId'));
    }

}
