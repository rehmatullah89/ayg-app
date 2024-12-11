<?php

namespace App\Delivery\Responses;

use App\Delivery\Entities\OrderComment;

class OrderAddCommentResponse extends ControllerResponse implements \JsonSerializable
{
    /**
     * @var OrderComment
     */
    private $orderComment;

    public function __construct(
        OrderComment $orderComment
    ) {
        $this->orderComment = $orderComment;
    }


    public static function createFromOrderComment(OrderComment $orderComment)
    {
        return new OrderAddCommentResponse($orderComment);
    }

    public function jsonSerialize()
    {
        return $this->orderComment;
    }
}
