<?php

namespace App\Consumer\Controllers;

use App\Consumer\Entities\User;
use App\Consumer\Errors\Error;
use App\Consumer\Errors\ErrorPrefix;
use App\Consumer\Responses\PaymentChargeCardForCredits;
use App\Consumer\Services\PaymentServiceFactory;
use Braintree_PaymentMethod;
use Braintree_PaymentMethodNonce;
use Braintree_Transaction;


class PaymentController extends Controller
{
    private $paymentService;

    public function __construct()
    {
        try {
            parent::__construct();
            $this->paymentService = PaymentServiceFactory::create($this->cacheService);
        } catch (\Exception $e) {
            $this->response->setErrorFromException(ErrorPrefix::APPLICATION_CONSUMER . ErrorPrefix::CONTROLLER_USER,
                $e)->returnJson();
        }
    }

    public function chargeCardForCredits(User $user)
    {
        $voucherId = (string)$this->app->request->post('voucherId');
        $amountInCents = (int)$this->app->request->post('amountInCents');
        $paymentMethodNonce = (string)$this->app->request->post('paymentMethodNonce');

        // check if voucher exists

        try{
            $this->paymentService->chargeCardForCredits($user, $voucherId, $amountInCents, $paymentMethodNonce);
            $this->response->setSuccess(PaymentChargeCardForCredits::createSuccess())->returnJson();
        }catch (\Exception $e){
            $this->response->setErrorFromException(ErrorPrefix::APPLICATION_CONSUMER . ErrorPrefix::CONTROLLER_PAYMENT,
                $e)->returnJson();
        }

        return ;
    }
}
