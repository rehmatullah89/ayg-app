<?php
namespace App\Tablet\Entities;

/**
 * Class ListOfOrderShortInfoPaginatedAndClosedEarlyData
 * @package App\Tablet\Entities
 */
class ListOfOrderShortInfoPaginatedAndClosedEarlyData extends Entity implements \JsonSerializable
{
    /**
     * @var ListOfOrderShortInfoPaginated
     */
    private $listOfOrderShortInfoPaginated;
    /**
     * @var CloseEarlyData
     */
    private $closeEarlyData;

    public function __construct(
        ListOfOrderShortInfoPaginated $listOfOrderShortInfoPaginated,
        CloseEarlyData $closeEarlyData
    )
    {
        $this->listOfOrderShortInfoPaginated = $listOfOrderShortInfoPaginated;
        $this->closeEarlyData = $closeEarlyData;
    }

    /**
     * @return ListOfOrderShortInfoPaginated
     */
    public function getListOfOrderShortInfoPaginated()
    {
        return $this->listOfOrderShortInfoPaginated;
    }

    /**
     * @return CloseEarlyData
     */
    public function getCloseEarlyData()
    {
        return $this->closeEarlyData;
    }



    // function called when encoded with json_encode
    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}