<?php

require_once 'ConsumerBaseTest.php';

/**
 * Class ConsumerWebBetaActionTest
 *
 * tested endpoint:
 * // Beta Activation
 * '/web/beta/action/a/:apikey/e/:epoch/u/:sessionToken/userObjectId/:userObjectId/activate/:activate'
 */
class ConsumerWebBetaActionTest extends ConsumerBaseTest
{
    /**
     * @todo check the error created by lack of redis values
     */
    public function testBetaCanBeActivated()
    {
        $user = $this->createUser([
            'isBetaActive' => false,
        ]);
        $userObjectId = $user->getObjectId();

        $this->addParseObjectAndPushObjectOnStack('BetaInvites', [
            'userEmail' => 'ludwik.grochowina+' . md5(time() . rand(1, 10000)) . '@gmail.com',
            'isActive' => false,
        ]);

        $activate = '1';

        $url = $this->generatePathForWebEndpoints('web/beta/action', $user->getSessionToken(), "userObjectId/$userObjectId/activate/$activate");

        $response = $this->get($url);

        $jsonDecodedBody = $response->getJsonDecodedBody();
        $this->assertEquals('Activated!', $jsonDecodedBody->json_resp_message);
        $this->assertEquals(1, $jsonDecodedBody->json_resp_status);


    }

    public function testBetaCanBeDeActivated()
    {
        $user = $this->createUser([
            'isBetaActive' => true,
        ]);
        $userObjectId = $user->getObjectId();

        $this->addParseObjectAndPushObjectOnStack('BetaInvites', [
            'userEmail' => 'ludwik.grochowina+' . md5(time() . rand(1, 10000)) . '@gmail.com',
            'isActive' => false,
        ]);

        $activate = '-1';

        $url = $this->generatePathForWebEndpoints('web/beta/action', $user->getSessionToken(), "userObjectId/$userObjectId/activate/$activate");

        $response = $this->get($url);

        $jsonDecodedBody = $response->getJsonDecodedBody();
        $this->assertEquals('Deactivated!', $jsonDecodedBody->json_resp_message);
        $this->assertEquals(1, $jsonDecodedBody->json_resp_status);


    }

}