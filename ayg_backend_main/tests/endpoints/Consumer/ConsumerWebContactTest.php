<?php

require_once 'ConsumerBaseTest.php';

/**
 * Class ConsumerWebContactTest
 *
 * tested endpoint:
 * // Save Contact form from the Website
 * '/web/contact/a/:apikey/e/:epoch/u/:sessionToken/name/:name/email/:email/comments/:comments/deviceId/:deviceId'
 */
class ConsumerWebContactTest extends ConsumerBaseTest
{
    /**
     * @todo there is no result on success
     */
    public function testContactFormCanBeSend()
    {
        $user = $this->createUser();

        $name = 'Someapp';
        $email = 'ludwik.grochowina+' . md5(time() . rand(1, 10000)) . '@gmail.com';
        $comments = 'Somecomments';
        $deviceId = 'someTestDeviceId';

        $url = $this->generatePathForWebEndpoints('web/contact', $user->getSessionToken(), "name/$name/email/$email/comments/$comments/deviceId/$deviceId");

        $response = $this->get($url);

        $jsonDecodedBody = $response->getJsonDecodedBody();
        $this->assertNull($jsonDecodedBody);




    }

}