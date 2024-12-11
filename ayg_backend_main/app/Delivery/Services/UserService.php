<?php

namespace App\Delivery\Services;

use App\Delivery\Entities\User;
use App\Delivery\Entities\UserAndAirportIataCodeAndSessionToken;
use App\Delivery\Exceptions\SignInBadCredentialsException;
use App\Delivery\Exceptions\UserHasNoRightsToUseDeliveryApplicationException;
use App\Delivery\Mappers\ParseUserIntoUserMapper;
use App\Delivery\Exceptions\SignOutFailException;
use App\Delivery\Repositories\DeliveryUserRepositoryInterface;
use Parse\ParseUser;

class UserService extends Service
{
    /**
     * @var DeliveryUserRepositoryInterface
     */
    private $deliveryUserRepository;

    public function __construct(
        DeliveryUserRepositoryInterface $deliveryUserRepository
    )
    {
        $this->deliveryUserRepository = $deliveryUserRepository;
    }

    public function signIn(
        $email,
        $password,
        $type,
        $deviceArray
    ) {
        if (empty($email) || empty($password)) {
            throw new SignInBadCredentialsException();
        }

        $error_array = loginUser($email, $password, $type, false);
        if (count_like_php5($error_array) > 0) {
            json_error($error_array["error_code"], $error_array["error_message_user"],
                $error_array["error_message_log"], $error_array["error_severity"], 1);
            if ($error_array['error_code'] == 'AS_020') {
                throw new SignInBadCredentialsException();
            }
            throw new \Exception($error_array["error_message_user"]);
        } // Success

        $standardResult = afterSignInSuccess($email, $type, $deviceArray);

        /**
         * @var $parseUser ParseUser
         */
        $parseUser = ParseUser::getCurrentUser();
        $user = ParseUserIntoUserMapper::map($parseUser);

        if ($parseUser->get('hasDeliveryAccess') !== true) {
            logoutUser($parseUser->getObjectId(), $parseUser->getSessionToken());
            throw new UserHasNoRightsToUseDeliveryApplicationException();
        }

        $airportIataCode = $this->deliveryUserRepository->getDeliveryUserAirportIataCode($user->getId());

        return new UserAndAirportIataCodeAndSessionToken(
            $user,
            $airportIataCode,
            $standardResult['u']
        );
    }


    public function signOut(User $user)
    {
        try {
            logoutUser($GLOBALS['user']->getObjectId(), $GLOBALS['user']->getSessionToken(), true, "", true);
        } catch (\Exception $ex) {
            throw new SignOutFailException($ex->getMessage());
        }
    }
}
