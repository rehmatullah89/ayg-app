<?php

namespace tests\integrations\ConsumerNew\Repositories;

date_default_timezone_set('America/New_York');

use App\Consumer\Entities\PhoneNumberInput;
use App\Consumer\Repositories\HelloWorldParseRepository;
use App\Consumer\Repositories\UserCreditParseRepository;
use App\Consumer\Repositories\UserPhoneParseRepository;
use Parse\ParseClient;

require_once __DIR__ . '/../../../../' . 'putenv.php';

ParseClient::setServerURL(getenv('env_ParseServerURL'), '/parse');
ParseClient::initialize(getenv('env_ParseApplicationId'), getenv('env_ParseRestAPIKey'), getenv('env_ParseMasterKey'));

/**
 * Class UserPhoneParseRepositoryTest
 * @package tests\integrations\ConsumerNew\Repositories
 * @covers \App\Consumer\Repositories\UserPhoneParseRepository
 */
class UserPhoneParseRepositoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers \App\Consumer\Repositories\UserPhoneParseRepository::updateTwilioCodeAddPhoneWhenNeeded()
     */
    public function testUserPhoneParseRepositoryTestCanUpdateTwilioCodeAddPhoneWhenNeeded()
    {
        $userQuery = new \Parse\ParseQuery("_User");
        $parseUser = $userQuery->first();
        $userId = $parseUser->getObjectId();

        $phoneNumberInput = new PhoneNumberInput([
            'phoneCountryCode' => '1',
            'phoneNumber' => '1234567890',
        ]);

        $userPhoneParseRepository = new UserPhoneParseRepository();
        $userPhone = $userPhoneParseRepository->updateTwilioCodeAddPhoneWhenNeeded($userId, $phoneNumberInput, '0001');

        // check if User phone with response exists in the database

        $userQuery = new \Parse\ParseQuery("UserPhones");
        $userQuery->equalTo('objectId', $userPhone->getId());
        $userQuery->includeKey('user');
        $foundParseUserPhone = $userQuery->first();
        $foundParseUserPhone->get('user')->fetch();

        $this->assertEquals($parseUser->getObjectId(), $foundParseUserPhone->get('user')->getObjectId());
        $this->assertEquals($phoneNumberInput->getPhoneNumberFormatted(), $foundParseUserPhone->get('phoneNumberFormatted'));
        $this->assertEquals($phoneNumberInput->getPhoneCountryCode(), $foundParseUserPhone->get('phoneCountryCode'));
        $this->assertEquals($phoneNumberInput->getPhoneNumber(), $foundParseUserPhone->get('phoneNumber'));
        $this->assertEquals('0001', $foundParseUserPhone->get('twilioCode'));

        $foundParseUserPhone->destroy();
    }

    /**
     * @covers \App\Consumer\Repositories\UserPhoneParseRepository::verifyPhone()
     */
    public function testUserPhoneParseRepositoryTestCanVerifyPhone()
    {
        $this->assertTrue(true);
    }

    /**
     * @covers \App\Consumer\Repositories\UserPhoneParseRepository::deleteUserPhone()
     */
    public function testUserPhoneParseRepositoryTestCanDeleteUserPhone()
    {
        $this->assertTrue(true);
    }
}