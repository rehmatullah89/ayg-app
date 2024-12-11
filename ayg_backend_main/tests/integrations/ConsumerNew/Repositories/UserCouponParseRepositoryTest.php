<?php

namespace tests\integrations\ConsumerNew\Repositories;


use App\Consumer\Entities\UserCoupon;
use App\Consumer\Helpers\ConfigHelper;
use App\Consumer\Repositories\UserCouponParseRepository;
use Parse\ParseUser;

use Parse\ParseClient;
if (strcasecmp(getenv('env_InHerokuRun'), "Y") != 0) {
    include __DIR__ . '/../../../../putenv.php';
}
date_default_timezone_set('America/New_York');
ParseClient::setServerURL(ConfigHelper::get('env_ParseServerURL'), '/parse');
ParseClient::initialize(ConfigHelper::get('env_ParseApplicationId'), ConfigHelper::get('env_ParseRestAPIKey'), ConfigHelper::get('env_ParseMasterKey'));


class UserCouponParseRepositoryTest extends \PHPUnit_Framework_TestCase
{
    public function testUserCouponParsedRepositoryAddCoupn()
    {
        $email = 'ludwik.grochowina+mainusercreate' . md5(time() . rand(1, 10000)) . '@gmail.com';
        $username = $email . '-c';

        $user = new ParseUser();
        $user->set("username", $username);
        $user->set("email", $email);
        $user->set("lastName", 'Customer app tests');
        $user->set("password", md5('PASSword000' . getenv('env_PasswordHashSalt')));

        $user->set("isActive", true);
        $user->set("hasConsumerAccess", true);
        $user->set("isBetaActive", true);

        if (!empty($data)) {
            foreach ($data as $key => $value) {
                $user->set($key, $value);
                if ($key == 'email') {
                    $username = $value . '-c';
                    $user->set("username", $username);
                }
            }
        }

        $user->signUp();
        $userCouponParseRepository = new UserCouponParseRepository();
        $result = $userCouponParseRepository->add($user->getObjectId(), "first5", "signup");
        $this->assertInstanceOf(UserCoupon::class, $result);

        $user->destroy();
        ParseUser::logOut();

    }
}