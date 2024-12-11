<?php
namespace App\Consumer\Entities;


class ScheduledOrderTimeRange extends Entity implements \JsonSerializable
{
    /**
     * @var \DateTime
     */
    private $from;
    /**
     * @var \DateTime
     */
    private $to;

    public function __construct(\DateTime $from, \DateTime $to)
    {

        $this->from = $from;
        $this->to = $to;
    }

    /**
     * @return \DateTime
     */
    public function getFrom(): \DateTime
    {
        return $this->from;
    }

    /**
     * @return \DateTime
     */
    public function getTo(): \DateTime
    {
        return $this->to;
    }

    public function getTimestamp(){
        return $this->from->getTimestamp() + round(($this->to->getTimestamp() - $this->from->getTimestamp()) / 2);
    }

    function jsonSerialize()
    {
        return [
            'display' => $this->from->format('h:i A') . ' - ' . $this->to->format('h:i A'),
            'timestamp' => $this->getTimestamp(),
        ];
    }
}
