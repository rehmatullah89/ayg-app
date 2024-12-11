<?php
namespace App\Consumer\Entities;


class RetailerItemTimeRestrictions extends Entity implements \JsonSerializable
{
    const LAST_SECOND_OF_A_DAY = 24 * 60 * 60;
    /**
     * @var RetailerItemPropertyList
     */
    private $retailerItemPropertyList;

    public function __construct(
        RetailerItemPropertyList $retailerItemPropertyList
    ) {

        $this->retailerItemPropertyList = $retailerItemPropertyList;
    }

    public function isAvailableForDay(): bool
    {
        $retailerItemProperty = $this->retailerItemPropertyList->getFirst();
        if ($retailerItemProperty === null) {
            return true;
        }

        // if any pair is -1 / -1 then it is not available
        foreach ($this->retailerItemPropertyList as $retailerItemProperty) {
            if (
                $retailerItemProperty->getRestrictOrderTimeInSecsStart() == -1 &&
                $retailerItemProperty->getRestrictOrderTimeInSecsEnd() == -1
            ) {
                return false;
            }
        }
        return true;
    }


    public function isAvailableForGivenSecondOfDay(int $secondOfTheDay): bool
    {
        $retailerItemProperty = $this->retailerItemPropertyList->getFirst();
        if ($retailerItemProperty === null) {
            return true;
        }

        foreach ($this->retailerItemPropertyList as $retailerItemProperty) {
            if (
                $retailerItemProperty->getRestrictOrderTimeInSecsStart() == -1 &&
                $retailerItemProperty->getRestrictOrderTimeInSecsEnd() == -1
            ) {
                continue;
            }

            $start = $retailerItemProperty->getRestrictOrderTimeInSecsStart();
            $end = $retailerItemProperty->getRestrictOrderTimeInSecsEnd();
            if ($end == 0) {
                $end = self::LAST_SECOND_OF_A_DAY;
            }

            if ($secondOfTheDay >= $start && $secondOfTheDay <= $end) {
                return true;
            }
        }

        return false;
    }

    public function asArray(): array
    {
        if (count($this->retailerItemPropertyList) == 0) {
            return [
                "restrictOrderTimeInSecsStart" => 0,
                "restrictOrderTimeInSecsEnd" => 0,
                "availableOrderTimeInSecs" => [
                    [
                        "start" => 0,
                        "end" => 0,
                    ]
                ]
            ];
        }

        $first = $this->retailerItemPropertyList->getFirst();

        $array = [
            "restrictOrderTimeInSecsStart" => $first->getRestrictOrderTimeInSecsStart(),
            "restrictOrderTimeInSecsEnd" => $first->getRestrictOrderTimeInSecsEnd(),
        ];

        foreach ($this->retailerItemPropertyList as $item) {
            $array['availableOrderTimeInSecs'][] = [
                "start" => $item->getRestrictOrderTimeInSecsStart(),
                "end" => $item->getRestrictOrderTimeInSecsEnd(),
            ];
        }

        return $array;
    }


    // function called when encoded with json_encode
    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}
