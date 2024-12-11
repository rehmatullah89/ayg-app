<?php
namespace App\Background\Entities;

class CouponsUpdate extends Entity
{
    private $dataDirPath;
    private $dataCsvPath;
    private $logosDirPath;

    const DIR_PREFIX = '_commonFiles';
    const CSV_FILE_NAME = 'Coupons.csv';
    const LOGOS_DIR_NAME = '_CouponLogos';

    public function __construct()
    {
        $this->dataDirPath = self::DIR_PREFIX;
        $this->dataCsvPath = self::DIR_PREFIX . '/'. self::CSV_FILE_NAME;
        $this->logosDirPath = self::DIR_PREFIX . '/'. self::LOGOS_DIR_NAME;
    }

    public function getDataDirPath(): string
    {
        return $this->dataDirPath;
    }

    /**
     * @return mixed
     */
    public function getDataCsvPath()
    {
        return $this->dataCsvPath;
    }

    public function getLogosDirPath()
    {
        return $this->logosDirPath;
    }


    public function getObjectKeysInArray()
    {
        return [
            "couponCode" => "N",
            "groupId" => "N",
            "isActive" => "N",
            "forSignup" => "N",
            "forCart" => "N",
            "applyDiscountToOrderMinOfInCents" => "I",
            "couponDiscountForFeeCents" => "I",
            "couponDiscountForFeePCT" => "F",
            "couponDiscountCents" => "I",
            "couponDiscountPCT" => "F",
            "couponDiscountPCTMaxCents" => "I",
            "activeTimestamp" => "I",
            "expiresTimestamp" => "I",
            "isRetailerCompensated" => "N",
            "maxUserAllowedByAll" => "I",
            "maxUserAllowedByUser" => "I",
            "maxUsageAllowedByDevice" => "I",
            "isFirstUseOnly" => "N",
            "applicableAirportIataCodes" => "Y",
            "applicableRetailerUniqueIds" => "Y",
            "applicableConsumerEmail" => "X",
            "description" => "N",
            "onSignupAcctCreditsInCents" => "I",
            "onSignupAcctCreditsWelcomeMsg" => "N",
            "onSignupAcctCreditsWelcomeLogoFilename" => "N",
            "allowWithReferralCredit" => "N",
            "fullfillmentTypeRestrict" => "N",
            "savingsTextDisplay" => "N",
            "onSignupAcctCreditsExpiresTimestamp" => "I",
            "disallowForCreditReasonCodes" => "Y",
        ];
    }

    public function getReferenceLookup()
    {
        return [
            "applicableUser" => array(
                "className" => "_User",
                "isRequired" => false,
                "whenColumnValuePresentIsRequired" => "applicableConsumerEmail",
                // Name of the column to check, if it is preset then isRequired is assumed to be true
                "lookupCols" => array(
                    // Column in ClassName => Column in File
                    "email" => "applicableConsumerEmail",
                    // "__LKPVAL__isActive" => true
                ),
                // "lookupColsType" => array(
                // 					"email" => "Y", // An array
                // 				)
            ),
        ];
    }

    public function getImagesIndexesWithPath($storagePath)
    {
        return [
            "onSignupAcctCreditsWelcomeLogoFilename" => [
                "S3KeyPath" => getS3KeyPath_ImagesCouponLogo(),
                "useUniqueIdInName" => "N",
                "useThisColumnValueInName" => "couponCode",
                "maxWidth" => '',
                "maxHeight" => '',
                "createThumbnail" => false,
                "imagePath" => $storagePath.'/'.$this->logosDirPath
            ],
        ];
    }
}
