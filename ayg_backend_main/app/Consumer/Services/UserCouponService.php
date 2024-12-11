<?php

namespace App\Consumer\Services;

use App\Consumer\Entities\SignupCouponCredit;
use App\Consumer\Entities\UserCoupon;
use App\Consumer\Exceptions\PromoCodeHasExpiredException;
use App\Consumer\Exceptions\PromoCodeHasNotReachedActiveStateYetException;
use App\Consumer\Exceptions\PromoCodeIsInactiveException;
use App\Consumer\Exceptions\PromoCodeIsNotForSignupException;
use App\Consumer\Exceptions\PromoCodeIsNotValidForTheSelectedAirportException;
use App\Consumer\Exceptions\PromoCodeIsNotValidForTheSelectedRetailerException;

use App\Consumer\Exceptions\PromoCodeIsOnlyForSignupException;
use App\Consumer\Exceptions\PromoCodeIsValidForOnlyYourFirstOrderException;
use App\Consumer\Exceptions\PromoCodeMaxUsageForAllUsersReachedException;
use App\Consumer\Exceptions\PromoCodeMaxUsageForCurrentUserReachedException;
use App\Consumer\Exceptions\PromoCodeMaxUsageForCurrentDeviceReachedException;
use App\Consumer\Exceptions\PromoCodeNotFoundException;
use App\Consumer\Exceptions\PromoCodeUserSpecificBeingUsedByAnotherUserException;
use App\Consumer\Exceptions\PromoCodeIsNotValidException;
use App\Consumer\Exceptions\PromoCodeOfCreditTypeAppliedInCartException;
use App\Consumer\Exceptions\PromoCodeAlreadyAddedException;
use App\Consumer\Exceptions\ReferralCodeIsNotValidException;
use App\Consumer\Exceptions\ReferralCodeNotEligibleException;
use App\Consumer\Helpers\ConfigHelper;
use App\Consumer\Helpers\S3Helper;
use App\Consumer\Repositories\LogInvalidSignupCouponsRepositoryInterface;
use App\Consumer\Repositories\UserCouponRepositoryInterface;
use App\Consumer\Repositories\UserCreditRepositoryInterface;

/**
 * Class UserCouponService
 * @package App\Consumer\Services
 */
class UserCouponService extends Service
{
    /**
     * @var UserCouponRepositoryInterface
     */
    private $userCouponRepository;
    /**
     * @var UserCreditRepositoryInterface
     */
    private $userCreditRepository;

    /**
     * @var LogInvalidSignupCouponsRepositoryInterface
     */
    private $LogInvalidSignupCouponsReposity;

    public function __construct(
        UserCouponRepositoryInterface $userCouponRepository,
        UserCreditRepositoryInterface $userCreditRepository,
        LogInvalidSignupCouponsRepositoryInterface $LogInvalidSignupCouponsReposity
    )
    {
        $this->userCouponRepository = $userCouponRepository;
        $this->userCreditRepository = $userCreditRepository;
        $this->LogInvalidSignupCouponsReposity = $LogInvalidSignupCouponsReposity;
    }

    /**
     * @param $userID
     * @param $couponCode
     * @param $addedOnStep
     * @return SignupCouponCredit
     * @throws PromoCodeIsNotValidException
     *
     * Verify the couponCode after signup to be added.
     * If couponCode is not verified for signup then incident is logged
     * If couponCode has onSignupAcctCreditsInCents i.e. not null or zero,
     * then the credit amount is added to UserCredits and fetches the credit amount in cent with message
     *
     */
    public function addCouponAfterSignup($userID, $couponCode, $addedOnStep)
    {
        // Check if the user has already loaded a signup promo
        // and is trying to load another
        $hasSignupCoupons = doesUserAlreadyHaveSignupCoupon($GLOBALS['user'], $couponCode);
        
        if ($hasSignupCoupons[1]) {

            $this->throwPromoCodeExceptionBasedOnIsValidCouponResponse($hasSignupCoupons);
        }

        // Fetch if this is a valid coupon
        // Is the coupon of signup type?
        if($hasSignupCoupons[5] == true) {

            $validCoupon = fetchValidCoupon("", $couponCode, "", "", true);
        }
        else {

            $validCoupon = fetchValidCoupon("", $couponCode, "", "", false);
        }

        if (!$validCoupon[1]) {
            $validCouponWithOutSignup = fetchValidCoupon("", $couponCode, "", "");
            if ($validCouponWithOutSignup[1]) {
                $this->LogInvalidSignupCouponsReposity->add($userID, $couponCode);
            }
            // new structure - one message, one error
            // switch by user message to select proper exception then error
            $this->throwPromoCodeExceptionBasedOnIsValidCouponResponse($validCoupon);
        }
        $couponObject = $validCoupon[0];

        // Is Promo NOT of Credit type?
        if (!isCouponCreditType($couponObject)) {
            $userCoupon = $this->userCouponRepository->add($userID, $couponCode, $addedOnStep);
            return new SignupCouponCredit([
                'id' => $userCoupon->getId(),
                'type' => 'coupon',
                'creditsInCents' => 0,
                'creditExpiresTimestamp' => 0,
                'welcomeMessage' => UserCoupon::$welcomeMessage,
                'welcomeMessageLogoURL' => S3Helper::preparePublicS3URL(
                    $couponObject->get('onSignupAcctCreditsWelcomeLogoFilename'),
                    S3Helper::getS3KeyPath_ImagesCouponLogo(),
                    ConfigHelper::get('env_S3Endpoint')
                ),
            ]);
        } else {
            $userCredit = $this->userCreditRepository->add($userID, null, $couponObject->get('onSignupAcctCreditsInCents'), $couponObject->get('onSignupAcctCreditsExpiresTimestamp'), getUserCreditReason("GeneralSignupPromo"), getUserCreditReasonCode("GeneralSignupPromo"), $couponObject->getObjectId(), '', '', '');
            return new SignupCouponCredit([
                'id' => $userCredit->getId(),
                'type' => 'credit',
                'creditsInCents' => $couponObject->get('onSignupAcctCreditsInCents'),
                'welcomeMessage' => $couponObject->get('onSignupAcctCreditsWelcomeMsg'),
                'welcomeMessageLogoURL' => S3Helper::preparePublicS3URL(
                    $couponObject->get('onSignupAcctCreditsWelcomeLogoFilename'),
                    S3Helper::getS3KeyPath_ImagesCouponLogo(),
                    ConfigHelper::get('env_S3Endpoint')
                ),
            ]);
        }
    }

    /**
     * @param $userID
     * @param $couponCode
     * @param $addedOnStep
     * @return SignupCouponCredit
     * @throws PromoCodeIsNotValidException
     *
     * Verify the couponCode for signup.
     * If couponCode is not verified for signup then incident is logged
     * If couponCode has onSignupAcctCreditsInCents i.e. not null or zero,
     * then the credit amount is added to UserCredits and fetches the credit amount in cent with message
     *
     */
    public function addCouponForSignup($userID, $couponCode, $addedOnStep)
    {
        $validCoupon = fetchValidCoupon("", $couponCode, "", "", true);

        if (!$validCoupon[1]) {
            $validCouponWithOutSignup = fetchValidCoupon("", $couponCode, "", "");
            if ($validCouponWithOutSignup[1]) {
                $this->LogInvalidSignupCouponsReposity->add($userID, $couponCode);
            }
            // new structure - one message, one error
            // switch by user message to select proper exception then error
            $this->throwPromoCodeExceptionBasedOnIsValidCouponResponse($validCoupon);
        }
        $couponObject = $validCoupon[0];

        // Referral code used
        if ($validCoupon[5] == true) {
            $userReferralObject = $validCoupon[0];

            if(getCreditCodeAppliedCounter($userID) > 1) {

                $isValid = false;
                $invalidReasonUser = 'This offer can be used only once.';
                $invalidReasonLog = 'Max coupon (or group) usage for current user reached.';
                $invalidErrorCode = 112;
                $validCoupon = [$validCoupon[0], $isValid, $invalidReasonUser, $invalidReasonLog, $invalidErrorCode, false];
                $this->throwPromoCodeExceptionBasedOnIsValidCouponResponse($validCoupon);
            }

            $userCredit = $this->userCreditRepository->add($userID, null, $GLOBALS['env_UserReferralOfferInCents'], time()+$GLOBALS['env_UserReferralRewardExpireInSeconds'], getUserCreditReason("ReferralSignup"), getUserCreditReasonCode("ReferralSignup"), null, $userReferralObject->getObjectId(), getMinOrderTotalFieldForCredits(), $GLOBALS['env_UserReferralMinSpendInCentsForOfferUse']);
            logReferralOffer($GLOBALS['user'], $userReferralObject, $GLOBALS['env_UserReferralOfferInCents'], $GLOBALS['env_UserReferralRewardInCents']);
            return new SignupCouponCredit([
                'id' => $userCredit->getId(),
                'type' => 'credit',
                'creditsInCents' => $GLOBALS['env_UserReferralOfferInCents'],
                'creditExpiresTimestamp' => time()+$GLOBALS['env_UserReferralRewardExpireInSeconds'],
                'welcomeMessage' => 'Any friend of ' . $userReferralObject->get('user')->get('firstName') . '\'s is a friend of ours. Your welcome credit will be applied to your next order (over ' . dollar_format_no_cents($GLOBALS['env_UserReferralMinSpendInCentsForOfferUse']) . ').',
                'welcomeMessageLogoURL' => S3Helper::preparePublicS3URL(
                    $GLOBALS['env_UserReferralWelcomeImageFileName'],
                    S3Helper::getS3KeyPath_ImagesCouponLogo(),
                    ConfigHelper::get('env_S3Endpoint')
                ),
            ]);
        }
        // Is Promo NOT of Credit type?
        else if (!isCouponCreditType($couponObject)) {

            if(getCreditCodeAppliedCounter($userID) > 1) {

                $isValid = false;
                $invalidReasonUser = 'This offer can be used only once.';
                $invalidReasonLog = 'Max coupon (or group) usage for current user reached.';
                $invalidErrorCode = 112;
                $validCoupon = [$validCoupon[0], $isValid, $invalidReasonUser, $invalidReasonLog, $invalidErrorCode, false];
                $this->throwPromoCodeExceptionBasedOnIsValidCouponResponse($validCoupon);
            }

            $userCoupon = $this->userCouponRepository->add($userID, $couponCode, $addedOnStep);
            return new SignupCouponCredit([
                'id' => $userCoupon->getId(),
                'type' => 'coupon',
                'creditsInCents' => 0,
                'creditExpiresTimestamp' => 0,
                'welcomeMessage' => UserCoupon::$welcomeMessage,
                'welcomeMessageLogoURL' => S3Helper::preparePublicS3URL(
                    $couponObject->get('onSignupAcctCreditsWelcomeLogoFilename'),
                    S3Helper::getS3KeyPath_ImagesCouponLogo(),
                    ConfigHelper::get('env_S3Endpoint')
                ),
            ]);
        } 
        else {

            /*
            if(getCreditCodeAppliedCounter($userID) > 1) {

                $isValid = false;
                $invalidReasonUser = 'This offer can be used only once.';
                $invalidReasonLog = 'Max coupon (or group) usage for current user reached.';
                $invalidErrorCode = 112;
                $validCoupon = [$validCoupon[0], $isValid, $invalidReasonUser, $invalidReasonLog, $invalidErrorCode, false];
                $this->throwPromoCodeExceptionBasedOnIsValidCouponResponse($validCoupon);
            }
            */

            $userCredit = $this->userCreditRepository->add($userID, null, $couponObject->get('onSignupAcctCreditsInCents'), $couponObject->get('onSignupAcctCreditsExpiresTimestamp'), getUserCreditReason("GeneralSignupPromo"), getUserCreditReasonCode("GeneralSignupPromo"), $couponObject->getObjectId(), '', getMinOrderTotalFieldForCredits(), intval($couponObject->get('applyDiscountToOrderMinOfInCents')));
            return new SignupCouponCredit([
                'id' => $userCredit->getId(),
                'type' => 'credit',
                'creditsInCents' => $couponObject->get('onSignupAcctCreditsInCents'),
                'creditExpiresTimestamp' => $couponObject->get('onSignupAcctCreditsExpiresTimestamp'),
                'welcomeMessage' => $couponObject->get('onSignupAcctCreditsWelcomeMsg'),
                'welcomeMessageLogoURL' => S3Helper::preparePublicS3URL(
                    $couponObject->get('onSignupAcctCreditsWelcomeLogoFilename'),
                    S3Helper::getS3KeyPath_ImagesCouponLogo(),
                    ConfigHelper::get('env_S3Endpoint')
                ),
            ]);
        }
    }

    /**
     * @param array $isValidCoupon
     * @throws PromoCodeHasExpiredException
     * @throws PromoCodeHasNotReachedActiveStateYetException
     * @throws PromoCodeIsInactiveException
     * @throws PromoCodeIsNotForSignupException
     * @throws PromoCodeIsNotValidForTheSelectedAirportException
     * @throws PromoCodeIsNotValidForTheSelectedRetailerException
     * @throws PromoCodeIsOnlyForSignupException
     * @throws PromoCodeIsValidForOnlyYourFirstOrderException
     * @throws PromoCodeMaxUsageForAllUsersReachedException
     * @throws PromoCodeMaxUsageForCurrentUserReachedException
     * @throws PromoCodeMaxUsageForCurrentDeviceReachedException
     * @throws PromoCodeNotFoundExceptiotn
     * @throws PromoCodeUserSpecificBeingUsedByAnotherUserException
     * @throws PromoCodeIsNotValidException new structure - one message, one error
     *
     * switch by user message to select proper exception and throw it
     * if not found PromoCodeIsNotValidException is thrown
     */
    private function throwPromoCodeExceptionBasedOnIsValidCouponResponse(array $isValidCoupon)
    {
        switch ($isValidCoupon[4]) {
            case 101:
                throw new PromoCodeNotFoundException($isValidCoupon[3]);
                break;
            case 102:
                throw new PromoCodeIsOnlyForSignupException($isValidCoupon[3]);
                break;
            case 103:
                throw new PromoCodeIsNotForSignupException($isValidCoupon[3]);
                break;
            case 104:
                throw new PromoCodeIsInactiveException($isValidCoupon[3]);
                break;
            case 105:
                throw new PromoCodeHasNotReachedActiveStateYetException($isValidCoupon[3]);
                break;
            case 106:
                throw new PromoCodeHasExpiredException($isValidCoupon[3]);
                break;
            case 107:
                throw new PromoCodeIsNotValidForTheSelectedAirportException($isValidCoupon[3]);
                break;
            case 108:
                throw new PromoCodeIsNotValidForTheSelectedRetailerException($isValidCoupon[3]);
                break;
            case 109:
                throw new PromoCodeUserSpecificBeingUsedByAnotherUserException($isValidCoupon[3]);
                break;
            case 110:
                throw new PromoCodeIsValidForOnlyYourFirstOrderException($isValidCoupon[3]);
                break;
            case 111:
                throw new PromoCodeMaxUsageForAllUsersReachedException($isValidCoupon[3]);
                break;
            case 112:
                throw new PromoCodeMaxUsageForCurrentUserReachedException($isValidCoupon[3]);
                break;
            case 114:
                throw new PromoCodeMaxUsageForCurrentDeviceReachedException($isValidCoupon[3]);
                break;
            case 115:
                throw new PromoCodeOfCreditTypeAppliedInCartException($isValidCoupon[3]);
                break;
            case 116:
                throw new PromoCodeAlreadyAddedException($isValidCoupon[3]);
                break;
            case 201:
                throw new ReferralCodeIsNotValidException($isValidCoupon[3]);
                break;
            case 209:
                throw new ReferralCodeNotEligibleException($isValidCoupon[3]);
                break;

            default:
                throw new PromoCodeIsNotValidException($isValidCoupon[3]);
                break;
        }
    }
}
