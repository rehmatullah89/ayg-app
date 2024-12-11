<?php
namespace App\Background\Repositories;

use App\Delivery\Entities\OrderComment;
use App\Delivery\Entities\OrderDetailed;

interface OrderCommentRepositoryInterface
{
    public function store(OrderComment $orderComment);

    public function getByOrderAndTimezone(OrderDetailed $order, string $timezone);
}
