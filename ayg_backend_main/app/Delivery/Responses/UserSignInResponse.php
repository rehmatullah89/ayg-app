<?php

namespace App\Delivery\Responses;

use App\Delivery\Entities\DeliveryAppConfig;
use App\Delivery\Entities\User;
use App\Delivery\Entities\UserAndAirportIataCodeAndSessionToken;
use App\Delivery\Entities\UserShortInfo;
use App\Delivery\Helpers\ConfigHelper;
use App\Delivery\Mappers\UserIntoUserShortInfoMapper;

/**
 * Class UserSignInResponse
 */
class UserSignInResponse extends ControllerResponse implements \JsonSerializable
{
    private $sessionToken;
    private $deliveryAppConfig;
    /**
     * @var UserShortInfo
     */
    private $loggedUser;
    /**
     * @var string
     */
    private $associatedAirportIataCode;


    public function __construct(
        $sessionToken,
        DeliveryAppConfig $deliveryAppConfig,
        UserShortInfo $loggedUser,
        string $associatedAirportIataCode
    ) {
        $this->sessionToken = $sessionToken;
        $this->deliveryAppConfig = $deliveryAppConfig;
        $this->loggedUser = $loggedUser;
        $this->associatedAirportIataCode = $associatedAirportIataCode;
    }

    public static function create(
        UserAndAirportIataCodeAndSessionToken $userAndAirportIataCodeAndSessionToken,
        DeliveryAppConfig $deliveryAppConfig
    ) {
        $sessionToken = $userAndAirportIataCodeAndSessionToken->getSessionToken();
        $loggedUser = UserIntoUserShortInfoMapper::map($userAndAirportIataCodeAndSessionToken->getUser());
        $associatedAirportIataCode = $userAndAirportIataCodeAndSessionToken->getAirportIataCode();
        return new UserSignInResponse($sessionToken, $deliveryAppConfig, $loggedUser, $associatedAirportIataCode);
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}
