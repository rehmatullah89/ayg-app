<?php

require_once 'ConsumerBaseTest.php';
require_once __DIR__ . '/../../../putenv.php';
$env_CacheEnabled = (getenv('env_CacheEnabled') === 'true');
$env_CacheRedisURL = getenv('env_CacheRedisURL');
$env_CacheSSLCA = getenv('env_CacheSSLCA');
$env_CacheSSLCert = getenv('env_CacheSSLCert');
$env_CacheSSLPK = getenv('env_CacheSSLPK');
require_once __DIR__ . '/../../../lib/initiate.redis.php';
require_once __DIR__ . '/../../../lib/functions_cache.php';
require_once __DIR__ . '/../../../lib/functions.php';

/**
 * Class ConsumerUserForgotValidateTokenTest
 *
 * tested endpoint:
 * // Forgot - Validate Token
 * '/user/forgot/validateToken/a/:apikey/e/:epoch/u/:sessionToken/type/:type/email/:email/token/:token'
 */
class ConsumerUserForgotValidateTokenTest extends ConsumerBaseTest
{

    /**
     *
     */
    public function testCorrectTokeHasStatusTrue()
    {
        $email = 'ludwik.grochowina+' . rand(1, 1000000) . '@gmail.com';
        $type = 'c';

        $user = $this->createUser([
            'email' => $email,
            'username' => $email . '-' . $type,
        ]);

        $token = $this->forgotGenerateAndSaveToken($email);
        $email=urlencode($email);
        $url = $this->generatePath('user/forgot/validateToken', $user->getSessionToken(), "type/$type/email/$email/token/$token");

        //var_dump($url);
        $response = $this->get($url);
        //var_dump($response);
        $jsonDecodedBody = $response->getJsonDecodedBody();
        $this->assertTrue($jsonDecodedBody->status);

    }


    /**
     *
     */
    public function testBadTokeHasStatusFalse()
    {
        $email = 'ludwik.grochowina+' . rand(1, 1000) . '@gmail.com';
        $type = 'c';
        $token = 'someTokenThatNotExists';

        $user = $this->createUser([
            'email' => $email,
        ]);

        $url = $this->generatePath('user/forgot/validateToken', $user->getSessionToken(), "type/$type/email/$email/token/$token");

        $response = $this->get($url);

        $jsonDecodedBody = $response->getJsonDecodedBody();

        $this->assertFalse($jsonDecodedBody->status);


    }


    private function forgotGenerateAndSaveToken($email)
    {
        $cacheKey = $this->forgotGetTokenName($email);
        // Generate a token
        $forgotToken = $this->generateToken();
        // Save it
        $x = setCache($cacheKey, $forgotToken, 0, 60 * 60);
        return $forgotToken;
    }

    private function generateToken()
    {
        srand(time());
        return mt_rand(1, 9) . mt_rand(0, 9) . mt_rand(0, 9) . mt_rand(0, 9) . mt_rand(0, 9) . mt_rand(0, 9);
    }

    private function forgotGetTokenName($email)
    {
        return "__FTKN__" . md5($email);
    }





}