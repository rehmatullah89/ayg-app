<?php
namespace App\Background\Services;


use App\Background\Entities\DeliveryUserUpdateStats;
use App\Background\Exceptions\DeliveryUserNotFoundException;
use App\Background\Repositories\DeliveryUserParseRepository;
use App\Background\Repositories\UserParseRepository;

class DeliveryService
{
    /**
     * @var UserParseRepository
     */
    private $userParseRepository;
    /**
     * @var DeliveryUserParseRepository
     */
    private $deliveryUserParseRepository;

    public function __construct(
        UserParseRepository $userParseRepository,
        DeliveryUserParseRepository $deliveryUserParseRepository
    ) {
        $this->userParseRepository = $userParseRepository;
        $this->deliveryUserParseRepository = $deliveryUserParseRepository;
    }

    public function addOrUpdateDeliveryUserData(
        string $deliveryUserEmail,
        string $deliveryUserPassword,
        string $firstName,
        string $lastName,
        string $comments,
        string $airportIataCode
    ) {
        $updated = false;
        $inserted = false;

        // check if there is delivery user with that email
        $user = $this->userParseRepository->getUserByEmail($deliveryUserEmail);

        if ($user !== null) {
            $user = $this->userParseRepository->updateDeliveryUser(
                $user,
                $deliveryUserPassword,
                $firstName,
                $lastName
            );
            try {
                $this->deliveryUserParseRepository->updateDeliveryUser(
                    $user,
                    $comments,
                    $airportIataCode
                );
            } catch (DeliveryUserNotFoundException $exception) {
                $this->deliveryUserParseRepository->addDeliveryUser(
                    $user,
                    $comments,
                    $airportIataCode
                );
            }
            $updated = true;
        } else {
            $user = $this->userParseRepository->addDeliveryUser(
                $deliveryUserEmail,
                $deliveryUserPassword,
                $firstName,
                $lastName
            );
            $this->deliveryUserParseRepository->addDeliveryUser(
                $user,
                $comments,
                $airportIataCode
            );
            $inserted = true;
        }

        return new DeliveryUserUpdateStats($airportIataCode, $user->getEmail(), $updated, $inserted);
    }
}
