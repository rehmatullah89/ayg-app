<?php
namespace App\Consumer\Entities;


class RetailerItemProperty extends Entity
{
    /**
     * @var string
     */
    private $uniqueRetailerItemId;
    /**
     * @var int
     */
    private $dayOfWeek;
    /**
     * @var int
     */
    private $restrictOrderTimeInSecsStart;
    /**
     * @var int
     */
    private $restrictOrderTimeInSecsEnd;

    public function __construct(
        string $uniqueRetailerItemId,
        int $dayOfWeek,
        int $restrictOrderTimeInSecsStart,
        int $restrictOrderTimeInSecsEnd
    ) {

        $this->uniqueRetailerItemId = $uniqueRetailerItemId;
        $this->dayOfWeek = $dayOfWeek;
        $this->restrictOrderTimeInSecsStart = $restrictOrderTimeInSecsStart;
        $this->restrictOrderTimeInSecsEnd = $restrictOrderTimeInSecsEnd;
    }

    /**
     * @return string
     */
    public function getUniqueRetailerItemId(): string
    {
        return $this->uniqueRetailerItemId;
    }

    /**
     * @return int
     */
    public function getDayOfWeek(): int
    {
        return $this->dayOfWeek;
    }

    /**
     * @return int
     */
    public function getRestrictOrderTimeInSecsStart(): int
    {
        return $this->restrictOrderTimeInSecsStart;
    }

    /**
     * @return int
     */
    public function getRestrictOrderTimeInSecsEnd(): int
    {
        return $this->restrictOrderTimeInSecsEnd;
    }
}
