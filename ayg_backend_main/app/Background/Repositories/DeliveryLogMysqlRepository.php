<?php
namespace App\Background\Repositories;

/**
 * Class DeliveryLogMysqlRepository
 * @package App\Background\Repositories
 */
class DeliveryLogMysqlRepository extends MysqlRepository implements DeliveryLogRepositoryInterface
{
    /**
     * @var \PDO
     */
    private $pdoConnection;

    /**
     * RetailerPingLogMysqlRepository constructor.
     * @param \PDO $pdoConnection
     */
    public function __construct(\PDO $pdoConnection)
    {
        $this->pdoConnection = $pdoConnection;
    }

    /**
     * @param $airportIataCode
     * @param $action
     * @param $timeStamp
     */
    public function logDeliveryStatusChangedToActive(string $airportIataCode, string $action, int $timeStamp): void
    {
        try {
            $stmt = $this->pdoConnection->prepare("INSERT INTO delivery_action_logs SET 
                `airportIataCode` = :airportIataCode,
                `action` = :actionStr,
                `timestamp` = :createdAt              
            ");

            $stmt->bindParam(':airportIataCode', $airportIataCode, \PDO::PARAM_STR);
            $stmt->bindParam(':actionStr', $action, \PDO::PARAM_STR);
            $stmt->bindParam(':createdAt', $timeStamp, \PDO::PARAM_INT);

            $result = $stmt->execute();

            if (!$result) {
                // echo "\nPDO::errorInfo():\n";
                //print_r($this->pdoConnection->errorCode());exit;
            }
        } catch (\Exception $e) {
            // log failed insert
        }
    }

    /**
     * @param $airportIataCode
     * @param $action
     * @param $timeStamp
     */
    public function logDeliveryStatusChangedToInactive(string $airportIataCode, string $action, int $timeStamp): void
    {
        try {
            $stmt = $this->pdoConnection->prepare("INSERT INTO delivery_action_logs SET 
                    `airportIataCode` = :airportIataCode,
                    `action` = :actionStr,
                    `timestamp` = :createdAt              
                ");

            $stmt->bindParam(':airportIataCode', $airportIataCode, \PDO::PARAM_STR);
            $stmt->bindParam(':actionStr', $action, \PDO::PARAM_STR);
            $stmt->bindParam(':createdAt', $timeStamp, \PDO::PARAM_INT);

            $result = $stmt->execute();

            if (!$result) {
                //echo "\nPDO::errorInfo():\n";
                //print_r($this->pdoConnection->errorInfo());exit;
            }
        } catch (\PDOException $e) {
            echo $e->getMessage();
        }
    }

    /**
     * @param $airportIataCode
     * @param $action
     * @param $timeStamp
     * @param $orderSequenceId
     */

    public function logOrderDeliveryStatus(string $airportIataCode, string $action, int $timeStamp, string $orderSequenceId): void
    {
        try {
            $stmt = $this->pdoConnection->prepare("INSERT INTO delivery_action_logs SET 
                    `airportIataCode` = :airportIataCode,
                    `action` = :actionStr,
                    `timeStamp` = :createdAt,
                    `orderSequenceId` = :orderSequenceId              
                ");

            $stmt->bindParam(':airportIataCode', $airportIataCode, \PDO::PARAM_STR);
            $stmt->bindParam(':actionStr', $action, \PDO::PARAM_STR);
            $stmt->bindParam(':createdAt', $timeStamp, \PDO::PARAM_INT);
            $stmt->bindParam(':orderSequenceId', $orderSequenceId, \PDO::PARAM_INT);

            $result = $stmt->execute();

            if (!$result) {
                //echo "\nPDO::errorInfo():\n";
                //print_r($this->pdoConnection->errorInfo());exit;
            }
        } catch (\PDOException $e) {
            echo $e->getMessage();
        }
    }
}
