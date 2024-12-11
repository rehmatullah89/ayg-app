<?php
namespace App\Background\Services;

use App\Background\Exceptions\PingNotFoundException;
use App\Background\Repositories\PingLogRepositoryInterface;
use Parse\ParseUser;

/**
 * Class ReportService
 * @package App\Backround\Services
 */
class ReportService extends Service
{
    /**
     * @var PingLogRepositoryInterface
     */
    private $pingLogRepository;

    public function __construct(
        PingLogRepositoryInterface $pingLogRepository
    )
    {
        $this->pingLogRepository = $pingLogRepository;
    }

    /**
     * @param $retailerUniqueId
     * @param $startTimestamp
     * @param $endTimestamp
     * @param $period
     * @return float
     * @throws \Exception
     */
    public function getLostRetailerPingPercentageForAGivenTimeRange($retailerUniqueId, $startTimestamp, $endTimestamp, $period)
    {
        if ($endTimestamp < $startTimestamp) {
            throw new \Exception('EndTimestamp can not be smaller then startTimestamp');
        }

        $countsOfPingsForTheDay = $this->pingLogRepository->getRetailerPingBetweenTimestamp($retailerUniqueId, $startTimestamp, $endTimestamp);
        // $lastLog = $this->pingLogRepository->getLastRetailerPingBeforeGivenTimestamp($retailerUniqueId, $endTimestamp);

        // No pings found so up time was 0
        if ($countsOfPingsForTheDay === null) { 

            return 100;
        }

        // if($lastLog === null) {
        //     // there is no first (no any) log, then return 0 (0 percent lost pings)
        //     throw new PingNotFoundException('No pings for a given range');
        // }

        // print_r($firstLog);exit;

        $startTimestampLoop = $startTimestamp;
        $validPings = 0;
        while($endTimestamp > $startTimestampLoop) {

            $middlePingFound = false;

            // Check if there is a ping between startTimestampLoop and startTimestampLoop + period
            foreach($countsOfPingsForTheDay as $log) {

                // If timestamp is greater than startTimestampLoop+$period
                if($log["timestamp"] > $startTimestampLoop+$period) {

                    // Means we have gone beyond upper timestamp
                    break;
                }

                // If timestamp is less than startTimestampLoop
                if($log["timestamp"] >= $startTimestampLoop
                    && $log["timestamp"] <= $startTimestampLoop+$period) {

                    $middlePingFound = true;
                    break;
                }
            }

            if($middlePingFound == true) {

                $validPings++;
            }

            $startTimestampLoop += $period;
        }

        /*
        foreach($countsOfPingsForTheDay as $i => $log) {

            // Is this a valid ping
            if(intval($countsOfPingsForTheDay[$i+1]["timestamp"]) - intval($log["timestamp"]) >= $period/2) {

                $validPings++;
            }
            else {

                echo($log["timestamp"] . " > " . $countsOfPingsForTheDay[$i+1]["timestamp"] . "\r\n");
            }

            if($i == count_like_php5($countsOfPingsForTheDay)-2) {

                break;
            }
        }
        */
       
        // get amount of lost pings after last ping
        // $lastIndex = (count_like_php5($firstLog)-1);

        // get amount of lost pings before first ping
        // $time = $firstLog[0]["timestamp"] - $startTimestamp;
        // $lostPingsCountBeforeFirstPing = floor($time / $period);

        // // get amount of lost pings after last ping
        // $time = $endTimestamp - $firstLog[$lastIndex]["timestamp"];
        // $lostPingsCountAfterLastPing = floor($time / $period);

        // $count = $pingsLost + $lostPingsCountBeforeFirstPing + $lostPingsCountAfterLastPing;

        /*
        echo("<br />");
        echo(' | ' . $pingsLost . ' | ' . $lostPingsCountBeforeFirstPing . ' | ' . $lostPingsCountAfterLastPing);exit;

        $count = $this->pingLogRepository->countRetailerPingsBetweenTimestamps($retailerUniqueId, $startTimestamp, $endTimestamp);

        // get amount of lost pings before first ping
        $time = $firstLog[0]["timestamp"] - $startTimestamp;
        echo($startTimestamp . ' - ' . $endTimestamp);exit;
        $lostPingsCountBeforeFirstPing = floor($time / $period);

        // get amount of lost pings after last ping
        $time = $endTimestamp - $lastLog->getTimestamp();
        $lostPingsCountAfterLastPing = floor($time / $period);
        /*
        
         */
        // expected amount of pings between first and last ping (including borders)
        // $time = $lastLog->getTimestamp() - $firstLog[0]["timestamp"];
        $time = $endTimestamp - $startTimestamp;
        $expectedPingsCount = floor($time / $period);

        // echo(date("Y-m-d", $startTimestamp) . "\r\n");
        // echo("$endTimestamp - $startTimestamp" . "\r\n");
        // echo($expectedPingsCount . " - " . $validPings . " - " . (100 - round(($validPings / $expectedPingsCount) * 100, 2)) . "\r\n");exit;
        return (100 - round(($validPings / $expectedPingsCount) * 100, 2));
    }

    /**
     * @param $deliveryIds
     * @param $startTimestamp
     * @param $endTimestamp
     * @param $period
     * @return Array
     * @throws \Exception
     */
    public function getLostDeliveryPingPercentageForAGivenTimeRange($deliveryIds, $startTimestamp, $endTimestamp, $period)
    {
        if ($endTimestamp < $startTimestamp) {
            throw new \Exception('EndTimestamp can not be smaller then startTimestamp');
        }

        $allPings = $this->pingLogRepository->getDeliveryPingBetweenTimestamp($deliveryIds, $startTimestamp, $endTimestamp);


        if ($allPings === null) {
           $allPings = [];
        }

        $countOfPingsMissed = 0;
        $missedPing = [];
        $lastTimestamp = $startTimestamp;

        foreach($allPings as $pingRecord) {

            $timeBetweenPings = $pingRecord["timestamp"] - $lastTimestamp;

            // Delay between pings
            if($timeBetweenPings > $period) {

                $countOfPingsMissed += floor($timeBetweenPings / $period);
                $missedPing[] = ["from" => $lastTimestamp, "to" => $pingRecord["timestamp"]];
            }

            $lastTimestamp = intval($pingRecord["timestamp"]);
        }

        if($lastTimestamp < $endTimestamp) {

            $timeBetweenPings = $endTimestamp - $lastTimestamp;
            $countOfPingsMissed += floor($timeBetweenPings / $period);
            $missedPing[] = ["from" => $lastTimestamp, "to" => $endTimestamp];
        }

        // expected amount of pings between first and last ping (including borders)
        $time = $endTimestamp - $startTimestamp;
        $expectedPingsCount = floor($time / $period) + 1;

        return [
            round(($countOfPingsMissed) / $expectedPingsCount * 100, 2),
            $missedPing];
    }}