<?php

namespace App\Consumer\Repositories;

use App\Consumer\Entities\Coupon;
use App\Consumer\Entities\User;
use Parse\ParseObject;
use Parse\ParseUser;

/**
 * Interface UserRepositoryInterface
 * @package App\Consumer\Repositories
 */
interface UserRepositoryInterface
{
    /**
     * @param $id
     * @return ParseObject
     */
    public function getParseUserById($id);

    /**
     * @param $id
     * @return User
     *
     * get User entity by user id
     */
    public function getUserById($id);

    /**
     * @param User $user
     * @return mixed
     *
     * updates User Profile data (firstName, lastName, email)
     */
    public function updateProfileData(User $user);

    /**
     * @param string $email
     * @param $id
     * @return User|null
     */
    public function getUserByEmailOtherThenId(string $email, $id): ?User;
}
