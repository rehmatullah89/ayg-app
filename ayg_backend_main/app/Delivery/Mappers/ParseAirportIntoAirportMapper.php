<?php
namespace App\Delivery\Mappers;

use App\Delivery\Entities\Airport;
use Parse\ParseObject;

/**
 * Class ParseUserIntoUserMapper
 * @package App\Delivery\Mappers
 */
class ParseAirportIntoAirportMapper
{
    public static function map(ParseObject $parseAirport): Airport
    {
        return new Airport(
            $parseAirport->getObjectId(),
            $parseAirport->get('airportIataCode'),
            $parseAirport->get('airportTimezone')
        );
    }
}
