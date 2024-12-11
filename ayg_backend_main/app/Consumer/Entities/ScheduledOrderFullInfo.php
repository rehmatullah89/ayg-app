<?php
namespace App\Consumer\Entities;


use App\Consumer\Helpers\DateTimeHelper;

class ScheduledOrderFullInfo extends Entity implements \JsonSerializable
{

    /** @var array<ScheduledOrderTimeRangeList> */
    private $data;

    public function __construct(
        Order $order,
        string $timezone,
        int $startTodayInAtLeastSeconds,
        OrderDeliveryPlanList $deliveryPlanList
    ) {
        $this->data = [];
        $day = new \DateTime('now', new \DateTimeZone($timezone));

        $oneDayTimeRange = new \DateInterval('P1D');
        for ($i = 0; $i < Order::SCHEDULED_ORDER_POSSIBLE_DAYS_AMOUNT; $i++) {

            $displayName = $day->format('m/d/Y');
            if ($i == 0) {
                $displayName = Order::SCHEDULED_ORDER_TODAY_STRING;
            } elseif ($i == 1) {
                $displayName = Order::SCHEDULED_ORDER_TOMORROW_STRING;
            }

            $this->data[$displayName] = [];
            $arrayTemp[$displayName] = clone $day;
            $day = $day->add($oneDayTimeRange);
        }


        /** @var \DateTime $day */
        foreach ($arrayTemp as $displayName => $day) {
            list($openingTimes, $closingTimes) = $this->getOpeningAndClosingHoursBasedOnDayAndOrder($day, $order);

            if (($openingTimes == 'CLOSED') || ($closingTimes == 'CLOSED')) {
                unset($this->data[$displayName]);
                continue;
            }

            $opening = DateTimeHelper::setHourAndMinuteBasedOnRetailerHours(clone $day, $openingTimes);
            $closing = DateTimeHelper::setHourAndMinuteBasedOnRetailerHours(clone $day, $closingTimes);

            $amountOfMinutes = ($closing->getTimestamp() - $opening->getTimestamp()) / 60;

            // when opening time is the same as closing time and hour equals zero,
            // that means someone set starting and closing for 12AM
            if ($amountOfMinutes == 0 && $opening->format('H')==0) {
                $amountOfMinutes = 24*60;
            }

            $amountOfPeriods = floor($amountOfMinutes / Order::SCHEDULED_ORDER_TIME_RANGE_IN_MINUTES);

            $todayNotEarlierThen = null;
            if ($displayName == Order::SCHEDULED_ORDER_TODAY_STRING) {
                $todayNotEarlierThen = new \DateTime('now +' . $startTodayInAtLeastSeconds . ' seconds');
            }

            $start = clone($opening);
            $this->data[$displayName] = new ScheduledOrderTimeRangeList();
            for ($i = 0; $i < $amountOfPeriods; $i++) {

                if (($todayNotEarlierThen === null) || $start > $todayNotEarlierThen) {

                    // add only when it is in deliveryPlan
                    $scheduledOrderTimeRange = new ScheduledOrderTimeRange(
                        clone($start),
                        (clone($start))->add(new \DateInterval('PT' . Order::SCHEDULED_ORDER_TIME_RANGE_IN_MINUTES . 'M')));

                    if ($deliveryPlanList->isTimestampInList($scheduledOrderTimeRange->getTimestamp(), $timezone)) {
                        $this->data[$displayName]->addItem($scheduledOrderTimeRange);
                    }
                }

                $start = $start->add(new \DateInterval('PT' . Order::SCHEDULED_ORDER_TIME_RANGE_IN_MINUTES . 'M'));

            }
        }
    }

    function jsonSerialize()
    {
        $return['days'] = [];
        $return['timeSlots'] = [];

        /**
         * @var string $dayName
         * @var ScheduledOrderTimeRangeList $values
         */
        foreach ($this->data as $dayName => $values) {
            if ($values->isEmpty()){
                continue;
            }

            $return['days'][] = $dayName;
            $return['timeSlots'][] = $values;
        }

        return $return;
    }


    /**
     * @param \DateTime $day
     * @param $order
     * @return array - in form of ["10:00 AM", "08:00 PM"]
     */
    private function getOpeningAndClosingHoursBasedOnDayAndOrder(\DateTime $day, Order $order): array
    {
        switch ($day->format('N')) {
            case 1:
                $openingTimes = $order->getRetailer()->getOpenTimesMonday();
                $closingTimes = $order->getRetailer()->getCloseTimesMonday();
                break;
            case 2:
                $openingTimes = $order->getRetailer()->getOpenTimesTuesday();
                $closingTimes = $order->getRetailer()->getCloseTimesTuesday();
                break;
            case 3:
                $openingTimes = $order->getRetailer()->getOpenTimesWednesday();
                $closingTimes = $order->getRetailer()->getCloseTimesWednesday();
                break;
            case 4:
                $openingTimes = $order->getRetailer()->getOpenTimesThursday();
                $closingTimes = $order->getRetailer()->getCloseTimesThursday();
                break;
            case 5:
                $openingTimes = $order->getRetailer()->getOpenTimesFriday();
                $closingTimes = $order->getRetailer()->getCloseTimesFriday();
                break;
            case 6:
                $openingTimes = $order->getRetailer()->getOpenTimesSaturday();
                $closingTimes = $order->getRetailer()->getCloseTimesSaturday();
                break;
            case 7:
                $openingTimes = $order->getRetailer()->getOpenTimesSunday();
                $closingTimes = $order->getRetailer()->getCloseTimesSunday();
                break;
            default:
                $openingTimes = '00:00 AM';
                $closingTimes = '11:59 PM';
        }
        return [$openingTimes, $closingTimes];
    }
}
