<?php
namespace App\Delivery\Entities;

/**
 * Class GeoLocation
 * @package App\Delivery\Entities
 */
class GeoLocation extends Entity implements \JsonSerializable
{
    /**
     * @var string
     */
    private $latitude;
    /**
     * @var string
     */
    private $longitude;

    public function __construct(array $data)
    {
        $this->latitude = $data['latitude'];
        $this->longitude = $data['longitude'];
    }

    /**
     * @return string
     */
    public function getLatitude()
    {
        return $this->latitude;
    }

    /**
     * @return string
     */
    public function getLongitude()
    {
        return $this->longitude;
    }

    // function called when encoded with json_encode
    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}
