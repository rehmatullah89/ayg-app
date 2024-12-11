# Airport Sherpa API Usage Guide

## Information for Base structures

### Keys

> See root README.MD


----------

## Airports List

List of all airports listed in the Airport Class

> /airports/list/a/:apikey/e/:epoch/u/:userid

##### Parameters

1. Standard API Auth parameters (a, e, u)

##### JSON Response Parameters

The response will be the following elements in an array:

1. *airportCity*
	* Name of the City of the Airport
2. *airportName*
	* Airport Name
3. *isReady*
	* Boolean, listing if the Airport is ready with Airport Sherpa
4. *airportIataCode*
	* IATA Code
5. *imageBackground*
	* URL of the background image
6. *timezone*
	* Timezone of the Airport
7. *isDeliveryReady*
	* true=Delivery orders available for this airport; false=Not ready
8. *isPickupReady*
	* true=Pickup orders available for this airport; false=Not ready
9. *geoPointLocation*
	* Geo Point location with a subarray with two elements: longitude and latitude
10. *objectId*
	* Object Id for the airport record


----------

## Airports Near Geo Location

List of all airports listed in the Airport Class near a geo location

> /airports/list/a/:apikey/e/:epoch/u/:userid/latitude/:latitude/longitude/:longitude

##### Parameters

1. Standard API Auth parameters (a, e, u)
2. *latitude*
	* Latitude
3. *longitude*
	* Longitude

##### JSON Response Parameters

The response will be the following elements in an array, ordered by the nearest listed first:

1. *airportCity*
	* Name of the City of the Airport
2. *airportName*
	* Airport Name
3. *isReady*
	* Boolean, listing if the Airport is ready with Airport Sherpa
4. *airportIataCode*
	* IATA Code
5. *imageBackground*
	* URL of the background image
6. *timezone*
	* Timezone of the Airport
7. *isDeliveryReady*
	* true=Delivery orders available for this airport; false=Not ready
8. *isPickupReady*
	* true=Pickup orders available for this airport; false=Not ready
9. *geoPointLocation*
	* Geo Point location with a subarray with two elements: longitude and latitude
10. *objectId*
	* Object Id for the airport record

----------

## Airports Details

Details of the Airport by IATA Code, along with the weather information

> /airports/find/a/:apikey/e/:epoch/u/:userid/airportIataCode/:airportIataCode

##### Parameters

1. Standard API Auth parameters (a, e, u)
2. *airportIataCode*
	* Airport IATA Code

##### JSON Response Parameters

The response will be the following element:

1. *airportCity*
	* Name of the City of the Airport
2. *airportName*
	* Airport Name
3. *isReady*
	* Boolean, listing if the Airport is ready with Airport Sherpa
4. *airportIataCode*
	* IATA Code
5. *imageBackground*
	* URL of the background image
6. *timezone*
	* Timezone of the Airport
7. *isDeliveryReady*
	* true=Delivery orders available for this airport; false=Not ready
8. *isPickupReady*
	* true=Pickup orders available for this airport; false=Not ready
9. *geoPointLocation*
	* Geo Point location with a subarray with two elements: longitude and latitude
10. *objectId*
	* Object Id for the airport record
11.	*weather*
	* See Weather API; once transitioned this attribute will be removed from this API		

----------

## Airports Weather

Details of the weather at Airport by IATA Code

> /airports/weather/a/:apikey/e/:epoch/u/:userid/airportIataCode/:airportIataCode

##### Parameters

1. Standard API Auth parameters (a, e, u)
2. *airportIataCode*
	* Airport IATA Code

##### JSON Response Parameters

The response will be the following element:

1. *weather*
		1. * This contains an array with the following elements with Weather Forecast at the Airport for next 5 days
		2. *date*
			* Date of the Forecast adjusted for the local time at the airport. The first key will be for the current date
		3. *timestampUTC*
			* Timestamp of the forecast
		4. *tempFahrenheit*
			* Temperature; For the first key, this will be the current temperature
		5. *tempMinFahrenheit*
			* Minimum temperature
		6. *tempMasFahrenheit*
			* Maximum temperature
		7. *weatherText*
			* Text explaining the current temperature. This will be one word, e.g. Clear, Rain
		8. *iconURL*
			* URL to the weather icon
		8. *windSpeed*
			* Wind Speed in MPH

----------

## Airport Ads

List of ads for the airport

> /airports/ads/a/:apikey/e/:epoch/u/:userid/airportIataCode/:airportIataCode

##### Parameters

1. Standard API Auth parameters (a, e, u)
2. *airportIataCode*
	* Airport IATA Code

##### JSON Response Parameters

The response will be an array with the following elements in each sub-array:

1. *retailerUniqueId*
	* Retailer's uniqueId to link to on the menu screen
2. *displaySeconds*
	* Number of seconds to show the ad before moving the next one
3. *imageAd*
	* URL to the ad image
		
----------

## Airlines List

List of all airlines listed in the Airlines Class

> /airlines/list/a/:apikey/e/:epoch/u/:userid

##### Parameters

1. Standard API Auth parameters (a, e, u)

##### JSON Response Parameters

The response will be the following element:

1. *airlineIcaoCode*
	* ICAO Code for the Airline; this must be searchable
2. *airlineName*
	* Name of the Airline
3. *airlineCallSign*
	* Call Sign used by Airports
4. *airlineCountry*
	* Originating Country
5. *airlineIataCode*
	* Airline IATA Code
6. *uniqueId*
	* Unique id for the record
7. *topRanked*
	* Boolean, not being used right now
8. *objectId*
	* Object Id for the airline record

----------

## TerminalGateMap

Location at the Airport with the Terminal Gate mappings

> /gatemap/a/:apikey/e/:epoch/u/:userid/airportIataCode/:airportIataCode

##### Parameters

1. Standard API Auth parameters (a, e, u)
2. *airportIataCode*
	* Airport Iata Code for which the Gate Map is required

##### JSON Response Parameters

The response will be an array with each having the following elements:

1. *airportIataCode*
	* Airport IATA Code
2. *displaySequence*
	* Display Sequence is a numeric value to be used to order the list when showing the Gap Map as selectable Option
3. *terminal*
	* Terminal
4. *concourse*
	* Concourse; can be blank when not applicable for an airport
5. *gate*
	* Gate
6. *locationDisplayName*
	* Name of location without english prefix such as Gate, e.g. this will say, 10 for Gate 10.
6. *gateDisplayName*
	* Name to be display for this location. This represents just the Gate area. So you should list this along with Terminal and Concourse
7. *geoPointLocation*
	* Geo Point location with a subarray with two elements: longitude and latitude
8. *uniqueId*
	* Unique Id
9. *objectId*
	* Object Id for the record; this matches the locationId from retailer records
10. *gpsRangeInMeters*
	* Range within which this location should be searched; override default of 50 meters
11. *terminalDisplayName*
	* Terminal name to display
12. *concourseDisplayName*
	* Concourse name to display


----------

## TerminalGateMap Nearest Gate

Find nearest Gate location near a Geo Location

> /gatemap/near/a/:apikey/e/:epoch/u/:userid/latitude/:latitude/longitude/:longitude

##### Parameters

1. Standard API Auth parameters (a, e, u)
2. *latitude*
	* Latitude
3. *longitude*
	* Longitude

##### JSON Response Parameters

The response will be the following element:

1. *airportIataCode*
	* Airport IATA Code
2. *displaySequence*
	* Display Sequence is a numeric value to be used to order the list when showing the Gap Map as selectable Option
3. *terminal*
	* Terminal
4. *concourse*
	* Concourse; can be blank when not applicable for an airport
5. *gate*
	* Gate
6. *locationDisplayName*
	* Name to be display for this location. This represents just the Gate area. So you should list this along with Terminal and Concourse
7. *geoPointLocation*
	* Geo Point location with a subarray with two elements: longitude and latitude
8. *uniqueId*
	* Unique Id
9. *objectId*
	* Object Id for the record; this matches the locatioId from retailer records
10. *withinOneMile*
	* True or False indicating if the Geo Location within 1 mile of the airport; If this flag is true, they show that you are in the airport
11. *withinTenMiles*
	* True or False indicating if the Geo Location within 10 miles of the airport; If this flag is true, they show that you are near the airport, else assume the user is far from any airport, so show the list
12. *gpsRangeInMeters*
	* Range within which this location should be searched; override default of 50 meters
