<?php

namespace App\Consumer\Repositories;

use App\Consumer\Entities\Coupon;
use App\Consumer\Entities\User;
use App\Consumer\Services\CacheService;
use Parse\ParseObject;
use Predis\Client;

/**
 * Class UserCacheRepository
 * @package App\Consumer\Repositories
 */
class UserCacheRepository implements UserRepositoryInterface
{
    /**
     * @var UserRepositoryInterface
     */
    private $decorator;
    /**
     * @var CacheService Redis client, already connected
     */
    private $cacheService;

    /**
     * UserCacheRepository constructor.
     * @param UserRepositoryInterface $userCouponRepository
     * @param CacheService $cacheService
     */
    public function __construct(UserRepositoryInterface $userCouponRepository, CacheService $cacheService)
    {
        $this->decorator = $userCouponRepository;
        $this->cacheService = $cacheService;
    }

    /**
     * @param $id
     * @return ParseObject
     */
    public function getParseUserById($id)
    {
        return $this->decorator->getParseUserById($id);
    }

    /**
     * @param $id
     * @return User
     *
     * Gets User Entity from user Id
     */
    public function getUserById($id)
    {
        return $this->decorator->getUserById($id);
    }

    public function updateProfileData(User $user)
    {
        return $this->decorator->updateProfileData($user);
    }

    public function getUserByEmailOtherThenId(string $email, $id): ?User
    {
        return $this->decorator->getUserByEmailOtherThenId($email, $id);
    }
}
