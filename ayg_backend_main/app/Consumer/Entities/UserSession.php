<?php
namespace App\Consumer\Entities;


class UserSession extends Entity implements \JsonSerializable
{
    /**
     * @var int|null
     */
    private $id;
    /**
     * @var string
     */
    private $token;
    /**
     * @var UserIdentifier
     */
    private $userIdentifier;
    /**
     * @var bool
     */
    private $isActive;
    /**
     * @var bool
     */
    private $hasFullAccess;
    /**
     * @var string|null
     */
    private $sessionDeviceId;
    /**
     * @var bool
     */
    private $sessionDeviceIsActive;

    public function __construct(
        ?int $id,
        string $token,
        UserIdentifier $userIdentifier,
        bool $isActive,
        bool $hasFullAccess,
        ?string $sessionDeviceId,
        bool $sessionDeviceIsActive
    ) {
        $this->id = $id;
        $this->userIdentifier = $userIdentifier;
        $this->isActive = $isActive;
        $this->hasFullAccess = $hasFullAccess;
        $this->token = $token;
        $this->sessionDeviceId = $sessionDeviceId;
        $this->sessionDeviceIsActive = $sessionDeviceIsActive;
    }

    /**
     * @return int|null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int|null $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getToken(): string
    {
        return $this->token;
    }

    /**
     * @return string
     */
    public function getTokenWithoutTypeIndicator(): string
    {
        $token = explode('-', $this->token);
        return $token[0];
    }

    /**
     * @return UserIdentifier
     */
    public function getUserIdentifier(): UserIdentifier
    {
        return $this->userIdentifier;
    }

    public function setUserIdentifier(UserIdentifier $userIdentifier): UserSession
    {
        $this->userIdentifier = $userIdentifier;
        return $this;
    }

    /**
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->isActive;
    }

    /**
     * @return bool
     */
    public function hasFullAccess(): bool
    {
        return $this->hasFullAccess;
    }

    public function setSessionHasFullAccess(): UserSession
    {
        $this->hasFullAccess = true;
        return $this;
    }

    /**
     * @return null|string
     */
    public function getSessionDeviceId(): ?string
    {
        return $this->sessionDeviceId;
    }

    /**
     * @return bool
     */
    public function isSessionDeviceActive(): bool
    {
        return $this->sessionDeviceIsActive;
    }


    // function called when encoded with json_encode
    public function jsonSerialize()
    {
        return get_object_vars($this);
    }

    public function setSessionDeviceId(?string $sessionDeviceId)
    {
        $this->sessionDeviceId = $sessionDeviceId;
        return $this;
    }

    public function setSessionDeviceAsInactive()
    {
        $this->sessionDeviceIsActive = false;
        return $this;
    }

    public function setSessionDeviceAsActive()
    {
        $this->sessionDeviceIsActive = true;
        return $this;
    }


}
