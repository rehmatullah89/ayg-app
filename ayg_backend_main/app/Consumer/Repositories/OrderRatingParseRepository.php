<?php
namespace App\Consumer\Repositories;

use App\Consumer\Entities\OrderRating;
use App\Consumer\Mappers\ParseOrderIntoOrderMapper;
use App\Consumer\Mappers\ParseOrderRatingIntoOrderRatingMapper;
use App\Consumer\Mappers\ParseUserIntoUserMapper;
use Parse\ParseObject;
use Parse\ParseQuery;

/**
 * Class OrderRatingParseRepository
 * @package App\Consumer\Repositories
 */
class OrderRatingParseRepository extends ParseRepository implements OrderRatingRepositoryInterface
{
    /**
     * @param $orderId
     * @param $userId
     * @return OrderRating|null
     *
     * gets newest order rating by orderId and userId,
     * returns null when no rating added
     */
    public function getLastRating($orderId, $userId)
    {
        $userInnerQuery = new ParseQuery('_User');
        $userInnerQuery->equalTo("objectId", $userId);

        $orderInnerQuery = new ParseQuery('Order');
        $orderInnerQuery->equalTo("objectId", $orderId);

        $orderRatingParseQuery = new ParseQuery("OrderRatings");
        $orderRatingParseQuery->matchesQuery('order', $orderInnerQuery);
        $orderRatingParseQuery->matchesQuery('user', $userInnerQuery);
        $orderRatingParseQuery->includeKey('order');
        $orderRatingParseQuery->includeKey('user');
        $orderRatingParseQuery->descending('createdAt');
        $parseOrderRating = $orderRatingParseQuery->first();

        if (empty($parseOrderRating)){
            return null;
        }

        $orderRating = ParseOrderRatingIntoOrderRatingMapper::map($parseOrderRating);
        $orderRating->setOrder(ParseOrderIntoOrderMapper::map($parseOrderRating->get('order')));
        $orderRating->setUser(ParseUserIntoUserMapper::map($parseOrderRating->get('user')));

        return $orderRating;
    }

    /**
     * @param $orderId
     * @param $overAllRating
     * @param $feedback
     * @return OrderRating
     *
     *
     * Add Rating and Feedback to Order and return OrderRating as an response
     */
    public function addOrderRatingWithFeedback($orderId, $userId, $overAllRating, $feedback)
    {
        $parseUser = new ParseObject('_User', $userId);
        $parseOrder = new ParseObject("Order", $orderId);

        $parseOrder->fetch();
        $parseUser->fetch();

        $parseOrderRating = new ParseObject("OrderRatings");
        $parseOrderRating->set("order", $parseOrder);
        $parseOrderRating->set("overAllRating", $overAllRating);
        $parseOrderRating->set("user", $parseUser);
        $parseOrderRating->set("feedback", $feedback);
        $parseOrderRating->save();

        $orderRating = ParseOrderRatingIntoOrderRatingMapper::map($parseOrderRating);
        $orderRating->setOrder(ParseOrderIntoOrderMapper::map($parseOrder));
        $orderRating->setUser(ParseUserIntoUserMapper::map($parseUser));

        return $orderRating;
    }
}