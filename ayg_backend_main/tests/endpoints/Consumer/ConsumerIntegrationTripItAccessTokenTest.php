<?php

require_once 'ConsumerBaseTest.php';

/**
 * Class ConsumerIntegrationTripItAccessTokenTest
 *
 * tested endpoint:
 * // Access Token, this is a temporary token and sent back to user
 * '/integrations/tripIt/accessToken/a/:apikey/e/:epoch/u/:sessionToken'
 * @todo error Airport Sherpa's access to your TripIt account is no longer valid. Please reauthorize by tapping Change TripIt Account
 */
class ConsumerIntegrationTripItAccessTokenTest extends ConsumerBaseTest
{
    private function getTripItSignedIn($redirectUri, $oauthToken)
    {
        $tripItLoginUrl = 'https://www.tripit.com/oauth/signIn';
        $url = $redirectUri . '?' . 'oauth_token=' . $oauthToken . '&oauth_callback=' . getenv('env_EnvironmentDevTestHost') . '/data.php';
        $websiteHtml = $this->getWebsite($url);

        preg_match('#name="csrf_token" value="(.*?)"#', $websiteHtml, $m);
        $csrfToken = $m[1];
        preg_match('#name="oauth_token" value="(.*?)"#', $websiteHtml, $m);
        $oauthToken = $m[1];
        preg_match('#name="oauth_callback" value="(.*?)"#', $websiteHtml, $m);
        $oauthCallback = $m[1];
        preg_match('#name="toc" value="(.*?)"#', $websiteHtml, $m);
        $toc = $m[1];
        $websiteHtml = $this->postWebsite($tripItLoginUrl, [
            'csrf_token' => $csrfToken,
            'oauth_token' => $oauthToken,
            'enc_account_id' => '',
            'rsvp_redirect_url' => '',
            'oauth_callback' => $oauthCallback,
            'a2t_key' => '',
            'toc' => $toc,
            'redirect_url' => '',
            'email_address' => 'its_ur_sujit@hotmail.com',
            'password' => '14tomakefrens',
        ]);


        $websiteHtml = $this->postWebsite($redirectUri, [
            'csrf_token' => $csrfToken,
            'oauth_token' => $oauthToken,
            'rsvp_redirect_url' => '',
            'oauth_callback' => $oauthCallback,
        ]);
    }

    /**
     *
     */
    public function t_estThatTripItRequestTokenCanBeRequested()
    {
        $user = $this->createUser();

        if (file_exists('storage/cookie/tripitcookie.txt')) {
            unlink('storage/cookie/tripitcookie.txt');
        }

        $redirect_uri = 'https://www.tripit.com/oauth/authorize';

        $url = $this->generatePath('integrations/tripIt/requestToken', $user->getSessionToken(), "");
        $response = $this->get($url);

        $jsonDecodedBody = $response->getJsonDecodedBody();

        $this->getTripItSignedIn($redirect_uri, $jsonDecodedBody->oauth_token);

        $url = $this->generatePath('integrations/tripIt/accessToken', $user->getSessionToken(), "");
        $response = $this->get($url);

        $arrayJsonDecodedBody = $response->getJsonDecodedBody(true);
        $this->assertArrayHasKey('oauth_token', $arrayJsonDecodedBody);


        $jsonDecodedBody = $response->getJsonDecodedBody();
        $this->assertEquals($jsonDecodedBody->oauth_token, $arrayJsonDecodedBody['oauth_token']);

        $url = $this->generatePath('integrations/tripIt/revokeAccess', $user->getSessionToken(), "");
        $response = $this->get($url);


    }

    /**
     *
     */
    public function testThatTripItRequestTokenCanNotBeRequestedWithBadSessionToken()
    {
        $sessionToken = 'someSessionTokenThatCanNotBeCreated';
        $url = $this->generatePath('integrations/tripIt/accessToken', $sessionToken, "");

        $response = $this->get($url);
        $this->assertTrue($response->isHttpResponseCorrect());

        $arrayJsonDecodedBody = $response->getJsonDecodedBody(true);
        $this->assertArrayHasKey('error_code', $arrayJsonDecodedBody);
        $this->assertArrayHasKey('error_description', $arrayJsonDecodedBody);

        $responseDecoded = $response->getJsonDecodedBody();
        $this->assertEquals('AS_015', $responseDecoded->error_code);
    }
}