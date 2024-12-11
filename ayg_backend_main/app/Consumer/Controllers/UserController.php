<?php

namespace App\Consumer\Controllers;

use App\Consumer\Entities\Coupon;
use App\Consumer\Entities\User;
use App\Consumer\Entities\UserProfileData;
use App\Consumer\Errors\ErrorPrefix;
use App\Consumer\Exceptions\UserPhoneDoesNotExistException;
use App\Consumer\Exceptions\UserPhoneVerifyException;
use App\Consumer\Exceptions\UserPhoneVerifyMaximumAttemptsException;
use App\Consumer\Helpers\CacheExpirationHelper;
use App\Consumer\Helpers\CacheKeyHelper;
use App\Consumer\Responses\InfoHelloWorldResponse;
use App\Consumer\Responses\OrderUserCreditResponse;
use App\Consumer\Responses\UserSessionDeviceResponse;
use App\Consumer\Responses\Response;
use App\Consumer\Responses\UserAddPhoneResponse;
use App\Consumer\Responses\UserAddProfileDataResponse;
use App\Consumer\Responses\UserCouponResponse;
use App\Consumer\Responses\UserSignInByPhoneResponse;
use App\Consumer\Responses\UserVerifyPhoneResponse;
use App\Consumer\Services\UserCouponService;
use App\Consumer\Services\UserCouponServiceFactory;
use App\Consumer\Services\UserCreditService;
use App\Consumer\Services\UserCreditServiceFactory;
use App\Consumer\Services\UserPhoneService;
use App\Consumer\Services\UserPhoneServiceFactory;
use App\Consumer\Services\UserService;
use App\Consumer\Services\UserAuthService;
use App\Consumer\Services\UserServiceFactory;
use App\Consumer\Services\UserAuthServiceFactory;

/**
 * Class UserController
 * @package App\Consumer\Controllers
 */
class UserController extends Controller
{
    /**
     * @var UserCouponService
     */
    private $userCouponService;

    /**
     * @var UserCreditService
     */
    private $userCreditService;

    /**
     * @var UserPhoneService
     */
    private $userPhoneService;

    /**
     * @var UserService
     */
    private $userService;

    /**
     * @var userAuthService
     */
    private $userAuthService;

    /**
     * UserController constructor.
     */
    public function __construct()
    {
        try {
            parent::__construct();
            $this->userCouponService = UserCouponServiceFactory::create($this->cacheService);
            $this->userCreditService = UserCreditServiceFactory::create($this->cacheService);
            $this->userPhoneService = UserPhoneServiceFactory::create($this->cacheService);
            $this->userService = UserServiceFactory::create($this->cacheService);
            $this->userAuthService = UserAuthServiceFactory::create();
        } catch (\Exception $e) {
            $this->response->setErrorFromException(ErrorPrefix::APPLICATION_CONSUMER . ErrorPrefix::CONTROLLER_USER,
                $e)->returnJson();
        }
    }

    public function signInByPhone(User $user, $apikey, $epoch, $sessionToken, $phoneId, $verifyCode)
    {
        try {
            $isUserAllowedToContinue = addToCountOfAttempt("VERIFYPHONE", $phoneId, 50, 60);
            if (!$isUserAllowedToContinue) {
                new UserPhoneVerifyMaximumAttemptsException();
            }

            $userPhone = $this->userPhoneService->getUserPhone($phoneId);
            if ($userPhone === null) {
                new UserPhoneDoesNotExistException();
            }
            if ($userPhone->getUserId() != $user->getId()) {
                new UserPhoneVerifyException('Phone does not belong to user');
            }

            $isUserPhoneVerified = $userPhone->isPhoneVerified();
            if (!$isUserPhoneVerified) {
                $isUserPhoneVerified = $this->userPhoneService->verifyPhone($userPhone, $verifyCode);
            }

            if (!$isUserPhoneVerified) {
                throw new UserPhoneVerifyException();
            }

            $userPhone->setPhoneAsVerified();
            $this->userService->signInByPhone($user, $userPhone, $sessionToken);

            $this->response->setSuccess(UserSignInByPhoneResponse::createSuccess())->returnJson();

        } catch (\Exception $e) {
            $this->response->setErrorFromException(
                ErrorPrefix::APPLICATION_CONSUMER . ErrorPrefix::CONTROLLER_USER,
                $e)->returnJson();
        }
    }

    public function addProfileData(User $user)
    {
        try {
            $email = $this->app->request()->post("email");
            $email = strtolower(sanitizeEmail(rawurldecode($email)));

            $userProfileData = new UserProfileData(
                $this->app->request()->post("firstName"),
                $this->app->request()->post("lastName"),
                $email
            );

            $this->userService->addProfileData($user, $userProfileData);
            $this->response->setSuccess(UserAddProfileDataResponse::createSuccess())->returnJson();

        } catch (\Exception $e) {
            $this->response->setErrorFromException(
                ErrorPrefix::APPLICATION_CONSUMER . ErrorPrefix::CONTROLLER_USER,
                $e)->returnJson();
        }
    }

    /**
     * @param User $user
     * @param $couponCode
     *
     * adds UserCoupon with step: signup
     */
    public function addCouponForSignup(User $user, $couponCode)
    {
        $this->promoCodeLimitGuard();

        try {
            $couponCode = strtolower($couponCode);
            $returnValue = $this->userCouponService->addCouponForSignup($user->getId(), $couponCode,
                Coupon::COUPON_STEP_SIGNUP);
            $response = UserCouponResponse::createFromSignupCouponCredit($returnValue);

            // Log coupon usage
            addCouponUsageByUser($user->getId(), $couponCode);
            addCouponUsageByCode($couponCode);

            $this->response->setSuccess($response)->returnJson();
        } catch (\Exception $e) {
            try {

                $invalidReasonLog = $e->getMessage();
                // JMD
                $workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueConsumerName']);

                $workerQueue->sendMessage(
                    array(
                        "action" => "log_user_action_signup_coupon_failed",
                        "content" =>
                            array(
                                "objectId" => $user->getId(),
                                "data" => json_encode(["coupon" => $couponCode, "reason" => $invalidReasonLog]),
                                "timestamp" => time()
                            )
                    )
                );
            } catch (Exception $ex2) {

                $response = json_decode($ex2->getMessage(), true);
                json_error($response["error_code"], "",
                    "Log user action queue message failed " . $response["error_message_log"], 1, 1);
            }

            $this->response->setErrorFromException(ErrorPrefix::APPLICATION_CONSUMER . ErrorPrefix::CONTROLLER_USER,
                $e)->returnJson();
        }
    }

    /**
     * @param User $user
     * @param $couponCode
     *
     * adds UserCoupon with step: signup
     */
    public function addCouponAfterSignup(User $user, $couponCode)
    {
        $this->promoCodeLimitGuard();

        try {
            $couponCode = strtolower($couponCode);

            if (isCouponForSignup($couponCode)[1]) {

                $returnValue = $this->userCouponService->addCouponAfterSignup($user->getId(), $couponCode,
                    Coupon::COUPON_STEP_SIGNUP);
            } else {

                $returnValue = $this->userCouponService->addCouponAfterSignup($user->getId(), $couponCode,
                    Coupon::COUPON_STEP_AFTERSIGNUP);
            }

            $response = UserCouponResponse::createFromSignupCouponCredit($returnValue);
            $this->response->setSuccess($response)->returnJson();
        } catch (\Exception $e) {
            $this->response->setErrorFromException(ErrorPrefix::APPLICATION_CONSUMER . ErrorPrefix::CONTROLLER_USER,
                $e)->returnJson();
        }
    }

    /**
     * @param User $user
     * @param $phoneCountryCode
     * @param $phoneNumber
     * @return void this is controller's method, it displays Json response
     *
     * url: /addPhoneWithTwilio/a/:apikey/e/:epoch/u/:sessionToken/phoneCountryCode/:phoneCountryCode/phoneNumber/:phoneNumber
     * Jira ticket: https://airportsherpa.atlassian.net/browse/CON-36
     *
     * this is used by customer user while signing up
     *
     * this endpoints adds phone to the user, It adds phone to the database and sends
     * verification code by text message using twilio, then user can user verifyPhoneWithTwilio endpoint to
     * verify his phone number
     */
    public function addPhoneWithTwilio(User $user, $phoneCountryCode, $phoneNumber)
    {
        try {
            $cacheKey = CacheKeyHelper::getCacheKeyByRoute($this->app->router()->getCurrentRoute());
            $this->returnRouteCacheValueIfExists($cacheKey);


            $returnValue = $this->userService->addPhoneWithTwilio($user, $phoneCountryCode, $phoneNumber);
            $response = UserAddPhoneResponse::createFromUserPhone($returnValue);

            // set cache if needed
            $this->cacheService->setCache(
                $cacheKey,
                $response,
                CacheExpirationHelper::getExpirationTimestampByRoute($this->app->router()->getCurrentRoute())
            );

            $this->response->setSuccess($response)->returnJson();

        } catch (\Exception $e) {
            $this->response->setErrorFromException(ErrorPrefix::APPLICATION_CONSUMER . ErrorPrefix::CONTROLLER_USER,
                $e)->returnJson();
        }
    }


    /**
     * @param User $user
     * @param $phoneId
     * @param $verifyCode
     * @return void this is controller's method, it displays Json response
     *
     * url: /verifyPhoneWithTwilio/a/:apikey/e/:epoch/u/:sessionToken/phoneId/:phoneId/verifyCode/:verifyCode
     * Jira ticket: https://airportsherpa.atlassian.net/browse/CON-36
     *
     * this is used by customer user while signing up
     *
     * this endpoints is for user to verify if phone number send earlier (addPhoneWithTwilio) is correct
     */
    public function verifyPhoneWithTwilio(User $user, $phoneId, $verifyCode)
    {
        $verifyPhoneResult = null;

        try {
            $verifyPhoneResult = $this->userService->verifyPhoneWithTwilio($user, $phoneId, $verifyCode);
            $response = UserVerifyPhoneResponse::createFromString($verifyPhoneResult);
            $this->response->setSuccess($response)->returnJson();
        } catch (\Exception $e) {
            $this->response->setErrorFromException(ErrorPrefix::APPLICATION_CONSUMER . ErrorPrefix::CONTROLLER_USER,
                $e)->returnJson();
        }
    }

    /**
     * @param User $user
     * @return void this method prints json response
     *
     * Jira ticket: MVP-1280
     * This method is called by route: POST /applyCreditsToUser/a/:apikey/e/:epoch/u/:sessionToken
     * Data in POST: 'creditsInCents', 'reasonForCredit', 'reasonForCreditCode', 'orderId', 'userId'
     *
     * This method allows Admin User to credit Consumer User for any reason
     * This method will return the Id of UserCredits thus created
     */
    public function applyCreditsToUser(User $user)
    {
        try {
            $creditsInCents = $this->app->request()->post("creditsInCents");
            $reasonForCredit = $this->app->request()->post("reasonForCredit");
            $reasonForCreditCode = $this->app->request()->post("reasonForCreditCode");
            $userId = $this->app->request()->post("userId");
            $orderId = !empty($this->app->request()->post("orderId")) ? $this->app->request()->post("orderId") : null;
            $creditsInCents = intVal($creditsInCents);
            $returnValue = $this->userCreditService->applyCreditsToUser($userId, $orderId, $creditsInCents,
                $reasonForCredit, $reasonForCreditCode);
            $response = OrderUserCreditResponse::createFromString($returnValue);
            $this->response->setSuccess($response)->returnJson();
        } catch (\Exception $e) {
            $this->response->setErrorFromException(ErrorPrefix::APPLICATION_CONSUMER . ErrorPrefix::CONTROLLER_ORDER,
                $e)->returnJson();
        }
    }

    private function promoCodeLimitGuard()
    {

        // Record attempt with a max of 10 attempts allowed per 120 mins
        $isUserAllowedToContinue = addToCountOfAttempt("SIGNUPROMO", $GLOBALS['user']->getObjectId(), 10, 120);

        // verify if max attempts reached
        if (!$isUserAllowedToContinue) {

            json_error("AS_487",
                "You have reached maximum attempts allowed. Please try again in two hours or you can skip this step.",
                "Max attempts for signup promo", 1);
        }
    }

    public function findCurrentUserActiveSessions($apikey, $epoch, $sessionToken)
    {
        try {
            $returnValue = $this->userAuthService->findUserActiveSessions($sessionToken, $GLOBALS['user']->getObjectId());
            $response = UserSessionDeviceResponse::createFromSessionDeviceList($returnValue);
            $this->response->setSuccess($response)->returnJson();
        } catch (\Exception $e) {
            $this->response->setErrorFromException(ErrorPrefix::APPLICATION_CONSUMER . ErrorPrefix::CONTROLLER_USER,
                $e)->returnJson();
        }
    }
}
