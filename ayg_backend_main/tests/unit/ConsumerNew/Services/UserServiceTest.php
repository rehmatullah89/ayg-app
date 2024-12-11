<?php
namespace tests\unit\Consumer\Services;

date_default_timezone_set('America/New_York');
use App\Consumer\Entities\User;
use App\Consumer\Entities\UserPhone;
use App\Consumer\Exceptions\TwilioSendSmsException;
use App\Consumer\Repositories\UserPhoneRepositoryInterface;
use App\Consumer\Repositories\UserRepositoryInterface;
use App\Consumer\Services\TwilioService;
use App\Consumer\Services\UserService;
use \Mockery as M;

use Parse\ParseClient;
use Twilio\Exceptions\TwilioException;

require_once __DIR__ . '/../../../../' . 'putenv.php';

ParseClient::setServerURL(getenv('env_ParseServerURL'), '/parse');
ParseClient::initialize(getenv('env_ParseApplicationId'), getenv('env_ParseRestAPIKey'), getenv('env_ParseMasterKey'));

/**
 * Class UserServiceTest
 * @package tests\unit\Consumer\Services
 * @covers \App\Consumer\Services\UserService
 */
class UserServiceTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers \App\Consumer\Services\UserService::addPhoneWithTwilio()
     */
    public function testUserServiceCanAddPhoneWithTwilio()
    {
        // mock repositories / services that will be injected into userService constructor
        $userRepositoryMock = M::mock(UserRepositoryInterface::class);

        $userPhoneRepositoryMock = M::mock(UserPhoneRepositoryInterface::class);

        $userPhone = $this->createEmptyUserPhone();
        $userPhoneRepositoryMock->shouldReceive('updateTwilioCodeAddPhoneWhenNeeded')->andReturn($userPhone);
        $userPhoneRepositoryMock->shouldReceive('deleteUserPhone')->andReturn(true);

        $twilioServiceMock = M::mock(TwilioService::class);
        $twilioServiceMock->shouldReceive('sendSms');

        // create userService
        $userService = new UserService(
            $userRepositoryMock,
            $userPhoneRepositoryMock,
            $twilioServiceMock
        );

        $user = $this->createEmptyUser();

        $addPhoneWithTwilioResponse = $userService->addPhoneWithTwilio($user, '1', '1234567890');

        $this->assertInstanceOf(UserPhone::class, $addPhoneWithTwilioResponse);
    }

    /**
     * @covers \App\Consumer\Services\UserService::addPhoneWithTwilio()
     * @expectedException \App\Consumer\Exceptions\TwilioSendSmsException
     */
    public function testUserServiceCanThrowExceptionWhenTwilioDoesNotWork(){
        // mock repositories / services that will be injected into userService constructor
        $userRepositoryMock = M::mock(UserRepositoryInterface::class);

        $userPhoneRepositoryMock = M::mock(UserPhoneRepositoryInterface::class);

        $userPhone = $this->createEmptyUserPhone();
        $userPhoneRepositoryMock->shouldReceive('updateTwilioCodeAddPhoneWhenNeeded')->andReturn($userPhone);
        $userPhoneRepositoryMock->shouldReceive('deleteUserPhone')->andReturn(true);

        $twilioServiceMock = M::mock(TwilioService::class);
        $twilioServiceMock->shouldReceive('sendSms')->andThrow(new TwilioException());

        // create userService
        $userService = new UserService(
            $userRepositoryMock,
            $userPhoneRepositoryMock,
            $twilioServiceMock
        );

        $user = $this->createEmptyUser();

        $userService->addPhoneWithTwilio($user, '1', '1234567890');
    }




    private function createEmptyUser()
    {
        return new User(
            [
                'id' => '',
                'email' => '',
                'firstName' => '',
                'lastName' => '',
                'profileImage' => '',
                'airEmpValidUntilTimestamp' => '',
                'emailVerified' => '',
                'typeOfLogin' => '',
                'username' => '',
                'emailVerifyToken' => '',
                'hasConsumerAccess' => ''
            ]
        );
    }

    private function createEmptyUserPhone()
    {
        return new UserPhone(
            [
                'id' => 'addedPhoneId',
                'createdAt' => '',
                'updatedAt' => '',
                'userId' => '',
                'phoneNumberFormatted' => '',
                'phoneNumber' => '',
                'phoneCountryCode' => '',
                'phoneVerified' => '',
                'phoneCarrier' => '',
                'SMSNotificationsEnabled' => '',
                'startTimestamp' => '',
                'endTimestamp' => '',
                'isActive' => '',
            ]
        );
    }
}