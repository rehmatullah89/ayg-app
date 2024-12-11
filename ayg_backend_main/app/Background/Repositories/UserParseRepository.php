<?php
namespace App\Background\Repositories;

use App\Background\Entities\User;
use App\Background\Exceptions\DeliveryUserNotFoundException;
use App\Background\Mappers\ParseUserIntoUserMapper;
use Parse\ParseException;
use Parse\ParseObject;
use Parse\ParseQuery;
use Parse\ParseUser;

class UserParseRepository
{
    const DELIVERY_USER_TYPE = 'd';

    public function getUserByEmail($deliveryUserEmail)
    {
        $query = new ParseQuery('_User');
        $query->equalTo('email', $deliveryUserEmail);
        $query->equalTo('typeOfLogin', self::DELIVERY_USER_TYPE);
        $query->equalTo("hasDeliveryAccess", true);
        $result = $query->find(true, true);

        if (empty($result)) {
            return null;
        }

        return ParseUserIntoUserMapper::map($result[0]);
    }

    public function updateDeliveryUser(User $user, $deliveryUserPassword, $firstName, $lastName)
    {
        $deliveryUserPassword = md5($deliveryUserPassword . $GLOBALS['env_PasswordHashSalt']);

        $query = ParseUser::query();
        $query->equalTo('email', $user->getEmail());
        $result = $query->find(true, true);
        $parseUser = $result[0];
        $parseUser->set("password", $deliveryUserPassword);
        $parseUser->set("firstName", $firstName);
        $parseUser->set("lastName", $lastName);
        $parseUser->set("isBetaActive", true);
        $parseUser->set("isActive", true);
        $parseUser->set("isLocked", false);
        $parseUser->set("hasConsumerAccess", false);
        $parseUser->set("hasTabletPOSAccess", false);

        $parseUser->save(true);
        var_dump('updated with password ' . $deliveryUserPassword);

        return ParseUserIntoUserMapper::map($parseUser);
    }

    public function addDeliveryUser($deliveryUserEmail, $deliveryUserPassword, $firstName, $lastName)
    {
        $deliveryUserPassword = md5($deliveryUserPassword . $GLOBALS['env_PasswordHashSalt']);


        $user = new ParseUser();
        $user->set("username", $deliveryUserEmail . '-' . self::DELIVERY_USER_TYPE);
        $user->set("password", $deliveryUserPassword);
        $user->set("email", $deliveryUserEmail);
        $user->set("firstName", $firstName);
        $user->set("lastName", $lastName);
        $user->set("typeOfLogin", self::DELIVERY_USER_TYPE);
        $user->set("hasDeliveryAccess", true);
        $user->set("isBetaActive", true);
        $user->set("isActive", true);
        $user->set("isLocked", false);
        $user->set("hasConsumerAccess", false);
        $user->set("hasTabletPOSAccess", false);

        try {
            $user->signUp();
            // Hooray! Let them use the app now.
        } catch (ParseException $ex) {
            // Show the error message somewhere and let the user try again.
            echo "Error: " . $ex->getCode() . " " . $ex->getMessage();
        }

        return ParseUserIntoUserMapper::map($user);
    }
}
