<?php

namespace App\Consumer\Services;

use App\Consumer\Exceptions\OrderNotFoundException;
use App\Consumer\Exceptions\UserIsNotConsumerException;
use App\Consumer\Exceptions\UserNotFoundException;
use App\Consumer\Repositories\HelloWorldRepositoryInterface;
use App\Consumer\Repositories\OrderRepositoryInterface;
use App\Consumer\Repositories\UserCreditRepositoryInterface;
use App\Consumer\Repositories\UserRepositoryInterface;

/**
 * Class OrderService
 * @package App\Consumer\Services
 */
class UserCreditService extends Service
{
    /**
     * @var UserCreditRepositoryInterface
     */
    private $userCreditRepository;

    /**
     * @var UserRepositoryInterface
     */
    private $userRepository;
    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;


    /**
     * OrderService constructor.
     * @param UserCreditRepositoryInterface $userCreditRepository
     * @param UserRepositoryInterface $userRepository
     * @param OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        UserCreditRepositoryInterface $userCreditRepository,
        UserRepositoryInterface $userRepository,
        OrderRepositoryInterface $orderRepository
    )
    {
        $this->userCreditRepository = $userCreditRepository;
        $this->userRepository = $userRepository;
        $this->orderRepository = $orderRepository;
    }

    /**
     * @param $userId
     * @param int|null $orderId
     * @param $creditsInCents
     * @param $reasonForCredit
     * @param $reasonForCreditCode
     * @return string
     * @throws OrderNotFoundException
     * @throws UserIsNotConsumerException
     * @throws UserNotFoundException
     *
     *
     * Applies the credit in cents for the user to Parse Repository
     * This method will return the Id of UserCredit
     */
    public function applyCreditsToUser($userId, $orderId, $creditsInCents, $reasonForCredit, $reasonForCreditCode)
    {
        $user = $this->userRepository->getUserById($userId);

        if (empty($user)) {
            throw new UserNotFoundException("User Not Found. UserId ".$userId);
        }

        if (!$user->hasConsumerAccess()) {
            throw new UserIsNotConsumerException("User is not a Consumer. UserId ".$userId);
        }

        if ($orderId !== null) {
            $result = $this->orderRepository->checkIfOrderExistsForAGivenUser($orderId, $userId);

            if (!$result) {
                throw new OrderNotFoundException("Order Not Found");
            }


        }

        $userCredit = $this->userCreditRepository->add($userId, $orderId, $creditsInCents, null, $reasonForCredit, $reasonForCreditCode, null, null, null, null);
        return $userCredit->getId();
    }
}
