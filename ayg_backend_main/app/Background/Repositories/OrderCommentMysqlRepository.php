<?php
namespace App\Background\Repositories;

use App\Delivery\Entities\OrderComment;
use App\Delivery\Entities\OrderCommentList;
use App\Delivery\Entities\OrderDetailed;

class OrderCommentMysqlRepository extends MysqlRepository implements OrderCommentRepositoryInterface
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

    public function store(OrderComment $orderComment)
    {
        try {
            $stmt = $this->pdoConnection->prepare("INSERT INTO order_comments SET 
            `author` = :author,
            `comment` = :comment,
            `createdAtUTC` = :createdAtUTC,
            `createdAtAirportTimezone` = :createdAtAirportTimezone,
            `orderId` = :orderId
        ");

            $author = $orderComment->getAuthor();
            $comment = $orderComment->getComment();
            $createdAtUTC = $orderComment->getCreatedAtUTC();
            $createdAtAirportTimezone = $orderComment->getCreatedAtAirportTimezone();
            $orderId = $orderComment->getOrderId();

            $stmt->bindParam(':author', $author, \PDO::PARAM_STR);
            $stmt->bindParam(':comment', $comment, \PDO::PARAM_STR);
            $stmt->bindParam(':createdAtUTC', $createdAtUTC, \PDO::PARAM_STR);
            $stmt->bindParam(':createdAtAirportTimezone', $createdAtAirportTimezone, \PDO::PARAM_STR);
            $stmt->bindParam(':orderId', $orderId, \PDO::PARAM_STR);

            $result = $stmt->execute();

            if (!$result) {
                // log failed insert
            }
        } catch (\Exception $e) {
            // log failed insert
        }
    }

    public function getByOrderAndTimezone(OrderDetailed $order, string $timezone): OrderCommentList
    {
        $orderCommentList = new OrderCommentList();

        $orderId = $order->getOrderId();

        $stmt = $this->pdoConnection->prepare("
            SELECT 
            *
            FROM 
            order_comments AS oc
            WHERE 
            oc.orderId= :orderId
        ");

        $stmt->bindParam(':orderId', $orderId, \PDO::PARAM_STR);

        $stmt->execute();
        $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        if (empty($result)) {
            return $orderCommentList;
        }

        foreach ($result as $orderCommentArray) {
            $format = 'Y-m-d H:i:s';
            $createdAtUTC = \DateTime::createFromFormat($format, $orderCommentArray['createdAtUTC']);

            $orderCommentList->addItem(new OrderComment(
                $orderCommentArray['orderId'],
                $orderCommentArray['author'],
                $orderCommentArray['comment'],
                $createdAtUTC->setTimezone(new \DateTimeZone($timezone))
            ));
        }

        return $orderCommentList;
    }
}
