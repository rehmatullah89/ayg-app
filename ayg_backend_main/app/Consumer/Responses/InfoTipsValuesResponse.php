<?php

namespace App\Consumer\Responses;

use App\Consumer\Entities\InfoTipValue;
use App\Consumer\Entities\InfoTipValueList;

class InfoTipsValuesResponse extends ControllerResponse implements \JsonSerializable
{
    /**
     * @var InfoTipValueList
     */
    private $possibleValues;
    /**
     * @var InfoTipValueList
     */
    private $customValues;

    public function __construct(
        InfoTipValueList $possibleValues,
        InfoTipValueList $customValues
    ) {

        $this->possibleValues = $possibleValues;
        $this->customValues = $customValues;
    }

    public static function createFromJsonString($jsonString)
    {
        $values = json_decode($jsonString, true);

        $possibleValues = new InfoTipValueList();
        $customValues = new InfoTipValueList();

        foreach ($values['possibleValues'] as $item) {
            $possibleValues->addItem(new InfoTipValue(
                $item["display"],
                $item["type"],
                $item["value"],
                $item["isDefault"]
            ));
        }

        foreach ($values['customValues'] as $item) {
            $customValues->addItem(new InfoTipValue(
                $item["display"],
                $item["type"],
                $item["value"],
                $item["isDefault"]
            ));
        }

        return new InfoTipsValuesResponse($possibleValues, $customValues);
    }

    public function getDefault():?InfoTipValue
    {
        /** @var InfoTipValue $possibleValue */
        foreach ($this->possibleValues as $possibleValue) {
            if ($possibleValue->isIsDefault()) {
                return $possibleValue;
            }
        }
        return null;
    }

    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}
