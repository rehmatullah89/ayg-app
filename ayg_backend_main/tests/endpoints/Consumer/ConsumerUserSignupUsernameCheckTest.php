<?php

require_once 'ConsumerBaseTest.php';

/**
 * Class ConsumerUserSignupUsernameCheckTest
 *
 * tested endpoint:
 * // Order Item Count
 * '/signup/usernameCheck/a/:apikey/e/:epoch/u/:sessionToken/type/:type/email/:email'
 */
class ConsumerUserSignupUsernameCheckTest extends ConsumerBaseTest
{


    /**
     *
     */
    public function testCanCheckEmailThatIsAvailable()
    {
        //$user = $this->createUser();
        $type = 'c';
        //$email = 'ludwik.grochowina+' . md5(time() . rand(1, 10000)) . '@gmail.com';

        //$email='pulkit+52@airportsherpa.io';
        $email = urlencode('ludwik+22@toptal.com');
        //$email = urlencode($email);

        $url = $this->generatePath('user/signup/usernameCheck', 0, "type/$type/email/$email");
        var_dump($url);

        $response = $this->get($url);

        $jsonDecodedBody = $response->getJsonDecodedBody();

        if ($jsonDecodedBody!==null){
            var_dump($jsonDecodedBody);
        }else{
            var_dump($response);
        }
        //$this->assertTrue($jsonDecodedBody->isAvailable);


    }

    /**
     *
     */
    public function tes1tCanCheckEmailThatIsNotAvailable()
    {
        $user = $this->createUser();
        $type = 'c';
        $email = $user->get('email');

        $email = urlencode($email);

        $url = $this->generatePath('user/signup/usernameCheck', $user->getSessionToken(), "type/$type/email/$email");

        $response = $this->get($url);

        $jsonDecodedBody = $response->getJsonDecodedBody();
        $this->assertFalse($jsonDecodedBody->isAvailable);


    }

    /**
     *
     */
    public function te11stCanNotCheckEmailWhenTypeIsIncorrect()
    {
        $user = $this->createUser();
        $type = 'z';
        $email = $user->get('email');

        $url = $this->generatePath('user/signup/usernameCheck', $user->getSessionToken(), "type/$type/email/$email");

        $response = $this->get($url);
        $this->assertTrue($response->isHttpResponseCorrect());
        $arrayJsonDecodedBody = $response->getJsonDecodedBody(true);
        $this->assertArrayHasKey('error_code', $arrayJsonDecodedBody);
        $this->assertArrayHasKey('error_description', $arrayJsonDecodedBody);
        $responseDecoded = $response->getJsonDecodedBody();
        $this->assertEquals('AS_416', $responseDecoded->error_code);


    }

}