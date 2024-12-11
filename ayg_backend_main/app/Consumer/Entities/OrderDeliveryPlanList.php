<?php
namespace App\Consumer\Entities;

use App\Consumer\Helpers\DateTimeHelper;
use ArrayIterator;

class OrderDeliveryPlanList extends Entity implements \JsonSerializable, \Countable, \IteratorAggregate
{
    private $data;

    public function __construct()
    {
        $this->data = [];
    }

    public function addItem(OrderDeliveryPlan $orderDeliveryPlan)
    {
        $this->data[] = $orderDeliveryPlan;
    }

    public function jsonSerialize()
    {
        return get_object_vars($this);
    }

    public function getIterator()
    {
        return new ArrayIterator($this->data);
    }

    public function count()
    {
        return count($this->data);
    }

    public function isTimestampInList(int $timestamp, string $timezone)
    {
        $dateTime = new \DateTime('now', new \DateTimeZone($timezone));
        $dateTime->setTimestamp($timestamp);

        $weekDay = $dateTime->format('N');

        /** @var OrderDeliveryPlan $deliveryPlan */
        foreach ($this->data as $deliveryPlan) {
            if ($deliveryPlan->getWeekDay() == $weekDay) {

                if ($deliveryPlan->getStartingTime() == 'CLOSED' || $deliveryPlan->getEndingTime() == 'CLOSED') {
                    return false;
                }

                $startingDateTime = DateTimeHelper::setHourAndMinuteBasedOnRetailerHours(
                    clone $dateTime,
                    $deliveryPlan->getStartingTime()
                );
                $endingDateTime = DateTimeHelper::setHourAndMinuteBasedOnRetailerHours(
                    clone $dateTime,
                    $deliveryPlan->getEndingTime()
                );

                if (($startingDateTime <= $dateTime) && ($endingDateTime >= $dateTime)) {
                    return true;
                }
            }
        }

        return false;
    }
}
