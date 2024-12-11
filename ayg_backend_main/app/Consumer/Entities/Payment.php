<?php

namespace App\Consumer\Entities;

class Payment extends Entity implements \JsonSerializable
{

    /**
     * @var string
     */
    private $userId;
    /**
     * @var string
     */
    private $externalCustomerId;

    public function __construct(
        string $userId,
        string $externalCustomerId
    )
    {
        $this->userId = $userId;
        $this->externalCustomerId = $externalCustomerId;
    }

    /**
     * @return string
     */
    public function getUserId(): string
    {
        return $this->userId;
    }

    /**
     * @return string
     */
    public function getExternalCustomerId(): string
    {
        return $this->externalCustomerId;
    }


    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}
