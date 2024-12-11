<?php

require_once 'ConsumerBaseTest.php';

/**
 * Class ConsumerOpsSaveContactFormTest
 *
 * tested endpoint:
 * // Contact Form
 * '/ops/contact/a/:apikey/e/:epoch/u/:sessionToken/deviceId/:deviceId/comments/:comments'
 */
class ConsumerOpsSaveContactFormTest extends ConsumerBaseTest
{
    /**
     *
     */
    public function testThatContactFormCanBeSaved()
    {
        $user = $this->createUser();

        $deviceId = "XKJSKDVL";
        $comments = urlencode("This is the test for Comment");
        $url = $this->generatePath('ops/contact', $user->getSessionToken(), "");

        $response = $this->post($url,[
            'deviceId' => $deviceId,
            'comments' => $comments,
        ]);
        $this->assertTrue($response->isHttpResponseCorrect());

        $jsonDecodedBody = $response->getJsonDecodedBody();

        $this->assertEquals(1, $jsonDecodedBody->saved);


    }

    public function testThatContactFormWithAllowContact()
    {
        $user = $this->createUser();

        $deviceId = "XKJSKDVL";
        $comments = urlencode("This is the test for Comment");
        $allowContact = 0;
        $url = $this->generatePath('ops/contact', $user->getSessionToken(), "");

        $response = $this->post($url,[
            'deviceId' => $deviceId,
            'comments' => $comments,
            'allowContact' => $allowContact,
        ]);
        $this->assertTrue($response->isHttpResponseCorrect());

        $jsonDecodedBody = $response->getJsonDecodedBody();

        $this->assertEquals(1, $jsonDecodedBody->saved);


    }

    public function testThatContactFormWithAllowContactContactNameAndEmail()
    {
        $user = $this->createUser();

        $deviceId = "XKJSKDVL";
        $comments = urlencode("This is the test for Comment");
        $allowContact = 0;
        $contactName = urlencode("Test Name");
        $contactEmail = 'ludwik.grochowina+' . md5(time() . rand(1, 10000)) . '@gmail.com';
        $url = $this->generatePath('ops/contact', $user->getSessionToken(), "");

        $response = $this->post($url,[
            'deviceId' => $deviceId,
            'comments' => $comments,
            'allowContact' => $allowContact,
            'contactName' => $contactName,
            'contactEmail' => $contactEmail,
        ]);
        $this->assertTrue($response->isHttpResponseCorrect());

        $jsonDecodedBody = $response->getJsonDecodedBody();

        $this->assertEquals(1, $jsonDecodedBody->saved);


    }

}