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

/**
 * Class ConsumerUserEmailVerifyTest
 *
 * @todo cant tests
 * env_HerokuSystemPath not set up
 */
class ConsumerUserEmailVerifyTest extends ConsumerBaseTest
{
    /**
     */
    public function testCanAddPhone()
    {
        $email = 'ludwik.grochowina+' . rand(1, 1000000) . '@gmail.com';
        $type = 'c';

        $user = $this->createUser([
            'email' => $email,
        ]);

        $token = $this->forgotGenerateAndSaveToken($email);

        $url = $this->generatePathWithoutSession("user/emailVerify/t/$token");

        $response = $this->get($url);
        $jsonDecodedBody = $response->getJsonDecodedBody();
        // skipped due to the problem with
        // fopen(assets/email_templates/email_verify_failed.html): failed to open stream: No such file or directory</div><div><strong>File:</strong> /lib/functions.php</div><div><strong>Line:</strong> 557

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