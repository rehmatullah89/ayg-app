<?php

require_once 'ConsumerBaseTest.php';
require_once __DIR__.'/../../../lib/functions_userauth.php';
require_once __DIR__.'/../../../lib/functions.php';

/**
 * Class ConsumerUserCheckinTest
 *
 * tested endpoint:
 * // Checkin
 * '/user/checkin/a/:apikey/e/:epoch/u/:sessionToken'
 */
class ConsumerUserCheckinTest extends ConsumerBaseTest
{
    /**
     *
     */
    public function testCanCheckin()
    {
        $user = $this->createUser();
        $userDevice = $this->addParseObjectAndPushObjectOnStack('UserDevices', [
            'user' => $user,
            'isPushNotificationEnabled' => false,

        ]);
        $token = $user->getSessionToken();
        $sessionDevice=$this->getLastSessionDeviceByToken($token);
        $sessionDevice->set('userDevice',$userDevice);
        $sessionDevice->save();

        $url = $this->generatePath('user/checkin', $token, "");


        $devArray='eyJhcHBWZXJzaW9uIjoiMSIsImlzSW9zIjoiMSIsImlzQW5kcm9pZCI6IjAiLCJkZXZpY2VUeXBlIjoic29tZVR5cGUiLCJkZXZpY2VNb2RlbCI6InNvbWVNb2RlbCIsImRldmljZU9TIjoic29tZU9zIiwiZGV2aWNlSWQiOiJzb21lSWQiLCJwdXNoTm90aWZpY2F0aW9uSWQiOiJzb21lSWQiLCJ0aW1lem9uZUZyb21VVENJblNlY29uZHMiOiIzNjAwIiwiZ2VvTGF0aXR1ZGUiOiIxMS4xMSIsImdlb0xvbmdpdHVkZSI6IjExLjExIiwiY291bnRyeSI6IlVTIiwiaXNPbldpZmkiOnRydWUsImlzUHVzaE5vdGlmaWNhdGlvbkVuYWJsZWQiOnRydWV9';
        $deviceArrayDecoded = $this->getDeviceArray($token, 1);
var_dump($sessionDevice->getObjectId());

        $deviceArrayEncoded=@urlencode(@base64_encode(json_encode($deviceArrayDecoded)));



        $response = $this->post($url, [
            //'deviceArray' => 'eyJhcHBWZXJzaW9uIjoxLCJpc0lvcyI6MSwiaXNBbmRyb2lkIjowLCJkZXZpY2VUeXBlIjoic29tZVR5cGUiLCJkZXZpY2VNb2RlbCI6InNvbWVNb2RlbCIsImRldmljZU9TIjoic29tZU9zIiwiZGV2aWNlSWQiOiJzb21lSWQiLCJwdXNoTm90aWZpY2F0aW9uSWQiOiJzb21lSWQiLCJ0aW1lem9uZUZyb21VVENJblNlY29uZHMiOiIzNjAwIiwiZ2VvTGF0aXR1ZGUiOjExLjExLCJnZW9Mb25naXR1ZGUiOjExLjExfQ==',
            "deviceArray" => $deviceArrayEncoded,
        ]);


        var_dump($response);

        die();
        $arrayJsonDecodedBody = $response->getJsonDecodedBody(true);


        $keysThatShouldAppear = [
            'isLoggedIn',
            'isEmailVerified',
            'isBetaActive',
            'isLocked',
            'isPhoneVerified',
            'firstName',
            'lastName',
            'email',
            'phoneCountryCode',
            'phoneNumber',
            'isActive',
        ];

        foreach ($keysThatShouldAppear as $item) {
            $this->assertArrayHasKey($item, $arrayJsonDecodedBody);
        }


    }

    /**
     *
     */
    public function t1estCanNotCheckinWithBadSessionToken()
    {
        $sessionToken = 'someSessionTokenThatCanNotBeCreated';
        $url = $this->generatePath('user/checkin', $sessionToken, "");

        $response = $this->post($url, [
            'deviceArray' => 'eyJhcHBWZXJzaW9uIjoxLCJpc0lvcyI6MSwiaXNBbmRyb2lkIjowLCJkZXZpY2VUeXBlIjoic29tZVR5cGUiLCJkZXZpY2VNb2RlbCI6InNvbWVNb2RlbCIsImRldmljZU9TIjoic29tZU9zIiwiZGV2aWNlSWQiOiJzb21lSWQiLCJwdXNoTm90aWZpY2F0aW9uSWQiOiJzb21lSWQiLCJ0aW1lem9uZUZyb21VVENJblNlY29uZHMiOiIzNjAwIiwiZ2VvTGF0aXR1ZGUiOjExLjExLCJnZW9Mb25naXR1ZGUiOjExLjExfQ==',
        ]);

        $this->assertTrue($response->isHttpResponseCorrect());

        $arrayJsonDecodedBody = $response->getJsonDecodedBody(true);
        $this->assertArrayHasKey('error_code', $arrayJsonDecodedBody);
        $this->assertArrayHasKey('error_description', $arrayJsonDecodedBody);

        $responseDecoded = $response->getJsonDecodedBody();
        $this->assertEquals('AS_455', $responseDecoded->error_code);
    }

    private function getDeviceArray($sessionToken, $push){
        $deviceArrayPrepared["appVersion"] = 100;
        $deviceArrayPrepared["isIos"] = 1;
        $deviceArrayPrepared["isAndroid"] = 0;
        $deviceArrayPrepared["deviceType"] = 1;
        $deviceArrayPrepared["deviceModel"] = 1;
        $deviceArrayPrepared["deviceOS"] = 1;
        $deviceArrayPrepared["deviceId"] = 1;
        $deviceArrayPrepared["country"] = 'pl';
        $deviceArrayPrepared["isOnWifi"] = 1;
        $deviceArrayPrepared["isPushNotificationEnabled"] = 1;
        $deviceArrayPrepared["pushNotificationId"] = 1;
        $deviceArrayPrepared["timezoneFromUTCInSeconds"] = 0;
        $deviceArrayPrepared["geoLatitude"] = 1;
        $deviceArrayPrepared["geoLongitude"] = 1;



        return $deviceArrayPrepared;
    }
}