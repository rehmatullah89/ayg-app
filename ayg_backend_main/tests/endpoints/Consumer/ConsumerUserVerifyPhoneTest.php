<?php

require_once 'ConsumerBaseTest.php';

/**
 * Class ConsumerUserVerifyPhoneTest
 *
 * tested endpoint:
 * // Sign up User - Verify Phone
 * '/user/verifyPhone/a/:apikey/e/:epoch/u/:sessionToken/phoneId/:phoneId/verifyCode/:verifyCode'
 */
class ConsumerUserVerifyPhoneTest extends ConsumerBaseTest
{
    /**
     * for now skipped
     * not env_AuthyAPIKey
     * {"error_code":"60001","message":"Invalid API key","errors":{"message":"Invalid API key"},"success":false}
     */
    public function t_estCanAddPhone()
    {

    }

}