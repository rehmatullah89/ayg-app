<?php
namespace App\Consumer\Services;


use App\Consumer\Entities\FormattedNumber;
use App\Consumer\Entities\PhoneNumberFormatted;
use App\Consumer\Entities\UserPhone;
use App\Consumer\Helpers\ConfigHelper;
use App\Consumer\Helpers\PhoneNumberHelper;
use Twilio\Http\Client;

/**
 * Class TwilioService
 * @package App\Consumer\Services
 */
class TwilioService extends Service
{
    const AUTHY_PHONE_CHECK_METHOD = 'get';

    /**
     * @var \Twilio\Rest\Client
     */
    public $client;
    /**
     * @var string
     */
    private $phoneNumber;
    /**
     * @var string
     */
    private $authyPhoneCheckURL;

    public function __construct(
        \Twilio\Rest\Client $twilioClient,
        $twilioPhoneNumber,
        string $authyPhoneCheckURL
    ) {
        $this->client = $twilioClient;
        $this->phoneNumber = $twilioPhoneNumber;
        $this->authyPhoneCheckURL = $authyPhoneCheckURL;
    }

    /**
     * @param $userPhoneNumber
     * @param $code
     */
    public function sendSms($userPhoneNumber, $code)
    {
        $customMessage = getAuthyCustomMessage(prepareDeviceArray(getCurrentSessionDevice()));

        $this->client->messages->create(
            $userPhoneNumber,
            array(
                'from' => ConfigHelper::get('env_TwilioPhoneNumber'),
                'body' => str_replace('{{code}}', $code, $customMessage),
            )
        );
    }

    /**
     * @param $number
     * @return PhoneNumberFormatted|null
     *
     * uses twilio client to get information about the number, then formats it into PhoneNumberFormatted Entity and returns
     */
    public function lookupForFormattedNumber($number)
    {
        try {
            $twilioLookupResponse = $this->client->lookups
                ->phoneNumbers($number)
                ->fetch(["type" => "carrier"]);
        } catch (\Exception $e) {
            return null;
        }

        $twilioLookupResponse = $twilioLookupResponse->toArray();

        return new PhoneNumberFormatted(
            $number,
            $twilioLookupResponse['nationalFormat'],
            $twilioLookupResponse['carrier']['name']
        );
    }

    public function authyVerify(UserPhone $userPhone, string $verifyCode): bool
    {
        // Verify phone
        $authyResponse = authyPhoneVerificationAPI($this->authyPhoneCheckURL, self::AUTHY_PHONE_CHECK_METHOD, [
            "phone_number" => $userPhone->getPhoneNumber(),
            "country_code" => $userPhone->getPhoneCountryCode(),
            "verification_code" => $verifyCode,
        ]);

        if (!$authyResponse->success) {
            return false;
            // todo think about logging
        }

        return true;
    }
}
