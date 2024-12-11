<?php

namespace App\Consumer\Services;


use App\Consumer\Entities\Payment;
use App\Consumer\Entities\User;
use App\Consumer\Exceptions\Exception;
use App\Consumer\Repositories\PaymentRepositoryInterface;
use App\Consumer\Repositories\UserCreditRepositoryInterface;
use App\Consumer\Repositories\VouchersRepositoryInterface;
use Braintree_Transaction;

class PaymentService extends Service
{

    /**
     * @var VouchersRepositoryInterface
     */
    private $vouchersRepository;
    /**
     * @var UserCreditRepositoryInterface
     */
    private $userCreditRepository;
    /**
     * @var PaymentRepositoryInterface
     */
    private $paymentRepository;

    public function __construct(
        VouchersRepositoryInterface $vouchersRepository,
        UserCreditRepositoryInterface $userCreditRepository,
        PaymentRepositoryInterface $paymentRepository
    ) {
        $this->vouchersRepository = $vouchersRepository;
        $this->userCreditRepository = $userCreditRepository;
        $this->paymentRepository = $paymentRepository;
    }


    public function chargeCardForCredits(
        User $user,
        string $voucherId,
        int $amountInCents,
        string $paymentMethodNonce
    ) {
        $voucher = $this->vouchersRepository->getActiveVoucherById($voucherId);
        if ($voucher == null) {
            throw new Exception('Voucher does not exist');
        }

        if ($voucher->getLimitInCents() < $amountInCents) {
            throw new Exception('Voucher Limit exceeded');
        }

        $payment = $this->paymentRepository->getPaymentByUserId($user->getId());
        if ($payment == null) {
            $customerId = createBraintreeCustomer();
            $payment = new Payment($user->getId(), $customerId);
        }

        $paymentNonce = $paymentMethodNonce;
        $paymentObject = Braintree_Transaction::sale([
            'amount' => $amountInCents / 100,
            'paymentMethodNonce' => $paymentNonce,
            'taxAmount' => 0.00,
            'orderId' => 'voucher',
            'customerId' => $payment->getExternalCustomerId(),
            'descriptor' => [
                'name' => "AYG*ccvoucherusage",
                'phone' => "844-266-4283"
            ],
            'customFields' => [
            ],
            'purchaseOrderNumber' => 'voucher',
            'options' => [
                'storeInVault' => false,
                'submitForSettlement' => true,
                'skipAvs' => true
            ]
        ]);


        if (!$paymentObject->success) {
            logResponse('BRAINTREE ERROR', false);
            logResponse(json_encode($paymentObject), false);
            throw new Exception('Transaction failed, please try again later');
        } else {
            // add credits
            $userCredit = $this->userCreditRepository->add(
                $user->getId(), null, $amountInCents, 0, 'Voucher ' . $voucher->getPartnerName(), 'VCH', null, null,
                null, null, true
            );
        }

        return $userCredit;
    }

}
