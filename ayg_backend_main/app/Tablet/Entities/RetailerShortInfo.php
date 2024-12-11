<?php
namespace App\Tablet\Entities;

/**
 * Class RetailerShortInfo
 * @package App\Tablet\Entities
 */
class RetailerShortInfo extends Entity implements \JsonSerializable
{
    /**
     * @var
     */
    private $retailerName;
    /**
     * @var
     */
    private $retailerLocationName;
    /**
     * @var
     */
    private $retailerLogoUrl;
    /**
     * @var string
     * @see \App\Tablet\Entities\User::POSSIBLE_USER_TYPES
     */
    private $userType;

    public function __construct(
        $retailerName,
        $retailerLocationName,
        $retailerLogoUrl,
        $userType
    )
    {
        $this->retailerName = $retailerName;
        $this->retailerLocationName = $retailerLocationName;
        $this->retailerLogoUrl = $retailerLogoUrl;
        $this->userType = $userType;
    }



    // function called when encoded with json_encode
    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}