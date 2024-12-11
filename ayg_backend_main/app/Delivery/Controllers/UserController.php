<?php

namespace App\Delivery\Controllers;

use App\Delivery\Entities\User;
use App\Delivery\Errors\ErrorPrefix;
use App\Delivery\Helpers\EncryptionHelper;
use App\Delivery\Responses\UserSignInResponse;
use App\Delivery\Services\DeliveryServiceFactory;
use App\Delivery\Services\UserServiceFactory;
use App\Delivery\Responses\UserSignOutResponse;


class UserController extends Controller
{
    /**
     * @var \App\Delivery\Services\UserService
     */
    private $userService;
    /**
     * @var \App\Delivery\Services\DeliveryService
     */
    private $deliveryService;

    public function __construct()
    {
        try {
            parent::__construct();
            $this->userService = UserServiceFactory::create($this->cacheService);
            $this->deliveryService = DeliveryServiceFactory::create();
        } catch (\Exception $e) {
            $this->response->setErrorFromException(ErrorPrefix::APPLICATION_DELIVERY . ErrorPrefix::CONTROLLER_INTEGRATION,
                $e)->returnJson();
        }
    }

    public function signIn()
    {
        try {
            $email = strtolower(sanitizeEmail(($this->app->request()->post('email'))));
            $deviceArray = $this->app->request()->post('deviceArray');
            $password = $this->app->request()->post('password');
            $type = $this->app->request()->post('type');
            // JMD
            json_error("AS_20001", "", "Delivery User log in attempt: " . $email, 3, 1);

            $password = EncryptionHelper::decryptStringInMotion($password);
            $deviceArray = EncryptionHelper::decodeDeviceArray($deviceArray);
            $deviceArray["IPAddress"] = getenv('HTTP_X_FORWARDED_FOR') . ' ~ ' . getenv('REMOTE_ADDR');

            $userAndAirportIataCodeAndSessionToken = $this->userService->signIn($email, $password, $type, $deviceArray);
            $deliveryAppConfig = $this->deliveryService->getDeliveryAppConfig();

            $this->response->setSuccess(
                UserSignInResponse::create(
                    $userAndAirportIataCodeAndSessionToken,
                    $deliveryAppConfig
                ))->returnJson();

        } catch (\Exception $e) {
            $this->response->setErrorFromException(ErrorPrefix::APPLICATION_DELIVERY . ErrorPrefix::CONTROLLER_INTEGRATION,
                $e)->returnJson();
        }
    }

    public function signOut(User $user)
    {
        try {
            $this->userService->signOut($user);
            $this->response->setSuccess(UserSignOutResponse::createFromBool(true))->returnJson();
        } catch (\Exception $e) {
            $this->response->setErrorFromException(ErrorPrefix::APPLICATION_DELIVERY . ErrorPrefix::CONTROLLER_USER,
                $e)->returnJson();
        }
    }
}
