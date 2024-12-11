<?php

namespace App\Consumer\Repositories;

use App\Consumer\Entities\User;
use App\Consumer\Exceptions\UserIsNotConsumerException;
use App\Consumer\Exceptions\UserNotFoundException;
use App\Consumer\Mappers\ParseUserIntoUserMapper;
use Parse\ParseObject;
use Parse\ParseQuery;

/**
 * Class UserParseRepository
 * @package App\Consumer\Repositories
 */
class UserParseRepository extends ParseRepository implements UserRepositoryInterface
{
    public function getParseUserById($id)
    {
        $parseUser = new ParseObject("_User", $id);
        $parseUser->fetch();
        return $parseUser;
    }

    public function getUserById($id)
    {
        $query = new ParseQuery("_User");
        $query->equalTo("objectId", $id);
        $parseUser = $query->first();

        if (!$parseUser) {
            return null;
        }
        $user = ParseUserIntoUserMapper::map($parseUser);
        return $user;
    }

    public function updateProfileData(User $user)
    {
        $parseUser = $this->getParseUserById($user->getId());
        $parseUser->set('firstName', $user->getFirstName());
        $parseUser->set('lastName', $user->getLastName());
        $parseUser->set('email', $user->getEmail());

        if ($user->getId() != $GLOBALS['user']->getObjectId()){
            // hack to be sure that only logged user can modify a user
            throw new \Exception('Logged user can modify only himself');
        }
        $parseUser->save(true);
    }

    public function getUserByEmailOtherThenId(string $email, $id): ?User
    {
        $query = new ParseQuery("_User");
        $query->equalTo("email", $email);
        $query->notEqualTo("objectId", $id);
        $parseUser = $query->first();

        if (!$parseUser) {
            return null;
        }
        $user = ParseUserIntoUserMapper::map($parseUser);
        return $user;
    }
}
