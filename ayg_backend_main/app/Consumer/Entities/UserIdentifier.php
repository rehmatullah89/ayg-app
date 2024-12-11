<?php
namespace App\Consumer\Entities;


class UserIdentifier extends Entity implements \JsonSerializable
{
    /**
     * @var ?int
     */
    private $id;
    /**
     * @var string
     */
    private $deviceIdentifier;
    /**
     * @var string|null
     */
    private $phoneNumber;
    /**
     * @var string|null
     */
    private $phoneCountryCode;
    /**
     * @var User
     */
    private $parseUserId;
    /**
     * @var bool
     */
    private $isActive;

    public function __construct(
        ?string $id,
        string $deviceIdentifier,
        ?string $phoneCountryCode,
        ?string $phoneNumber,
        string $parseUserId,
        bool $isActive
    ) {
        $this->id = $id;
        $this->deviceIdentifier = $deviceIdentifier;
        $this->phoneCountryCode = $phoneCountryCode;
        $this->phoneNumber = $phoneNumber;
        $this->parseUserId = $parseUserId;
        $this->isActive = $isActive;
    }

    /**
     * @return mixed
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
    public function getDeviceIdentifier(): string
    {
        return $this->deviceIdentifier;
    }

    /**
     * @return string
     */
    public function getPhoneCountryCode(): ?string
    {
        return $this->phoneCountryCode;
    }

    /**
     * @return string
     */
    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumberAndCountryCode(int $phoneCountryCode, int $phoneNumber): self
    {
        $this->phoneNumber = $phoneNumber;
        $this->phoneCountryCode = $phoneCountryCode;
        return $this;
    }

    public function getParseUserId(): string
    {
        return $this->parseUserId;
    }

    public function setParseUserId(string $userId): self
    {
        $this->parseUserId = $userId;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function changePhoneData(UserPhone $userPhone)
    {
        $this->phoneCountryCode = $userPhone->getPhoneCountryCode();
        $this->phoneNumber = $userPhone->getPhoneNumber();
    }

    // function called when encoded with json_encode
    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}
