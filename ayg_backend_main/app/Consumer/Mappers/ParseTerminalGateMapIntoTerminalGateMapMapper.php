<?php
namespace App\Consumer\Mappers;

use App\Consumer\Entities\GeoLocation;
use App\Consumer\Entities\TerminalGateMap;
use Parse\ParseObject;

/**
 * Class ParseRetailerPOSConfigIntoRetailerPOSConfigMapper
 * @package App\Delivery\Mappers
 */
class ParseTerminalGateMapIntoTerminalGateMapMapper
{
    /**
     * @param ParseObject $parseTerminalGateMap
     * @return TerminalGateMap
     */
    public static function map(ParseObject $parseTerminalGateMap)
    {
        return new TerminalGateMap([
                'id' => $parseTerminalGateMap->getObjectId(),
                'createdAt' => $parseTerminalGateMap->getCreatedAt(),
                'updatedAt' => $parseTerminalGateMap->getUpdatedAt(),
                'airportIataCode' => $parseTerminalGateMap->get('airportIataCode'),
                'concourse' => $parseTerminalGateMap->get('concourse'),
                'displaySequence' => $parseTerminalGateMap->get('displaySequence'),
                'gate' => $parseTerminalGateMap->get('gate'),
                'geoPointLocation' => new GeoLocation([
                    'latitude' => $parseTerminalGateMap->get('geoPointLocation')->getLatitude(),
                    'longitude' => $parseTerminalGateMap->get('geoPointLocation')->getLongitude(),
                ]),
                'locationDisplayName' => $parseTerminalGateMap->get('locationDisplayName'),
                'terminal' => $parseTerminalGateMap->get('terminal'),
                'uniqueId' => $parseTerminalGateMap->get('uniqueId'),
                'gateDisplayName' => $parseTerminalGateMap->get('gateDisplayName'),
                'isDefaultLocation' => $parseTerminalGateMap->get('isDefaultLocation'),
            ]
        );
    }
}
