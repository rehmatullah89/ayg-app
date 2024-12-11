<?php
namespace App\Delivery\Mappers;

use App\Delivery\Entities\TerminalGateMap;
use App\Delivery\Entities\TerminalGateMapShortInfo;

class TerminalGateMapIntoTerminalShortMapShortInfoMapper
{

    public static function map(TerminalGateMap $terminalGateMap)
    {
        return new TerminalGateMapShortInfo(
            $terminalGateMap->getTerminal(),
            $terminalGateMap->getConcourse(),
            $terminalGateMap->getLocationDisplayName()
        );
    }
}
