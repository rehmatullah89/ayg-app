<?php
namespace App\Consumer\Helpers;

use App\Consumer\Entities\User;

/**
 * Class UserHelper
 * @package App\Consumer\Helpers
 */
class UserHelper
{
    /**
     * @return string
     */
    public static function generateTwilioCode()
    {
        return mt_rand(1, 9) . mt_rand(0, 9) . mt_rand(0, 9) . mt_rand(0, 9);
    }
}