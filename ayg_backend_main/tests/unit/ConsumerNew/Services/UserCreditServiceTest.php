<?php
namespace tests\unit\Consumer\Services;

date_default_timezone_set('America/New_York');
use App\Consumer\Entities\Order;
use App\Consumer\Entities\Retailer;
use App\Consumer\Entities\User;
use App\Consumer\Entities\UserCredit;
use App\Consumer\Entities\UserCreditApplied;
use App\Consumer\Repositories\HelloWorldRepositoryInterface;
use App\Consumer\Repositories\UserRepositoryInterface;
use App\Consumer\Repositories\UserCreditRepositoryInterface;
use App\Consumer\Services\OrderService;
use App\Consumer\Services\OrderServiceFactory;
use App\Consumer\Services\UserCreditService;
use App\Consumer\Services\UserCreditServiceFactory;
use App\Consumer\Repositories\OrderRepositoryInterface;
use \Mockery as M;

use Parse\ParseClient;

require_once __DIR__ . '/../../../../' . 'putenv.php';

ParseClient::setServerURL(getenv('env_ParseServerURL'), '/parse');
ParseClient::initialize(getenv('env_ParseApplicationId'), getenv('env_ParseRestAPIKey'), getenv('env_ParseMasterKey'));

class UserCreditServiceTest extends \PHPUnit_Framework_TestCase
{
    public function testInfoServiceCanBeCreatedByFactory()
    {
        $infoService = UserCreditServiceFactory::create();
        $this->assertInstanceOf(UserCreditService::class, $infoService);
    }

    public function testInfoServiceApplyCreditsToUser()
    {
        $userCreditRepositoryMock = M::mock(UserCreditRepositoryInterface::class);
        $userRepositoryMock = M::mock(UserRepositoryInterface::class);
        $orderRepositoryMock = M::mock(OrderRepositoryInterface::class);
        $resultOfUserCreditRepositoryApplyCreditsToUser = $this->createEmptyUserCredit();
        $userMockObject = $this->createEmptyUser();
        $orderMockObject = $this->createEmptyOrder();
        $userCreditRepositoryMock->shouldReceive('add')->andReturn($resultOfUserCreditRepositoryApplyCreditsToUser);
        $userRepositoryMock->shouldReceive('getUserById')->andReturn($userMockObject);
        $orderRepositoryMock->shouldReceive('checkIfOrderExists')->andReturn($orderMockObject);
        $infoService = new UserCreditService($userCreditRepositoryMock, $userRepositoryMock, $orderRepositoryMock);

        $result = $infoService->applyCreditsToUser("userId", "123", 45, "No Money");

        $this->assertTrue(is_string($result));
    }

    public function createEmptyUserCredit()
    {
        return new UserCredit([
            'id' => 'SomeObjectId',
            'creditsInCents' => '',
            'reasonForCredit' => '',
            'fromOrder' => '',
            'user' => '',
            'signupCoupon' => '',
        ]);
    }

    public function createEmptyUser()
    {
        return new User([
            'id' => 'SomeObjectId',
            'email' => '',
            'firstName' => '',
            'lastName' => '',
            'profileImage' => '',
            'airEmpValidUntilTimestamp' => '',
            'emailVerified' => '',
            'typeOfLogin' => '',
            'username' => '',
            'emailVerifyToken' => '',
            'hasConsumerAccess' => true,
        ]);
    }

    private function createEmptyOrder()
    {
        return new Order([
            'id' => 'someOrderId',
            'interimOrderStatus' => '',
            'paymentType' => '',
            'paymentId' => '',
            'submissionAttempt' => '',
            'orderPOSId' => '',
            'totalsWithFees' => '',
            'etaTimestamp' => '',
            'coupon' => '',
            'statusDelivery' => '',
            'tipPct' => '',
            'cancelReason' => '',
            'quotedFullfillmentFeeTimestamp' => '',
            'fullfillmentType' => '',
            'ACL' => '',
            'invoicePDFURL' => '',
            'orderSequenceId' => '',
            'totalsForRetailer' => '',
            'paymentTypeName' => '',
            'fullfillmentProcessTimeInSeconds' => '',
            'updatedAt' => '',
            'quotedFullfillmentPickupFee' => '',
            'status' => '',
            'fullfillmentFee' => '',
            'requestedFullFillmentTimestamp' => '',
            'orderPrintJobId' => '',
            'deliveryInstructions' => '',
            'quotedFullfillmentDeliveryFee' => '',
            'createdAt' => '',
            'totalsFromPOS' => '',
            'paymentTypeId' => '',
            'submitTimestamp' => '',
            'comment' => '',
            'retailer' => $this->createEmptyRetailer(),
        ]);
    }

    private function createEmptyRetailer()
    {
        return new Retailer([
            'id' => 'SomeRetailerId',
            'retailerType' => '',
            'retailerPriceCategory' => '',
            'locationId' => '',
            'searchTags' => '',
            'imageLogo' => '',
            'closeTimesSaturday' => '',
            'closeTimesThursday' => '',
            'closeTimesWednesday' => '',
            'imageBackground' => '',
            'retailerFoodSeatingType' => '',
            'ACL' => '',
            'openTimesSunday' => '',
            'openTimesMonday' => '',
            'closeTimesFriday' => '',
            'hasDelivery' => '',
            'retailerCategory' => '',
            'updatedAt' => '',
            'isActive' => true,
            'openTimesTuesday' => '',
            'openTimesSaturday' => '',
            'openTimesThursday' => '',
            'uniqueId' => '',
            'hasPickup' => '',
            'isChain' => '',
            'openTimesWednesday' => '',
            'createdAt' => '',
            'retailerName' => '',
            'openTimesFriday' => '',
            'description' => '',
            'airportIataCode' => '',
            'closeTimesMonday' => '',
            'closeTimesSunday' => '',
            'closeTimesTuesday' => '',
        ]);
    }
}