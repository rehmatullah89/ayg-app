<?php

namespace App\Tablet\Services;

use App\Tablet\Entities\User;
use App\Tablet\Entities\UserRetailersAndSessionToken;
use App\Tablet\Exceptions\ActiveOrdersStillExistException;
use App\Tablet\Exceptions\BusinessClosingRequestAlreadySentException;
use App\Tablet\Exceptions\NonRetailerUserTriesToCloseBusinessException;
use App\Tablet\Exceptions\RetailerUserNotConfiguredCorrectlyException;
use App\Tablet\Exceptions\SignInBadCredentialsException;
use App\Tablet\Exceptions\SignOutFailException;
use App\Tablet\Exceptions\SignOutInvalidPasswordException;
use App\Tablet\Exceptions\UserHasNoRightsToUseTabletApplicationException;
use App\Tablet\Exceptions\TabletUserConnectedToMultipleRetailerException;
use App\Tablet\Exceptions\TabletReopenLevelNotMetException;
use App\Tablet\Exceptions\TabletUserRetailerConnectionNotFoundException;
use App\Tablet\Helpers\QueueMessageHelper;
use App\Tablet\Helpers\RetailerHelper;
use App\Tablet\Mappers\ParseUserIntoUserMapper;
use App\Tablet\Repositories\OrderRepositoryInterface;
use App\Tablet\Repositories\RetailerRepositoryInterface;
use Parse\ParseUser;

/**
 * Class UserService
 * @package App\Tablet\Services
 */
class UserService extends Service
{

    /**
     * @var RetailerRepositoryInterface
     */
    private $retailerRepository;
    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var QueueServiceInterface
     */
    private $queueService;

    public function __construct(
        RetailerRepositoryInterface $retailerRepository,
        OrderRepositoryInterface $orderRepository,
        QueueServiceInterface $queueService
    )
    {
        $this->retailerRepository = $retailerRepository;
        $this->orderRepository = $orderRepository;
        $this->queueService = $queueService;
    }

    /**
     * @param User $user
     * @param $password
     * @throws SignOutFailException
     * @throws SignOutInvalidPasswordException
     */
    public function signOut(User $user, $password)
    {
        // logout session
        $email = $user->getEmail();
        $typeOfLogin = $user->getTypeOfLogin();
        $passwordIsCorrect = $this->checkUserCredentials($email, $password, $typeOfLogin);

        if (!$passwordIsCorrect) {
            throw new SignOutInvalidPasswordException("Tablet user logout denied. Invalid Password Entered, user Id: " . $user->getId());
        }

        try {
            if ($user->getRetailerUserType() == User::USER_TYPE_RETAILER) {
                $retailers = $this->retailerRepository->getByTabletUserId($user->getId());
                $retailer = $retailers[0];

                // Upon sign out forces Tablet to be marked as Early Closed
                if (!isRetailerCloseEarlyForNewOrders($retailer->getUniqueId())) {
                    setRetailerClosedEarlyTimerMessage($retailer->getUniqueId(), getTabletOpenCloseLevelFromTablet());
                }

                $logRetailerLogoutMessage = QueueMessageHelper::getLogRetailerLogoutMessage($retailer->getUniqueId(), time());
                $this->queueService->sendMessage($logRetailerLogoutMessage, 0);
            }
            logoutUser($GLOBALS['user']->getObjectId(), $GLOBALS['user']->getSessionToken(), true, "", true);
        } catch (\Exception $ex) {
            throw new SignOutFailException($ex->getMessage());
        }
    }


    /**
     * @param $email
     * @param $password
     * @param $type
     * @param $deviceArray
     * @return UserRetailersAndSessionToken
     * @throws \Exception
     *
     * allows user to signin from device using email and password
     */
    public function signIn(
        $email,
        $password,
        $type,
        $deviceArray
    )
    {

        if(empty($email) || empty($password)) {

            // JMD            
            throw new SignInBadCredentialsException();
        }

        $error_array = loginUser($email, $password, $type, false);
        if (count_like_php5($error_array) > 0) {
            json_error($error_array["error_code"], $error_array["error_message_user"], $error_array["error_message_log"], $error_array["error_severity"], 1);
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
        $userId = $parseUser->getObjectId();

        if ($parseUser->get('hasTabletPOSAccess') !== true) {
            logoutUser($parseUser->getObjectId(), $parseUser->getSessionToken());
            throw new UserHasNoRightsToUseTabletApplicationException();
        }

        $retailers = $this->retailerRepository->getByTabletUserId($userId);

        if (empty($retailers)) {
            logoutUser($parseUser->getObjectId(), $parseUser->getSessionToken());
            throw new TabletUserRetailerConnectionNotFoundException();
        }

        if (!in_array($user->getRetailerUserType(), User::POSSIBLE_USER_TYPES)) {
            logoutUser($parseUser->getObjectId(), $parseUser->getSessionToken());
            throw new RetailerUserNotConfiguredCorrectlyException('User ' . $userId . ' has not correct RetailerUserType value');
        }

        // if user is retailer (not ops), we need to clear cache related with closing early request
        if (($user->getRetailerUserType() == User::USER_TYPE_RETAILER) && count_like_php5($retailers) == 1) {
            delRetailerCloseEarlyForNewOrders($retailers[0]->getUniqueId());

            $logRetailerLoginMessage = QueueMessageHelper::getLogRetailerLoginMessage($retailers[0]->getUniqueId(), time());
            $this->queueService->sendMessage($logRetailerLoginMessage, 0);
        }

        json_error("AS_20002", "", "Tablet User logged in: " . $email . " - " . $retailers[0]->getRetailerName(), 3, 1);

        return new UserRetailersAndSessionToken($user, $retailers, $standardResult['u']);
    }

    /**
     * @param User $user
     * @return int - number of seconds to close app
     * @throws ActiveOrdersStillExistException
     * @throws BusinessClosingRequestAlreadySentException
     * @throws NonRetailerUserTriesToCloseBusinessException
     * @throws TabletUserRetailerConnectionNotFoundException close business early
     * Retailer has possibility to close business for a day,
     * cache value is stored and no more orders can be ordered this day for this retailer
     *
     * it affects all retailers connected to a given user
     */
    public function closeBusinessEarlyByUser(User $user)
    {
        $retailers = $this->retailerRepository->getByTabletUserId($user->getId());

        if (empty($retailers)) {
            throw new TabletUserRetailerConnectionNotFoundException();
        }

        if ($user->getRetailerUserType() != User::USER_TYPE_RETAILER) {
            throw new NonRetailerUserTriesToCloseBusinessException();
        }

        $retailerIds = RetailerHelper::retailersListIntoRetailerIdsList($retailers);
        $orderListCount = $this->orderRepository->getBlockingEarlyCloseOrdersCountByRetailerIdList($retailerIds);

        if ($orderListCount > 0) {
            throw new ActiveOrdersStillExistException();
        }

        $retailer = $retailers[0];

        // check if it was closed early before, if it is return exception -> error
        if (\isRetailerCloseEarlyForNewOrders($retailer->getUniqueId())) {
            throw new BusinessClosingRequestAlreadySentException('Retailer ' . $retailer->getUniqueId() . ' request closing business multiple times');
        }

        return setRetailerClosedEarlyTimerMessage($retailer->getUniqueId(), getTabletOpenCloseLevelFromTablet());
    }

    /**
     * @param $userId
     * @return bool
     * @throws TabletUserConnectedToMultipleRetailerException
     * @throws TabletReopenLevelNotMetException
     * @throws TabletUserRetailerConnectionNotFoundException reopen closed early business
     * Retailer has possibility to close business for a day, this method reverse this action
     * previously created cache value is now deleted
     *
     * it affects all retailers connected to a given user
     */
    public function reopenBusinessAfterClosedEarlyByUserId($userId)
    {
        $retailers = $this->retailerRepository->getByTabletUserId($userId);

        if (empty($retailers)) {
            throw new TabletUserRetailerConnectionNotFoundException();
        }

        if (count_like_php5($retailers) > 1) {
            throw new TabletUserConnectedToMultipleRetailerException();
        }

        $retailer = $retailers[0];

        if(!canRetailerOpenAfterClosedEarly($retailer->getUniqueId(), getTabletOpenCloseLevelFromTablet())) {

            throw new TabletReopenLevelNotMetException();
        }

        setRetailerOpenAfterClosedEarly($retailer->getUniqueId(), getTabletOpenCloseLevelFromTablet());

        return true;
    }


    /**
     * @param $email
     * @param $password
     * @param $typeOfLogin
     * @return bool
     *
     * Check if user credentials are valid or not
     */
    private function checkUserCredentials($email, $password, $typeOfLogin)
    {
        // Login user to the values can be set
        try {
            $user = ParseUser::logIn(createUsernameFromEmail($email, $typeOfLogin), generatePasswordHash($password));
            logoutUser($user->getObjectId(), $user->getSessionToken());
            return true;
        } catch (\Exception $ex) {
            return false;
        }
    }
}
