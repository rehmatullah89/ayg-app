<?php
namespace App\Consumer\Services;

use App\Consumer\Entities\UserIdentifier;
use App\Consumer\Entities\UserPhone;
use App\Consumer\Entities\UserSession;
use App\Consumer\Exceptions\UserAuthException;
use App\Consumer\Exceptions\UserCustomIdentifierCanNotBeCreatedException;
use App\Consumer\Exceptions\UserDeviceArrayIncorrectException;
use App\Consumer\Exceptions\UserWithoutPhoneException;
use App\Consumer\Helpers\UserAuthHelper;
use App\Consumer\Mappers\UserPhoneFactory;
use App\Consumer\Repositories\UserCustomIdentifierMysqlRepository;
use App\Consumer\Repositories\UserCustomIdentifierRepositoryInterface;
use App\Consumer\Repositories\UserCustomSessionMysqlRepository;
use App\Consumer\Repositories\UserCustomSessionRepositoryInterface;
use App\Consumer\Repositories\UserSessionDeviceParseRepository;
use App\Consumer\Repositories\UserSessionDeviceRepositoryInterface;
use Parse\ParseQuery;
use Parse\ParseUser;
use Ramsey\Uuid\Uuid;

class UserAuthService extends Service
{
    const CONSUMER_TYPE_SHORT = 'c';
    /**
     * @var UserCustomIdentifierMysqlRepository
     */
    private $userCustomIdentifierRepository;
    /**
     * @var UserCustomSessionMysqlRepository
     */
    private $userCustomSessionRepository;
    /**
     * @var UserSessionDeviceParseRepository
     */
    private $userSessionDeviceParseRepository;


    public function __construct(
        UserCustomIdentifierRepositoryInterface $userCustomIdentifierRepository,
        UserCustomSessionRepositoryInterface $userCustomSessionRepository,
        UserSessionDeviceRepositoryInterface $userSessionDeviceParseRepository
    ) {
        $this->userCustomIdentifierRepository = $userCustomIdentifierRepository;
        $this->userCustomSessionRepository = $userCustomSessionRepository;
        $this->userSessionDeviceParseRepository = $userSessionDeviceParseRepository;
    }

    public function setSessionAsFullAccess(string $sessionToken)
    {
        $activeUserSession = $this->userCustomSessionRepository->findActiveUserSessionBySessionToken($sessionToken);
        if ($activeUserSession === null) {
            throw new UserAuthException('No active session for given token');
        }
        $activeUserSession = $activeUserSession->setSessionHasFullAccess();
        $this->userCustomSessionRepository->save($activeUserSession);
    }

    public function loginWithNewSessionToken(
        string $sessionToken
    ): UserSession {
        $activeUserSession = $this->userCustomSessionRepository->findActiveUserSessionBySessionToken($sessionToken);

        if ($activeUserSession !== null) {
            return $activeUserSession;
        } else {
            throw new UserAuthException('No active session for given token');
        }
    }

    public function logout(
        string $sessionToken
    ): void {
        $this->userCustomSessionRepository->deactivateSessionByToken($sessionToken);

        $GLOBALS['user'] = array();
        $GLOBALS['userPhones'] = array();

        $sessionToken = rtrim($sessionToken, '-c');
        logoutUserFromSessionDevice($sessionToken, true);
    }

    private function create(
        string $deviceIdentifier
    ): ParseUser {
        $parseUserObject = new ParseUser();
        $parseUserObject->set('username',
            $deviceIdentifier . '_' . (new \DateTimeImmutable())->getTimestamp() . uniqid('', true));
        $parseUserObject->set('password', uniqid(rand(1, 100000), true) . Uuid::uuid1());
        $parseUserObject->set('hasConsumerAccess', true);
        $parseUserObject->set('isActive', true);
        $parseUserObject->set('isBetaActive', true);
        $parseUserObject->set('typeOfLogin', 'c');
        $parseUserObject->signUp();

        return $parseUserObject;
    }

    private function deactivateParseUserByDeviceIdentifier(
        string $deviceIdentifier
    ): void {
        $parseUserObject = parseExecuteQuery(["username" => $deviceIdentifier], "_User", "", "", []);
        foreach ($parseUserObject as $parseUserObject) {
            $parseUserObject->logout();
            $parseUserObject->set('isActive', false);
            $parseUserObject->save(true);
        }
        return;
    }

    private function createUserIdentifierForNewUser(
        $deviceIdentifier,
        $parseUserObject
    ): UserIdentifier {
        return new UserIdentifier(
            null,
            $deviceIdentifier,
            null,
            null,
            $parseUserObject->getObjectId(),
            true
        );
    }

    public function createUserSessionForNewUser(
        array $decodedDeviceArray
    ) {
        $deviceIdentifier = UserAuthHelper::getDeviceIdentifierFromDeviceArray($decodedDeviceArray);

        $this->deactivateParseUserByDeviceIdentifier($deviceIdentifier);

        $parseUserObject = $this->create($deviceIdentifier);

        $userIdentifier = $this->createUserIdentifierForNewUser($deviceIdentifier, $parseUserObject);

        // we should not deactivate user, since we will need those for switching session
        $this->userCustomIdentifierRepository->deactivateNotVerifiedUsersByUserDeviceIdentifier($deviceIdentifier);
        $userIdentifier = $this->userCustomIdentifierRepository->add($userIdentifier);

        $this->userCustomSessionRepository->deactivateSessionByUserDeviceIdentifier($deviceIdentifier);
        $userSession = $this->createUserSessionWithRestrictedAccess($this->generateUserSessionToken(),
            $userIdentifier, true);

        $userSession = $this->userCustomSessionRepository->add($userSession);

        // Create row in UserDevices
        $objUserDevice = createUserDevice($parseUserObject, $decodedDeviceArray);
        // Create row in SessionDevices
        $currentSessionToken = parseSessionToken($userSession->getToken())[0];
        $objSessionDevice = createSessionDevice($objUserDevice, $decodedDeviceArray,
            $currentSessionToken, $parseUserObject);


        $userSession->setSessionDeviceId($objSessionDevice->getObjectId());
        $this->userCustomSessionRepository->save($userSession);

        // Check and Create a SQS action if we need to create a new device in OneSignal
        $response = createOneSignalViaQueue($objUserDevice);

        if (is_array($response)) {

            json_error($response["error_code"], "",
                $response["error_message_log"] . " Create Onesignal Id failed due to Queue - " . $parseUserObject->getObjectId(),
                1, 1);
        }

        return $userSession;
    }

    public function attachUserSessionToExistingLoggedInParseUser(
        ParseUser $parseUserObject,
        array $decodedDeviceArray,
        array $parseCheckInResponse
    ) {
        $deviceIdentifier = UserAuthHelper::getDeviceIdentifierFromDeviceArray($decodedDeviceArray);

        if (!isset($parseCheckInResponse['phoneCountryCode']) || !isset($parseCheckInResponse['phoneNumber'])) {
            throw new UserWithoutPhoneException($deviceIdentifier);
        }
        // check if some custom session user is not already there with the number

        // we are getting userIdentifier object (not saving anything)

        if (
            isset($parseCheckInResponse['phoneCountryCode']) &&
            isset($parseCheckInResponse['phoneNumber']) &&
            !empty($parseCheckInResponse['phoneCountryCode']) &&
            !empty($parseCheckInResponse['phoneNumber'])
        ) {
            $phone = UserPhoneFactory::mapFromNumberOnly(
                $parseCheckInResponse['phoneCountryCode'],
                $parseCheckInResponse['phoneNumber']
            );

            $existingCustomIdentifierList = $this->userCustomIdentifierRepository->findByPhone($phone);

            if (!$existingCustomIdentifierList->checkIfAllHasTheSameUserId()) {
                throw new \Exception('User Identifiers conflict, multiple user Ids');
            }

            if (count($existingCustomIdentifierList) > 0) {
                $userIdentifier = $existingCustomIdentifierList->getLast();
                if ($userIdentifier->getParseUserId() !== $parseUserObject->getObjectId()) {


                    throw new \Exception(\json_encode($parseCheckInResponse) . 'User Identifiers conflict, user ids do not match');
                }
            }

            //$userIdentifier = $existingCustomIdentifier->getLast();
            //$doesUserExists = true;
        }


        //@todo where should be disable parse session?

        //@todo think if person is locked - i think we can return new session anyway, he wil be checked on middleware

        //@todo think if person is not Active - i think we can return new session anyway, he wil be checked on middleware

        //@todo if there is any way that a user at that point dont have phone number added?

        $userIdentifier = new UserIdentifier(
            null,
            $deviceIdentifier,
            $parseCheckInResponse['phoneCountryCode'],
            $parseCheckInResponse['phoneNumber'],
            $parseUserObject->getObjectId(),
            $parseUserObject->get('isActive')
        );
        $userIdentifierId = $this->userCustomIdentifierRepository->getId($userIdentifier);


        // check if user is already there?
        if ($userIdentifierId === null) {
            $this->userCustomIdentifierRepository->add($userIdentifier);
            // @todo - should we deactivate users with the same deviceIdentifier ?
        } else {
            $userIdentifier->setId($userIdentifierId);
        }

        $activeSession = $this->userCustomSessionRepository->findActiveUserSessionByDeviceIdentifier($deviceIdentifier);
        if ($activeSession !== null) {
            return $activeSession;
        }

        // $this->userSessionMysqlRepository->deactivateSessionByUserDeviceIdentifier($deviceIdentifier);

        if ($parseCheckInResponse['isPhoneVerified']) {
            $userSession = $this->createUserSessionWithFullAccess($this->generateUserSessionToken(),
                $userIdentifier, false);
        } else {
            $userSession = $this->createUserSessionWithRestrictedAccess($this->generateUserSessionToken(),
                $userIdentifier, false);
        }

        $userSession = $this->createInactiveSessionDeviceForCustomSession(
            $userSession,
            $parseUserObject,
            $decodedDeviceArray
        );

        $this->userCustomSessionRepository->add($userSession);

        return $userSession;
    }

    private function generateUserSessionToken()
    {
        $createSequence = new ParseQuery("Sequences");
        $createSequence->equalTo('keyName', 'sessionToken');
        $sequenceObject = $createSequence->first();
        $sequenceObject->increment('sequenceNumber');
        $sequenceObject->save();
        $sequenceId = $sequenceObject->get('sequenceNumber');

        $randomLetters = substr(str_shuffle("abcdefghijklmnopqrstuvwxyzQWERTYUIOPASDFGHJKLZXCVBNM"), 0,
            10 - strlen($sequenceId));

        $uuId = str_replace('-', '', Uuid::uuid1());
        return
            $uuId .
            $randomLetters . $sequenceId . '.' .
            uniqid('', true) . '-' .
            self::CONSUMER_TYPE_SHORT;
    }

    private function createUserSessionWithRestrictedAccess(
        string $token,
        UserIdentifier $userIdentifier,
        bool $isActive
    ): UserSession {
        return new UserSession(null, $token, $userIdentifier, true, false, null, $isActive);
    }

    public function createUserSessionWithFullAccess(
        string $token,
        UserIdentifier $userIdentifier,
        bool $isActive
    ): UserSession {
        return new UserSession(null, $token, $userIdentifier, true, true, null, $isActive);
    }

    public function pullAndStoreParseUserIntoGlobals(
        UserSession $userSession
    ) {
        $GLOBALS['user'] = parseExecuteQuery(
            ["objectId" => $userSession->getUserIdentifier()->getParseUserId()], "_User", "", "", [], 1, true, [],
            'find', true
        );

        $GLOBALS['userSessionToken'] = $userSession->getTokenWithoutTypeIndicator();
        $GLOBALS['userSession'] = $userSession;
    }

    public function pullAndStoreParseUserPhonesIntoGlobals()
    {
        $GLOBALS['userPhones'] = parseExecuteQuery(array(
            "user" => $GLOBALS['user'],
            "phoneVerified" => true
        ),
            "UserPhones", "", "", array(), 1, false, [
                "cacheKey" => getCacheKeyForUserPhone($GLOBALS['user']->getObjectId()),
                "ttl" => "EOW",
                "cacheOnlyWhenResult" => true
            ]);

    }

    private function createInactiveSessionDeviceForCustomSession(
        UserSession $userSession,
        $parseUserObject,
        $decodedDeviceArray
    ): UserSession {
        $objUserDevice = createUserDevice($parseUserObject, $decodedDeviceArray);
        $currentSessionToken = parseSessionToken($userSession->getToken())[0];
        $objSessionDevice = createSessionDevice($objUserDevice, $decodedDeviceArray,
            $currentSessionToken, $parseUserObject, true
        );
        $userSession = $userSession->setSessionDeviceId($objSessionDevice->getObjectId());
        $userSession = $userSession->setSessionDeviceAsInactive();
        return $userSession;
    }

    public function activateCustomSessionAndDeactivateParse(UserSession $activeUserSession)
    {
        // get all sessionDevices
        $parseSessionDevices = getAllSessionDevicesByUser();
        foreach ($parseSessionDevices as $parseSessionDevice) {
            if ($parseSessionDevice->get('sessionTokenRecall') != $activeUserSession->getTokenWithoutTypeIndicator()) {
                $parseSessionDevice->set('isActive', false);
                $parseSessionDevice->set('sessionEndTimestamp', time());
                $parseSessionDevice->save();
            } else {
                $parseSessionDevice->set('isActive', true);
                $parseSessionDevice->save();
            }
        }

        $activeUserSession = $activeUserSession->setSessionDeviceAsActive();
        $this->userCustomSessionRepository->save($activeUserSession);
    }

    public function deactivateCustomSessionAndDeactivateParseSessionDevicesBySessionTokenWithoutTypeIndicator(
        string $userSessionToken
    ) {
        $userSession = $this->userCustomSessionRepository->findActiveUserSessionBySessionToken($userSessionToken . '-c');
        // get all sessionDevices
        $parseSessionDevices = getAllSessionDevicesByUser();
        foreach ($parseSessionDevices as $parseSessionDevice) {
            if ($userSession !== null && $parseSessionDevice->get('sessionTokenRecall') == $userSession->getTokenWithoutTypeIndicator()) {
                $parseSessionDevice->set('isActive', false);
                $parseSessionDevice->set('sessionEndTimestamp', time());
                $parseSessionDevice->save();
            }
        }

        if ($userSession !== null) {
            $userSession = $userSession->setSessionDeviceAsInactive();
            $this->userCustomSessionRepository->save($userSession);
        }
    }

    public function doesSessionDeviceEntryExist(UserSession $userSession)
    {

        // Check if this session is still active in SessionDevices
        $sessionDevice = parseExecuteQuery(array(
            "user" => $GLOBALS['user'],
            "sessionTokenRecall" => $userSession->getTokenWithoutTypeIndicator(),
            "isActive" => true
        ), "SessionDevices", "", "", array(), 1, false, [
            "cacheKey" => getCacheKeyForSessionDevice($GLOBALS['user']->getObjectId(),
                $userSession->getTokenWithoutTypeIndicator()),
            "ttl" => "EOW",
            "cacheOnlyWhenResult" => true
        ]);

        // No row found
        if (count_like_php5($sessionDevice) == 0) {
            return false;
        }
        return true;
    }

    public function logoutCustomSessionByParseUserId(string $parseUserId): void
    {
        $this->userCustomSessionRepository->deactivateSessionByParseUserId($parseUserId);
    }

    public function findUserActiveSessions(string $sessionToken, string $userId)
    {
        if(\App\Consumer\Helpers\UserAuthHelper::isSessionTokenFromCustomSessionManagement($sessionToken)){
            $sessions = $this->userCustomSessionRepository->findActiveUserSessionsBySessionToken($sessionToken);
            $list = $this->userSessionDeviceParseRepository->getUserActiveSessionsListBySessions($sessions);
        }else{
            $list = $this->userSessionDeviceParseRepository->getUserActiveSessionsList($userId);
        }
        return $list;
    }

}
