<?php
namespace App\Consumer\Entities\Partners\Grab;


use App\Consumer\Entities\Entity;

class SaveOrderResult extends Entity
{
    /**
     * @var array|null
     */
    private $cartExceptions;
    /**
     * @var string|null
     */
    private $exception;
    /**
     * @var string
     */
    private $orderID;

    public function __construct(
        ?array $cartExceptions,
        ?string $exception,
        string $orderID
    ) {
        $this->cartExceptions = $cartExceptions;
        $this->exception = $exception;
        $this->orderID = $orderID;
    }

    public static function createFromApiResult(array $apiResultArray): self
    {
        return new SaveOrderResult(
            $apiResultArray['cartExceptions'],
            $apiResultArray['exception'],
            (string)$apiResultArray['orderID']
        );
    }

    /**
     * @return array
     */
    public function getCartExceptions(): array
    {
        return $this->cartExceptions;
    }

    /**
     * @return string
     */
    public function getException(): string
    {
        return $this->exception;
    }

    /**
     * @return string
     */
    public function getOrderID(): string
    {
        return $this->orderID;
    }
}
