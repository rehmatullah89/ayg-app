<?php
namespace App\Consumer\Services;

use App\Consumer\Entities\PhoneNumberInput;
use App\Consumer\Entities\User;
use App\Consumer\Entities\UserIdentifier;
use App\Consumer\Entities\UserPhone;
use App\Consumer\Entities\UserProfileData;
use App\Consumer\Entities\UserSession;
use App\Consumer\Exceptions\Exception;
use App\Consumer\Exceptions\TwilioCodeExpiredException;
use App\Consumer\Exceptions\TwilioCodeNotValidException;
use App\Consumer\Exceptions\TwilioSendSmsException;
use App\Consumer\Exceptions\UserPhoneAddMaximumAttemptsException;
use App\Consumer\Exceptions\UserPhoneExistingActivePhoneException;
use App\Consumer\Exceptions\UserPhoneVerifyMaximumAttemptsException;
use App\Consumer\Exceptions\UserWithSameEmailExistsException;
use App\Consumer\Helpers\CacheExpirationHelper;
use App\Consumer\Helpers\CacheKeyHelper;
use App\Consumer\Helpers\PhoneNumberHelper;
use App\Consumer\Helpers\UserHelper;
use App\Consumer\Mappers\UserPhoneFactory;
use App\Consumer\Repositories\FlightTripRepositoryInterface;
use App\Consumer\Repositories\OrderRepositoryInterface;
use App\Consumer\Repositories\UserCustomIdentifierRepositoryInterface;
use App\Consumer\Repositories\UserCustomSessionRepositoryInterface;
use App\Consumer\Repositories\UserPhoneRepositoryInterface;
use App\Consumer\Repositories\UserRepositoryInterface;
use Monolog\DateTimeImmutable;
use Parse\ParseObject;
use Parse\ParseQuery;
use Parse\ParseUser;

/**
 * Class UserService
 * @package App\Consumer\Services
 */
class UserService extends Service
{
    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;
    /**
     * @var UserRepositoryInterface
     */
    private $userRepository;

    /**
     * @var TwilioService
     */
    private $twilioService;

    /**
     * @var UserCustomIdentifierRepositoryInterface
     */
    private $customIdentifierRepository;
    /**
     * @var UserCustomSessionRepositoryInterface
     */
    private $customSessionRepository;

    /**
     * @var UserPhoneRepositoryInterface
     */
    private $userPhoneRepository;
    /**
     * @var CacheService
     */
    private $cacheService;
    /**
     * @var FlightTripRepositoryInterface
     */
    private $flightTripRepository;

    /**
     * UserService constructor.
     * @param FlightTripRepositoryInterface $flightTripRepository
     * @param OrderRepositoryInterface $orderRepository
     * @param UserRepositoryInterface $userRepository
     * @param UserPhoneRepositoryInterface $userPhoneRepository
     * @param UserCustomIdentifierRepositoryInterface $customIdentifierRepository
     * @param UserCustomSessionRepositoryInterface $customSessionRepository
     * @param TwilioService $twilioService
     * @param CacheService $cacheService
     */
    public function __construct(
        FlightTripRepositoryInterface $flightTripRepository,
        OrderRepositoryInterface $orderRepository,
        UserRepositoryInterface $userRepository,
        UserPhoneRepositoryInterface $userPhoneRepository,
        UserCustomIdentifierRepositoryInterface $customIdentifierRepository,
        UserCustomSessionRepositoryInterface $customSessionRepository,
        TwilioService $twilioService,
        CacheService $cacheService
    ) {
        $this->flightTripRepository = $flightTripRepository;
        $this->orderRepository = $orderRepository;
        $this->userRepository = $userRepository;
        $this->userPhoneRepository = $userPhoneRepository;
        $this->twilioService = $twilioService;
        $this->cacheService = $cacheService;
        $this->customIdentifierRepository = $customIdentifierRepository;
        $this->customSessionRepository = $customSessionRepository;
    }

    /**
     * @param User $user
     * @param $phoneCountryCode
     * @param $phoneNumber
     * @return UserPhone
     * @throws TwilioSendSmsException generates random code
     * adds new row in the database with phone number and generated code
     * sends text message by twilio to the given phone number
     * returns UserPhone Entity (values added to the database)
     *
     * When the user Phone already exists for a given user, it refresh code
     * and send the sms code again
     * @throws UserPhoneAddMaximumAttemptsException
     * @throws UserPhoneExistingActivePhoneException
     */
    public function addPhoneWithTwilio(User $user, $phoneCountryCode, $phoneNumber)
    {
        $amountOfAttempts = $this->cacheService->increaseAttempts(
            CacheKeyHelper::getUserVerifyPhoneAttemptCounterKey($phoneCountryCode . $phoneNumber),
            CacheExpirationHelper::getExpirationInSecondsByMethodName(__METHOD__)
        );

        if ($amountOfAttempts > UserPhone::MAX_AMOUNT_OF_ADD_ATTEMPTS) {
            throw new UserPhoneAddMaximumAttemptsException('Max attempts for Phone Add reached. User ' . $user->getId() . ', phone number ' . $phoneCountryCode . $phoneNumber);
        }


        $twilioCode = UserHelper::generateTwilioCode();

        $phoneNumberInput = new PhoneNumberInput([
            'phoneCountryCode' => $phoneCountryCode,
            'phoneNumber' => $phoneNumber
        ]);

        // check if there is already added active phone number
        $activePhoneNumberCount = $this->userPhoneRepository->getActiveUserPhoneCountByUserId($user->getId());
        if ($activePhoneNumberCount > 0) {
            throw new UserPhoneExistingActivePhoneException('No additional phones can be added as an active one already exists. User ' . $user->getId());
        }

        // gets User phone (null if not found)
        $userPhone = $this->userPhoneRepository->get($user->getId(), $phoneNumberInput);

        $newPhoneAdded = false;
        if ($userPhone === null) {
            $newPhoneAdded = true;
            $userPhone = $this->userPhoneRepository->add($user->getId(), $phoneNumberInput);
        }

        try {

            // send sms
            $this->twilioService->sendSms(PhoneNumberHelper::removeNonDigitsAndNonPlus($phoneNumberInput->getFullNumber()),
                $twilioCode);

            $this->setUserVerifyPhoneCodeIntoCache($userPhone->getId(), $twilioCode);

            // update formatted number data for new userPhone
            if ($newPhoneAdded) {
                // lookup for formatted data
                $phoneNumberFormatted = $this->twilioService->lookupForFormattedNumber($phoneCountryCode . $phoneNumber);

                // update in the database
                $userPhone = $this->userPhoneRepository->update($userPhone, [
                    'phoneNumberFormatted' => $phoneNumberFormatted->getNationalFormat(),
                    'phoneCarrier' => $phoneNumberFormatted->getCarrierName(),
                ]);

            }
        } catch (\Exception $e) {
            $this->userPhoneRepository->deleteUserPhone($userPhone);
            throw new TwilioSendSmsException($e->getMessage());
        }


        return $userPhone;
    }

    /**
     * @param User $user
     * @param $phoneId
     * @param $verifyCode
     * @return bool
     * @throws TwilioCodeExpiredException
     * @throws TwilioCodeNotValidException
     * @throws UserPhoneVerifyMaximumAttemptsException
     *
     * verifies text message code, return true if it is ok
     * throw exceptions if it is an error
     */
    public function verifyPhoneWithTwilio(User $user, $phoneId, $verifyCode)
    {
        $amountOfAttempts = $this->cacheService->increaseAttempts(
            CacheKeyHelper::getUserVerifyPhoneAttemptCounterKey($phoneId),
            CacheExpirationHelper::getExpirationInSecondsByMethodName(__METHOD__)
        );

        if ($amountOfAttempts > UserPhone::MAX_AMOUNT_OF_VERIFY_ATTEMPTS) {
            throw new UserPhoneVerifyMaximumAttemptsException('Max attempts for Phone Verify reached. User ' . $user->getId() . ', phoneId ' . $phoneId);
        }

        $cacheCode = $this->getUserVerifyPhoneCodeFromCache($phoneId);

        if ($cacheCode === null) {
            throw new TwilioCodeExpiredException();
        } else {
            if ($cacheCode != $verifyCode) {
                throw new TwilioCodeNotValidException();
            }
        }

        $this->userPhoneRepository->verifyPhone($user->getId(), $phoneId, $verifyCode);

        return true;
    }

    public function addProfileData(User $user, UserProfileData $userProfileData)
    {
        $userWithTheSameEmail = $this->userRepository->getUserByEmailOtherThenId(
            $userProfileData->getEmail(),
            $user->getId()
        );

        if ($userWithTheSameEmail !== null) {
            throw new UserWithSameEmailExistsException();
        }

        $user->setFirstName($userProfileData->getFirstName());
        $user->setLastName($userProfileData->getLastName());
        $user->setEmail($userProfileData->getEmail());
        $this->userRepository->updateProfileData($user);
    }


    /**
     * @param $userPhoneId
     * @param $code
     */
    private function setUserVerifyPhoneCodeIntoCache($userPhoneId, $code)
    {
        $cacheKey = CacheKeyHelper::getUserVerifyPhoneKey($userPhoneId);
        $this->cacheService->setCache($cacheKey, $code,
            CacheExpirationHelper::getExpirationTimestampByMethodName(__METHOD__));
    }

    /**
     * @param $userPhoneId
     * @return mixed
     */
    private function getUserVerifyPhoneCodeFromCache($userPhoneId)
    {
        $cacheKey = CacheKeyHelper::getUserVerifyPhoneKey($userPhoneId);
        return $this->cacheService->getCache($cacheKey);
    }

    public function switchFlightTripOwner(string $guestParseUserId, string $existingUserParseUserId)
    {
        $this->flightTripRepository->switchFlightTripOwner($guestParseUserId, $existingUserParseUserId);
    }

    public function signInByPhone(User $user, UserPhone $userPhone, string $currentSessionToken)
    {
        // @todo think about transactions
        $currentUserSession = $this->customSessionRepository->getBySessionToken($currentSessionToken);

        if (!$currentUserSession->isActive() || !$currentUserSession->getUserIdentifier()->isActive()) {
            throw new \Exception('we can only signIn Active Users');
        }


        // check if there was a user before that had the same phone,
        // if so - login as him, and deactivate guest user
        $userIdentityList = $this->customIdentifierRepository->findActiveVerifiedByPhone($userPhone);

        if (!$userIdentityList->checkIfAllHasTheSameUserId()) {
            throw new \Exception('User Identifiers conflict');
        }


        $userIdentity = null;
        if (count($userIdentityList) != 0) {
            $userIdentity = $userIdentityList->getLast();
        } else {
            $parseUserPhones = $this->getVerifiedActiveUserPhonesWithUsers($userPhone->getPhoneCountryCode(),
                $userPhone->getPhoneNumber());
            $userConnectedToPhone = $this->selectLastActiveUserByUserPhones($parseUserPhones);

            if ($userConnectedToPhone !== null) {
                $userIdentity = new UserIdentifier(
                    null,
                    $currentUserSession->getUserIdentifier()->getDeviceIdentifier(),
                    $userPhone->getPhoneCountryCode(),
                    $userPhone->getPhoneNumber(),
                    $userConnectedToPhone->getObjectId(),
                    false
                );
                $this->customIdentifierRepository->add($userIdentity);
            }
        }


        // there is an user used before for that number, we need to switch guest user with user that we found
        if ($userIdentity !== null) {

            $guestParseUserId = $currentUserSession->getUserIdentifier()->getParseUserId();
            $existingUserParseUserId = $userIdentity->getParseUserId();

            $this->customIdentifierRepository->deactivateUserByUserIdentifierId($currentUserSession->getUserIdentifier()->getId());

            $fromUserId = $currentUserSession->getUserIdentifier()->getParseUserId();
            $toUserId = $userIdentity->getParseUserId();

            // switch session to use old identity
            $currentUserSession->setUserIdentifier($userIdentity);
            $currentUserSession->setSessionHasFullAccess();
            $this->customSessionRepository->save($currentUserSession);

            // switch sessionDevice to use old identity
            $this->switchSessionDeviceUser($fromUserId, $toUserId, $currentUserSession->getTokenWithoutTypeIndicator());
            $this->deactivateSessionDeviceForUserOtherThenGivenToken($toUserId,
                $currentUserSession->getTokenWithoutTypeIndicator());

            $userIdentity->changePhoneData($userPhone);
            $this->customIdentifierRepository->save($userIdentity);

            $this->orderRepository->abandonOpenOrdersByUserId($existingUserParseUserId);
            $this->orderRepository->switchCartOwner($guestParseUserId, $existingUserParseUserId);

            $this->flightTripRepository->switchFlightTripOwner($guestParseUserId, $existingUserParseUserId);

            // as phone has been verified for guest, we need to inactivate guest user phone
            $this->inactivateUserPhoneDueToUserSwitch($userPhone, $fromUserId, $toUserId);

            $realUserPhone = UserPhoneFactory::mapFromArray([
                'phoneNumber' => $userPhone->getPhoneNumber(),
                'phoneCountryCode' => $userPhone->getPhoneCountryCode(),
                'isVerified' => true,
                'isActive' => true,
                'userId' => $toUserId
            ]);

            $this->activePhone($realUserPhone);

        } else {
            // in case another custom identifiers used that phone number, we are making them inactive
            $userIdentityList = $this->customIdentifierRepository->findByPhone($userPhone);
            /**
             * @var UserIdentifier $item
             */
            foreach ($userIdentityList as $item) {
                $this->customSessionRepository->deactivateSessionByUserIdentifierId($item->getId());
                $this->customIdentifierRepository->deactivateUserByUserIdentifierId($item->getId());
            }

            // set back current session as active and set full access
            $currentUserSession->setSessionHasFullAccess();
            $currentUserIdentity = $currentUserSession->getUserIdentifier();
            $currentUserIdentity->changePhoneData($userPhone);

            $this->customSessionRepository->save($currentUserSession);
            $this->customIdentifierRepository->save($currentUserIdentity);


            $parseUser = $this->userRepository->getParseUserById($currentUserIdentity->getParseUserId());
            $this->attachReferraCode($parseUser);
            $this->sendRegistrationEmail($parseUser);

            $this->activePhone($userPhone);
        }

        // due to web interface we decided to DO NOT logout other devices
        //$this->logoutOtherDevices($currentUserSession);
    }

    private function logoutOtherDevices(UserSession $currentUserSession)
    {

        //////////////////////////////////////////////////////////////////////////////////////////
        // Logout all other sessions for this user
        // List all existing active Session Devices
        $objSessionDevices = parseExecuteQuery([
            "isActive" => true,
            "__NE__sessionTokenRecall" => $currentUserSession->getTokenWithoutTypeIndicator(),
            "user" => $GLOBALS['user']
        ], "SessionDevices");

        // Traverse through all SessionDevice
        // Expire existing Session Devices and logout all sessions
        $sessionsCleared = [];
        foreach ($objSessionDevices as $obj) {

            $obj->set("sessionEndTimestamp", time());
            $obj->set("isActive", false);
            $obj->save();

            if (!in_array($currentUserSession->getToken(), $sessionsCleared)) {

                try {

                    if (\App\Consumer\Helpers\UserAuthHelper::isSessionTokenFromCustomSessionManagementWithoutTypeIndicator($obj->get('sessionTokenRecall'))) {
                        // set session as inactive
                        // set Parse SessionDevices as inactive
                        $userAuthService = \App\Consumer\Services\UserAuthServiceFactory::create();

                        $userAuthService->deactivateCustomSessionAndDeactivateParseSessionDevicesBySessionTokenWithoutTypeIndicator($obj->get('sessionTokenRecall'));
                    } else {
                        logoutUser($GLOBALS['user']->getObjectId(), $obj->get('sessionTokenRecall'), false,
                            $currentUserSession->getToken());
                    }
                } catch (Exception $ex) {

                    json_error("AS_443", "",
                        "Old session clean up failed for signin userId = 
                        " . $currentUserSession->getUserIdentifier()->getParseUserId() .
                        ' sessionToken = ' . $obj->get('sessionTokenRecall') .
                        " - " . $ex->getMessage()
                        . $ex->getLine()
                        . $ex->getFile()
                        . $ex->getTraceAsString()
                        ,
                        2, 1);
                }
            }

            $sessionsCleared[] = $currentUserSession->getToken();
        }
    }

    private function attachReferraCode(ParseObject $parseUser)
    {
        generateReferralCode($parseUser);
    }

    private function sendRegistrationEmail(ParseObject $parseUser)
    {
        // Create row in UserDevices
        $userDeviceQuery = new ParseQuery("UserDevices");
        $userDeviceQuery->equalTo('user', $parseUser);
        $userDeviceQuery->addDescending('createdAt');
        $userDeviceQuery->limit(1);
        $userDevices = $userDeviceQuery->find();

        if (count($userDevices) != 1) {
            throw new \Exception('User Device not found');
        }

        $objUserDevice = $userDevices[0];

        try {
            //$workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueConsumerName']);
            $workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueEmailConsumerName']);
            $workerQueue->sendMessage(
                array(
                    "action" => "email_verify_on_signup",
                    "content" =>
                        array(
                            "objectId" => $parseUser->getObjectId(),
                            "app" => $objUserDevice->get('isIos') == true ? 'iOS' : 'Android'
                        )
                ),
                120 // delay to allow the promo code to be captured by then
            );

        } catch (Exception $ex) {

            $response = json_decode($ex->getMessage(), true);
            json_error($response["error_code"], "",
                "User signup failed! " . $response["error_message_log"], 1);
        }
    }

    private function switchSessionDeviceUser(string $fromUserId, string $toUserId, string $sessionToken)
    {
        $userFromInnerQuery = new ParseQuery('_User');
        $userFromInnerQuery->equalTo("objectId", $fromUserId);
        $userToInnerQuery = new ParseQuery('_User');

        $userToInnerQuery->equalTo("objectId", $toUserId);
        $userToInnerQueryResult = $userToInnerQuery->find();
        if (empty($userToInnerQueryResult) || $userToInnerQueryResult === false || !is_array($userToInnerQueryResult)) {
            throw new \Exception('User not Found');
        }
        $userToInnerQueryResult = $userToInnerQueryResult[0];

        // Create row in UserDevices
        $userDeviceQuery = new ParseQuery("SessionDevices");
        $userDeviceQuery->matchesQuery("user", $userFromInnerQuery);
        $userDeviceQuery->equalTo('sessionTokenRecall', $sessionToken);
        $userDeviceQuery->equalTo('isActive', true);
        $userDeviceQuery->addDescending('createdAt');
        $records = $userDeviceQuery->find();

        foreach ($records as $sessionDeviceObject) {
            $sessionDeviceObject->set('user', $userToInnerQueryResult);
            $sessionDeviceObject->save();
        }
    }

    private function getVerifiedActiveUserPhonesWithUsers(int $countryCode, int $phoneNumber): array
    {
        // Create row in UserDevices
        $userPhonesQuery = new ParseQuery("UserPhones");
        $userPhonesQuery->equalTo('isActive', true);
        $userPhonesQuery->equalTo('phoneVerified', true);
        $userPhonesQuery->equalTo('phoneCountryCode', (string)$countryCode);
        $userPhonesQuery->equalTo('phoneNumber', (string)$phoneNumber);
        $userPhonesQuery->includeKey('user');
        $userPhonesQuery->addDescending('createdAt');
        return $userPhonesQuery->find();
    }

    private function selectLastActiveUserByUserPhones(array $parseUserPhones): ?ParseObject
    {
        foreach ($parseUserPhones as $userPhone) {
            if ($userPhone->get('user') !== null) {
                $user = $userPhone->get('user');
                if ($user->get('isBetaActive') && $user->get('isActive') && $user->get('isLocked') !== true && $user->get('hasConsumerAccess')) {
                    return $user;
                }
            }
        }
        return null;
    }

    private function deactivateSessionDeviceForUserOtherThenGivenToken($parseUserId, $sessionToken)
    {
        $userInnerQuery = new ParseQuery('_User');
        $userInnerQuery->equalTo("objectId", $parseUserId);

        $userDeviceQuery = new ParseQuery("SessionDevices");
        $userDeviceQuery->matchesQuery("user", $userInnerQuery);
        $userDeviceQuery->notEqualTo('sessionTokenRecall', $sessionToken);
        $userDeviceQuery->equalTo('isActive', true);
        $userDeviceQuery->addDescending('createdAt');
        $records = $userDeviceQuery->find();

        foreach ($records as $sessionDeviceObject) {
            $sessionDeviceObject->set('isActive', false);
            $sessionDeviceObject->save();
        }
    }

    private function inactivateUserPhoneDueToUserSwitch(UserPhone $userPhone, $fromUserId, $toUserId)
    {
        $userPhoneQuery = new ParseQuery("UserPhones");
        $userPhoneQuery->equalTo("objectId", $userPhone->getId());
        $parseUserPhone = $userPhoneQuery->find(1);
        if (count($parseUserPhone) === 0) {
            return;
        }
        $parseUserPhone = $parseUserPhone[0];

        $dateTime = new DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $parseUserPhone->set('comment',
            'inactive due to switch from guest user ' . $fromUserId . ' to real user ' . $toUserId . ' ' . $dateTime->format('c')
        );
        $parseUserPhone->set("isActive", false);
        $parseUserPhone->set('endTimetamp', time());
        $parseUserPhone->save();
    }

    private function activePhone(UserPhone $userPhone)
    {
        $userInnerQuery = new ParseQuery('_User');
        $userInnerQuery->equalTo("objectId", $userPhone->getUserId());

        $userPhoneQuery = new ParseQuery("UserPhones");
        $userPhoneQuery->matchesQuery("user", $userInnerQuery);
        $userPhoneQuery->equalTo("phoneCountryCode", $userPhone->getPhoneCountryCode());
        $userPhoneQuery->equalTo("phoneNumber", $userPhone->getPhoneNumber());

        $parseUserPhone = $userPhoneQuery->find(1);
        $parseUserPhone = $parseUserPhone[0];

        $dateTime = new DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $parseUserPhone->set('comment', 'active due to signin from guest ' . $dateTime->format('c'));
        $parseUserPhone->set("isActive", true);
        $parseUserPhone->set('isVerified', true);
        $parseUserPhone->save();
    }

}
