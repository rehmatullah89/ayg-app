<?php

namespace App\Tablet\Controllers;

use App\Tablet\Entities\User;
use App\Tablet\Errors\ErrorPrefix;
use App\Tablet\Exceptions\SignOutPasswordNotEncryptedException;
use App\Tablet\Helpers\EncryptionHelper;
use App\Tablet\Responses\Response;
use App\Tablet\Responses\UserCloseBusinessResponse;
use App\Tablet\Responses\UserReopenClosedBusinessResponse;
use App\Tablet\Responses\UserSignInResponse;
use App\Tablet\Responses\UserSignOutResponse;
use App\Tablet\Services\RetailerService;
use App\Tablet\Services\RetailerServiceFactory;
use App\Tablet\Services\UserService;
use App\Tablet\Services\UserServiceFactory;

/**
 * Class UserController
 * @package App\Tablet\Controllers
 */
class UserController extends Controller
{
    /**
     * @var UserService
     */
    private $userService;

    /**
     * @var RetailerService
     */
    private $retailerService;

    /**
     * UserController constructor.
     */
    public function __construct()
    {
        try {
            parent::__construct();
            $this->userService = UserServiceFactory::create($this->cacheService);
            $this->retailerService = RetailerServiceFactory::create($this->cacheService);
        } catch (\Exception $e) {
            $this->response->setErrorFromException(ErrorPrefix::APPLICATION_TABLET . ErrorPrefix::CONTROLLER_INTEGRATION, $e)->returnJson();
        }
    }

    /**
     * @return void - controller's method prints json response
     *
     * Jira Ticket - https://airportsherpa.atlassian.net/browse/MVP-1268
     * /user/signin/a/:apikey/e/:epoch/u/:sessionToken
     * used by: TabletUser
     * method: POST
     * params: email, deviceArray, password, type
     *
     * logs user in, use loginUser function - the same that is used in consumer app
     */
    public function signIn()
    {
        try {
            $email = strtolower(sanitizeEmail(($this->app->request()->post('email'))));
            $deviceArray = $this->app->request()->post('deviceArray');
            $password = $this->app->request()->post('password');
            $type = $this->app->request()->post('type');
            // JMD
            json_error("AS_20001", "", "Tablet User log in attempt: " . $email, 3, 1);

            $password = EncryptionHelper::decryptStringInMotion($password);
            $deviceArray = EncryptionHelper::decodeDeviceArray($deviceArray);
            $deviceArray["IPAddress"] = getenv('HTTP_X_FORWARDED_FOR') . ' ~ ' . getenv('REMOTE_ADDR');

            $userRetailersAndSessionToken = $this->userService->signIn($email, $password, $type, $deviceArray);
            $retailerAppConfig = $this->retailerService->getRetailerAppConfig();

            $this->response->setSuccess(UserSignInResponse::create($userRetailersAndSessionToken,$retailerAppConfig))->returnJson();

        } catch (\Exception $e) {
            $this->response->setErrorFromException(ErrorPrefix::APPLICATION_TABLET . ErrorPrefix::CONTROLLER_INTEGRATION, $e)->returnJson();
        }
    }

    /**
     * @param User $user
     * @return void - controller's method prints json response
     *
     * Jira Ticket - https://airportsherpa.atlassian.net/browse/MVP-1268
     * /user/signout/a/:apikey/e/:epoch/u/:sessionToken
     * used by: TabletUser
     * method: GET
     *
     * logs user out
     */
    public function signOut(User $user)
    {
        try {
            $password = $this->app->request()->post('password');
            try {
                $password = EncryptionHelper::decryptStringInMotion($password);
            } catch (\Exception $e) {
                throw new SignOutPasswordNotEncryptedException('user ' . $user->getId() . ' provided not encrypted password while signing out');
            }
            $this->userService->signOut($user, $password);
            $this->response->setSuccess(UserSignOutResponse::createFromBool(true))->returnJson();
        } catch (\Exception $e) {
            $this->response->setErrorFromException(ErrorPrefix::APPLICATION_TABLET . ErrorPrefix::CONTROLLER_INTEGRATION, $e)->returnJson();
        }
    }

    /**
     * Jira Ticket - https://airportsherpa.atlassian.net/browse/RET-60
     * Close business
     * @param User $user
     *
     * close business early
     * Retailer has possibility to close business for a day,
     * cache value is stored and no more orders can be ordered this day for this retailer
     *
     * it affects all retailers connected to a logged user
     */
    public function closeBusiness(User $user)
    {
        try {
            // get active orders, if any - throw exception

            $numberOfSecondsToClose = $this->userService->closeBusinessEarlyByUser($user);
            $this->response->setSuccess(new UserCloseBusinessResponse($numberOfSecondsToClose))->returnJson();
        } catch (\Exception $e) {
            $this->response->setErrorFromException(ErrorPrefix::APPLICATION_TABLET . ErrorPrefix::CONTROLLER_INTEGRATION, $e)->returnJson();
        }
    }

    /**
     * Jira Ticket - https://airportsherpa.atlassian.net/browse/RET-60
     * Reopen Early Closed business
     * @param User $user
     *
     * reopen closed early business
     * Retailer has possibility to close business for a day, this endpoint reverse this action
     * previously created cache value is now deleted
     *
     * it affects all retailers connected to a logged user
     */
    public function reopenBusiness(User $user)
    {
        try {
            $response = $this->userService->reopenBusinessAfterClosedEarlyByUserId($user->getId());
            $this->response->setSuccess(new UserReopenClosedBusinessResponse($response))->returnJson();
        } catch (\Exception $e) {
            $this->response->setErrorFromException(ErrorPrefix::APPLICATION_TABLET . ErrorPrefix::CONTROLLER_INTEGRATION, $e)->returnJson();
        }
    }
}
