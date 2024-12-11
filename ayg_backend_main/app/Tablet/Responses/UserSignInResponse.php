<?php

namespace App\Tablet\Responses;

use App\Tablet\Entities\RetailerAppConfig;
use App\Tablet\Entities\User;
use App\Tablet\Entities\RetailerShortInfo;
use App\Tablet\Entities\UserRetailersAndSessionToken;
use App\Tablet\Helpers\ConfigHelper;

/**
 * Class UserSignInResponse
 */
class UserSignInResponse extends ControllerResponse implements \JsonSerializable
{
    /**
     * @var
     */
    private $sessionToken;
    /**
     * @var RetailerShortInfo
     */
    private $retailerShortInfo;

    /**
     * @var RetailerAppConfig
     */
    private $config;

    /**
     * UserSignInResponse constructor.
     * @param $sessionToken
     * @param RetailerShortInfo $retailerShortInfo - this info has a data about
     * retailer name,
     * location display name,
     * and logo url
     * @param RetailerAppConfig $config
     */
    public function __construct(
        $sessionToken,
        RetailerShortInfo $retailerShortInfo,
        RetailerAppConfig $config
    )
    {
        $this->sessionToken = $sessionToken;
        $this->retailerShortInfo = $retailerShortInfo;
        $this->config = $config;
    }

    /**
     * @param UserRetailersAndSessionToken $userRetailerAndSessionToken
     * @param RetailerAppConfig $retailerAppConfig
     * @return UserSignInResponse
     */
    public static function create(
        UserRetailersAndSessionToken $userRetailerAndSessionToken,
        RetailerAppConfig $retailerAppConfig
    )
    {
        $sessionToken = $userRetailerAndSessionToken->getSessionToken();
        $retailerShortInfo = null;

        // ops team
        if ($userRetailerAndSessionToken->getUser()->getRetailerUserType() == User::USER_TYPE_OPS_TEAM) {
            $retailerShortInfo = new RetailerShortInfo(
                $userRetailerAndSessionToken->getUser()->getFirstName() . ' ' . $userRetailerAndSessionToken->getUser()->getLastName(),
                '',
                ConfigHelper::get('env_TabletAppMultipleRetailerLogoUrl'),
                User::USER_TYPE_OPS_TEAM
            );
        }

        // retailer
        if ($userRetailerAndSessionToken->getUser()->getRetailerUserType() == User::USER_TYPE_RETAILER) {
            $retailer = $userRetailerAndSessionToken->getRetailers()[0];
            if ($retailer->getLocation() === null) {
                $locationDisplayName = null;
            } else {
                $locationDisplayName = $retailer->getLocation()->getGateDisplayName();
            }
            $retailerShortInfo = new RetailerShortInfo(
                $retailer->getRetailerName(),
                $locationDisplayName,
                $retailer->getImageLogo(),
                User::USER_TYPE_RETAILER
            );
        }

        return new UserSignInResponse($sessionToken, $retailerShortInfo, $retailerAppConfig);
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}