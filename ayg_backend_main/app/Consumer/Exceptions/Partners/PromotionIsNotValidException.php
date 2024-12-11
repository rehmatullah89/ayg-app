<?php
namespace App\Consumer\Exceptions\Partners;

use App\Consumer\Exceptions\Exception;

class PromotionIsNotValidException extends Exception
{
    /**
     * @var string
     */
    private $body;

    public function __construct(
        $message = "",
        $code = 0,
        \Exception $previous = null,
        string $body
    ) {
        $this->body = $body;
    }
}
