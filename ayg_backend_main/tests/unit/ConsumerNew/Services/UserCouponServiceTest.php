<?php
namespace tests\unit\Consumer\Services;

use App\Consumer\Entities\SignupCouponCredit;
use App\Consumer\Repositories\LogInvalidSignupCouponsRepositoryInterface;
use App\Consumer\Repositories\UserCouponRepositoryInterface;
use App\Consumer\Repositories\UserCreditRepositoryInterface;
use App\Consumer\Services\UserCouponService;
use \Mockery as M;

class UserCouponServiceTest extends \PHPUnit_Framework_TestCase
{
    //Call to undefined function App\Consumer\Services\fetchValidCoupon()
    public function t_estAddCouponForSignup()
    {
        $userCreditRepositoryMock = M::mock(UserCreditRepositoryInterface::class);
        $userCouponRepositoryMock = M::mock(UserCouponRepositoryInterface::class);
        $invalidSingupRepositoryMock = M::mock(LogInvalidSignupCouponsRepositoryInterface::class);

        $infoService = new UserCouponService($userCouponRepositoryMock, $userCreditRepositoryMock, $invalidSingupRepositoryMock);

        $result = $infoService->addCouponForSignup("userId", "123", "signup");

        $this->assertTrue(is_string($result));
    }

    public function createEmptySignupCouponCredit()
    {
        return new SignupCouponCredit([
            "id" => "123",
            "type" => "dasd",
            "creditsInCents" => 7,
            "welcomeMessage" => "SomeWelcomeMessage"
        ]);
    }
}