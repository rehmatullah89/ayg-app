<?php
namespace App\Background\Entities;

/**
 * Class RetailerPingLog
 * @package App\Background\Entities
 */
class RetailerPingLog extends Entity
{
    /**
     * @var string
     */
    private $retailerUniqueId;

    /**
     * @var int
     */
    private $timestamp;

    public function __construct(array $data)
    {
        $this->retailerUniqueId=$data['retailerUniqueId'];
        $this->timestamp=$data['timestamp'];
    }

    /**
     * @return string
     */
    public function getRetailerUniqueId()
    {
        return $this->retailerUniqueId;
    }

    /**
     * @return int
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }


}