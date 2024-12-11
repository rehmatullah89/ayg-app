<?php
namespace App\Tablet\Entities;

class Pagination implements \JsonSerializable
{
    /**
     * @var int
     */
    private $totalRecords;
    /**
     * @var int
     */
    private $interval;
    /**
     * @var int
     */
    private $totalPages;
    /**
     * @var int
     */
    private $currentPage;

    public function __construct(
        $currentPage,
        $totalPages,
        $interval,
        $totalRecords
    )
    {
        $this->currentPage = $currentPage;
        $this->totalPages = $totalPages;
        $this->interval = $interval;
        $this->totalRecords = $totalRecords;
    }

    // function called when encoded with json_encode
    public function jsonSerialize()
    {
        return get_object_vars($this);
    }

    /**
     * @return int
     */
    public function getTotalRecords()
    {
        return $this->totalRecords;
    }

    /**
     * @param int $totalRecords
     */
    public function setTotalRecords($totalRecords)
    {
        $this->totalRecords = $totalRecords;
    }

    /**
     * @return int
     */
    public function getInterval()
    {
        return $this->interval;
    }

    /**
     * @param int $interval
     */
    public function setInterval($interval)
    {
        $this->interval = $interval;
    }

    /**
     * @return int
     */
    public function getTotalPages()
    {
        return $this->totalPages;
    }

    /**
     * @param int $totalPages
     */
    public function setTotalPages($totalPages)
    {
        $this->totalPages = $totalPages;
    }

    /**
     * @return int
     */
    public function getCurrentPage()
    {
        return $this->currentPage;
    }

    /**
     * @param int $currentPage
     */
    public function setCurrentPage($currentPage)
    {
        $this->currentPage = $currentPage;
    }
}