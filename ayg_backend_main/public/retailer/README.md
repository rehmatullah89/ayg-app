# Airport Sherpa API Usage Guide

## Retailers

### Keys

> See root README.MD


## Overview

This wrapper API provides Retailer-related functionality, such as search, menu, and directions. To view the full usage view the documentation in README.MD of the API root directory.

## Retailers near a Gate

This function returns a list of Parse objectId's for Retailers near a given gate. It will basically return all objectId's for a given Airport, but ordered by their distance to the provided gate.

> Variation 1 - Distance from Current Location, with a provided Retailer Type (e.g. Food)
> /retailer/bydistance/a/:apikey/e/:epoch/u/:sessionToken/airportIataCode/:airportIataCode/locationId/:locationId/retailerType/:retailerType/limit/:limit

> Variation 2 - Distance from Current Location, without a provided Retailer Type (defaults to Food)
> /retailer/bydistance/a/:apikey/e/:epoch/u/:sessionToken/airportIataCode/:airportIataCode/locationId/:locationId/limit/:limit

> Variation 3 - Distance from Current Location, with a provided Retailer Type (e.g. Food), BUT with a different Sort location. This is to be used when listing Retailers near a location different than current location. E.g. Near my Departure Gate.
> /retailer/bydistance/a/:apikey/e/:epoch/u/:sessionToken/airportIataCode/:airportIataCode/locationId/:locationId/terminalSort/:terminalSort/concourseSort/:concourseSort/gateSort/:gateSort/limit/:limit


##### Parameters

1. Standard API Auth parameters (a, e, u)
2. *airportIataCode*
	* IATA Airport Code of which the list of Retailers is requested
3. *locationId*
	* Location Id from the TerminalGateMap API call; this is the location of the user
4. *terminalSort*
	* (Optional, you can skip this) Set if sort required is different from Current location Terminal, Concourse and Gate
5. *concourseSort*
	* (Optional, you can skip this) Set if sort required is different from Current location Terminal, Concourse and Gate
6. *gateSort*
	* (Optional, you can skip this) Set if sort required is different from Current location Terminal, Concourse and Gate
7. *retailerType*
	* (Optional, you can skip this) Category / Retailer Type to filter one, e.g. Food; **Note**: You must URL Encode this value this it has special characters. It must match the case of the value in Parse.
8. *limit*
	* Limit the number of records returned, e.g. 10 for the Restaurant Cards. If you want all records, set it to 0.

##### JSON Response Parameters

The response will be an array with the following elements in each sub-array. The array will be ordered by distance with nearest to farthest.

*uniqueId*
	* This is the key of the sub-arrays

1. *distanceMilesToGate*
	* Distance in Miles from the Gate (rounded to second decimal)
2. *distanceStepsToGate*
	* Distance in Walking Steps from the Gate
3. *walkingTimeToGate*
	* Walking time in minutes to the Gate
4. *differentTerminalFlag*
	* If set to Y indicates that the distance includes transfer to a different Terminal. **The user must be shown a notice indicating Terminal change.**
5. *sortedSequence*
	* Sequence number of the API sort order

----------

## Distance to a retailer from given Gate location

This function returns a distance and time metrics to walk to a Retailer from a given gate location. This function should be used when displaying the distance and time estimates on the Restaurant / Retailer screen, or in a screen to supplement the Retailer information pulled directly from Parse Class.

> /retailer/distance/a/:apikey/e/:epoch/u/:sessionnToken/airportIataCode/:airportIataCode/fromLocationId/:fromLocationId/toRetailerLocationId/:toRetailerLocationId

##### Parameters

1. Standard API Auth parameters (a, e, u)
2. *airportIataCode*
	* Airport IATA Code
3. *fromLocationId*
	* Location Id from TerminalGateMap, identifying the starting location
4. *toRetailerLocationId*
	* Location Id from the Retailer API, identifying the retailer's location
	
##### JSON Response Parameters

The response will be an array with the following elements in each sub-array. Each sub-array represents a Segement. For example, going from Gate A1 to D1, will require switching Terminals from A to D. Therefore there will be 3 sub-arrays with first listing distance estimates within Terminal A, then second will list distance estimates Terminal transfer to D, then third will list distance estimates within Terminal D to arrive at D1.

In a scenario when requesting distance estimates within the same Terminal, the response will include only one sub-array.

1. *distanceMilesToGate*
	* Distance in Miles from the Gate (rounded to second decimal)
2. *distanceStepsToGate*
	* Distance in Walking Steps from the Gate
3. *walkingTimeToGate*
	* Walking time in minutes to the Gate
4. *differentTerminalFlag*
	* If set to Y indicates that the distance includes transfer to a different Terminal. **The user must be shown a notice indicating Terminal change.**


----------

## Directions

This function returns text directions along with an image of the path to be taken from provided Gate location to a Retailer.

> /retailer/directions/a/:apikey/e/:epoch/u/:sessionToken/airportIataCode/:airportIataCode/fromLocationId/:fromLocationId/toRetailerLocationId/:toRetailerLocationId/referenceRetailerId/:referenceRetailerId

##### Parameters

1. Standard API Auth parameters (a, e, u)
2. *airportIataCode*
	* Airport IATA Code
3. *fromLocationId*
	* From Location Id from TerminalGateMap API call, identifying user's current location
4. *toRetailerLocationId*
	* To Location Id from Retailer API call, identifying retailer's location
5. *referenceRetailerId*
	* Optional value (set to 0 if not provided), else list the retailer object id identifying where is the user is headed
	
##### JSON Response Parameters

The response will be an array with the following elements in each sub-array. Each sub-array represents a Segment. For example, going from Gate A1 to D1, will require switching Terminals from A to D. Therefore there will be 3 sub-arrays with first listing directions within Terminal A, then second will list directions Terminal transfer to D, then third will list directions within Terminal D to arrive at D1.

In a scernario when requesting directions within the same Terminal, the response will include only one sub-array.

1. *directionsBySegments*
	* This is a sub-array that will contain up to 3 sub-arrays, one each for a segment. A segment is specific to a Terminal. E.g. Going from B15 to A7, will contain three segments--Within Terminal B, Terminal B to A, Within Terminal A. Each segment contains the following elements:
		1. *pathImage*
			* URL to path image to be shown; **NOTE:** This field might be empty when providing Terminal to Terminal path. You need manage this exception.
		2. *segmentPathText*
			* Lists the Start and End location of the segment, e.g. Gate B15 to B5 or Terminal B to Terminal A
		3. *directions*
			* This is an array that lists individual entries to be displayed as turn by turn directions. The array will contain the following elements:
				1. *distanceSteps*
					* Distance in Walking Steps from the last Turn to the next
				2. *distanceMiles*
					* Distance in Miles from the last Turn to the next
				3. *walkingTime*
					* Walking time in minutes from the last Turn to the next
				4. *directionCue*
					* The values will include: L, R, S (Left, Right, Straight respectively); these values should be used to show direction icons for each step
				5. *displayText*
					* This is an array with lines of text explaining the Turn direction; In most cases you will see only one line
		4. *distanceMiles*
			* Total Distance in Miles for the Segment (rounded to second decimal)
		5. *distanceSteps*
			* Total Distance in Steps for the Segment
		6. *walkingTime*
			* Total Walking time in minutes for the Segment
2. *totalDistanceMetricsForTrip*
	* This is a sub-array that will contain up 3 elements, listing total distance metrics for the Trip
		1. *distanceMiles*
			* Total Distance in Miles for the Trip (rounded to second decimal)
		2. *distanceSteps*
			* Total Distance in Steps for the Trip
		3. *walkingTime*
			* Total Walking time in minutes for the Trip
		4. *reEnterSecurityFlag*
			* Y=means directions require traveler to go through security again, N=No security rentry required


----------

## Menu - Item List

This function returns list of items available for a Retailer.

> /retailer/menu/a/:apikey/e/:epoch/u/:sessionToken/retailerId/:retailerId

##### Parameters

1. Standard API Auth parameters (a, e, u)
2. *retailerId*
	* Unique Id of the Retailer

##### JSON Response Parameters

The response will be an array with the following elements in each sub-array. Each sub-array represents items for a category, e.g. Appetizers, Burgers, etc. The sequence of the array (marked by the index, e.g. 0, 1, etc), must be followed when displaying the items. For example, if the index item 0, contains menu items for Category Appetizers, then this category must be shown first. Each sub-array will contain:

1. *Name of Category* [index name]
	* Name of the category the item belongs to, e.g. Appetizers, this will be used as the index name; the following will be the elements of this sub-array
		1. *itemId*
			* Item Identifier; to be used for any Order operations
		2. *itemName*
			* Item Name
		3. *itemDescription*
			* Item Description
		4. *itemPrice*
			* Price (number) in cents; this should be used for any calculations.
		5. *itemPriceDisplay*
			* Price (string) in dollars with a $ notation; this value should be displayed to the user
		6. *itemImageURL*
			* If an image of the item exists, a URL pointing to it will be returned; else a blank value would be returned
		7. *itemImageThumbURL*
			* If a thumbnail image of the item exists, a URL pointing to it will be returned; else a blank value would be returned
		8. *allowedThruSecurity*
			* true or false, indicates if this item is allowed through security
		9. *itemTags*
			* Tags associated with the item
		10. *restrictOrderTimeInSecsStart*
			* -1, or a value between 0 and 86400, indicating number of seconds since midnight airport time is this order available from (i.e. Start). A value of -1 means this order is not available on this day of the week. A value of 0 means, the item is available all day.
		11. *restrictOrderTimeInSecsEnd*
			* Same as above *restrictOrderTimeInSecsStart* but this indicates the end of the item availability time for the day.

----------

## Menu v2.0 - Item List for a given timestamp (used in order scheduling)

This function returns list of items available for a Retailer and a given timestamp.

> /retailer/menu/a/:apikey/e/:epoch/u/:sessionToken/retailerId/:retailerId/timestamp/:timestamp

##### Parameters

1. Standard API Auth parameters (a, e, u)
2. *retailerId*
	* Unique Id of the Retailer
2. *timestamp*
	* unix timestamp, set to 0 for current menu

##### App Cache Rules: 

*Cache for 15 mins. Allow for Gzipped response.*

##### JSON Response Parameters

The response will be an array with the following elements in each sub-array. Each sub-array represents items for a category, e.g. Appetizers, Burgers, etc. The sequence of the array (marked by the index, e.g. 0, 1, etc), must be followed when displaying the items. For example, if the index item 0, contains menu items for Category Appetizers, then this category must be shown first. Each sub-array will contain:

1. *featured*
	* Sub-array of featured items should be displayed
		1. *itemName*
			* Name of the item
		2. *itemDescription*
			* Description of the item if displayed
		3. *imageURL*
			* URL of the image to be displayed
		4. *itemPrice*
			* Price of the item with the dollar sign, e.g. $8.45
		5. *tooltip*
			* Sub-array of text that needs to be listed underneath the name of the featured item. Each array represent one string that should be displayed in a sequence separated by a (vertical middle aligned) dot
				1. *textDisplay*
					* Text to be displayed
				2. *hexColor*
					* Hex code of the color (without #), e.g. 000000 = black color
				3. *icon*
					* (Optional) Icon to be displayed on the left side of the text, if provided.
						1. *lib*
							* Name of the font library to be used. Allowed list includes
								1. *fa*
									* Font Awesome
								2. *ion*
									* Ionicons
								3. *typ*
									* Typicons
								4. *line*
									* Linecons
								5. *zurb*
									* Foundation Icon Fonts 3
						2. *code*
							* Icon code

2. *categories*
	* Sub-array of items listing them under the category
		1. *config*
			* Attributes of how the category should be displayed
				1. *categoryName*
					* Name of the category the items belongs to, e.g. Appetizers
				3. *highlight*
					* (Optional) Sub-array with details of the text to be displayed (when provided) as highlight
						1. *textDisplay*
							* Text to be displayed
						2. *hexColor*
							* Hex code of the color (without #), e.g. 000000 = black color
						3. *icon*
							* (Optional) Icon to be displayed on the left side of the text, if provided.
								1. *lib*
									* Name of the font library to be used. Allowed list includes
										1. *fa*
											* Font Awesome
										2. *ion*
											* Ionicons
										3. *typ*
											* Typicons
										4. *line*
											* Linecons
										5. *zurb*
											* Foundation Icon Fonts 3
								2. *code*
									* Icon code

		2. *items*
			* List of items under the category
				1. *itemId*
					* Item Identifier; to be used for any Order operations
				2. *itemName*
					* Item Name
				3. *itemDescription*
					* Item Description
				4. *itemPrice*
					* Price (number) in cents; this should be used for any calculations.
				5. *itemPriceDisplay*
					* Price (string) in dollars with a $ notation; this value should be displayed to the user
				6. *itemImageURL*
					* If an image of the item exists, a URL pointing to it will be returned; else a blank value would be returned
				7. *itemImageThumbURL*
					* If a thumbnail image of the item exists, a URL pointing to it will be returned; else a blank value would be returned
				8. *allowedThruSecurity*
					* true or false, indicates if this item is allowed through security
				9. *itemTags*
					* Tags associated with the item
				10. *restrictOrderTimeInSecsStart*
					* -1, or a value between 0 and 86400, indicating number of seconds since midnight airport time is this order available from (i.e. Start). A value of -1 means this order is not available on this day of the week. A value of 0 means, the item is available all day.
				11. *restrictOrderTimeInSecsEnd*
					* Same as above *restrictOrderTimeInSecsStart* but this indicates the end of the item availability time for the day.
				12. *itemPrepTimeGTAvg*
					* E.g. 25 mins. This value is only listed when item prep time > retailer's avg prep time


----------

## Menu - Item Modifiers

**Note**: Not all items will have Modifiers. If you receive found=0, it means there are no modifiers and the item can be added to order directly.

Each item can one or more modifiers. Modifiers are selections that a user can add to their existing order. For example, if the Item is Pizza, then a modifier can be Toppings. There will be then Modifier Options under the Toppings modifier, e.g. Peppers, Chicken, etc. 

This function returns list of Modifiers and their Options items available for an Item.

> /retailer/menu/a/:apikey/e/:epoch/u/:sessionToken/retailerId/:retailerId/itemId/:itemId

##### Parameters

1. Standard API Auth parameters (a, e, u)
2. *retailerId*
	* Unique Id of the Retailer
3. *itemId*
	* Item Identifier

##### JSON Response Parameters

The response will be an array with the following elements in each sub-array. Each sub-array represents Modifiers, e.g. Toppings. Each sub-array has a further another array with the list of Options available for the modifier. The Options can be optional or required. Each option has a quantity minimum and maximum, along with price for each qauntitiy.

For example, if an Modifier, such as Cheese on Pizza, is listed as:
 * required = true
 * minimum = 1
 * maximum = 5
 
The Options under this modifier can be:

 * American Cheese (pricePerUnit = $1)
 * Swiss Cheese (pricePerUnit = $2)
 * No Cheese (pricePerUnit = $0)
 
In this case the user MUST select one more Cheese options (because required = true and minimum = 1), and can add one more quantities of cheese servings (up to 5 = maximum). For each serving of cheese there be a price associated with it, e.g. Swiss Cheese with two servings means, $4 will be added to the price of the Pizza.

Here is how the sub-array will be structured:

1. *Name of the Modifier* [index name of the sub-array]
	* Name of the Modifier that are available for the e.g. Toppings; this will be used as the index name; the following will be the elements of this sub-array
		1. *modifierDescription*
			* Modifier Description; can be null, if so, it should not be displayed
		2. *maxQuantity*
			* Maximum Quantity allowed for this Modifier
		3. *minQuantity*
			* Minimum Quantity allowed for this Modifier
		4. *isRequired*
			* true or false, indicating if this Modifier is a required (as in minQuantity of the Options MUST be selected)
		5. *displaySequence*
			* Sequence of the modifiers; the array is pre-sorted per this sequence
		6. *options*
			* Further sub-array, that will list the Options applicable for the modifier, e.g. Cheese Type
				1. *optionId*
					* Identifier for the Option (to be used during adding item and its modifier options to cart)
				2. *optionName*
					* Name of the Option to be displayed, e.g. Cheese Type
				3. *optionDescription*
					* Option Description; can be null, if so, it should not be displayed
				4. *pricePerUnit*
					* Price (in cents) for the Option per Quantity
				5. *pricePerUnitDisplay*
					* Price (in dollars) with $ notation for the Option per Quantity


----------

## Retailer - Information

This function returns basic information about the Retailer.

> /info/a/:apikey/e/:epoch/u/:sessionToken/retailerId/:retailerId

##### Parameters

1. Standard API Auth parameters (a, e, u)
2. *retailerId*
	* Unique Id of the Retailer

##### JSON Response Parameters

The response will be an array with the following elements, listing different attributes of the Retailer.

1. *retailerName*
	* Name of the Retailer
2. *retailerType*
	* This will be a sub-array	
		1. *retailerType*
			* Retailer Type, e.g. Food, Books & Mags, etc.
		2. *displayOrder*
			* Display Order
		3. *iconCode*
			* Icon code
3. *airportIataCode*
	* 3 Letter code of the airport the Retailer belongs to, e.g. BWI
4. *openTimesMonday*
5. *openTimesTuesday*
6. *openTimesWednesday*
7. *openTimesThursday*
8. *openTimesFriday*
9. *openTimesSaturday*
10. *openTimesSunday*
	* Airport timezone specific opening time for the provided week and weekend day, e.g. 5:00 AM
11. *closeTimesMonday*
12. *closeTimesTuesday*
13. *closeTimesWednesday*
14. *closeTimesThursday*
15. *closeTimesFriday*
16. *closeTimesSaturday*
17. *closeTimesSunday*
	* Airport timezone specific closing time for the provided week and weekend day, e.g. 10:00 PM
18. *cuisineCategories*
	Sub-array, listing categories that apply to the Retailer's cuisine, e.g. American, Asian
19. *description*
	* Description of the Retailer
20. *searchTags*
	* Sub-array, listing keywords that will result in this retailer to be shown in searches
21. *hasDelivery*
	* Boolean, listing if the Retailer offers Delivery with Airport Sherpa
22. *hasPickup*
	* Boolean, listing if the Retailer offers Pickup with Airport Sherpa	
23. *imageBackground*
	* URL to the background image for the Retailer
24. *imageLogo*
	* URL to the logo image for the Retailer
25. *isChain*
	* Boolean, listing if the Restaurant is a Chain
26. *location*
	* This will be a sub-array
		1. *terminal*
			* Airport Terminal where the Retailer is located, e.g. A, B, C
		2. *concourse*
			* Airport Concourse where the Retailer is located; blank if none listed
		3. *gate*
			* Airport Gate where the Retailer is located, e.g. 1, 2, 3
		4. *locationDisplayName*
			* Display name of where the retailer is located
		5. *locationId*
			* Object Id for TerminalGateMap location
		6. *geoPointLocation*
			* Geo Point location with a subarray with two elements: 
				1. *longitude*
					* Longitude
				2. *latitude*
					* Latitude
27. *uniqueId*
	* Unique Id associated with the Retailer; to be used for subsequent Retailer and Order transactions
28. *retailerPriceCategory*
	* This will be a sub-array
		1. *retailerPriceCategory*
			* Retailer Price Category, e.g. 1, 2, 3, 4
		2. *retailerPriceCategorySign*
			* $, $$, $$$, $$$$
		3. *displayOrder*
			* Display Order
		4. *iconCode*
			* Icon code
29. *retailerCategory*
	* This will be a sub-array
		1. *retailerCategory*
			* Retailer Category
		2. *displayOrder*
			* Display Order
		3. *iconCode*
			* Icon code
30. *retailerFoodSeatingType*
	* This will be a sub-array
		1. *retailerFoodSeatingType*
			* Retailer Food Seating Type
		2. *displayOrder*
			* Display Order
		3. *iconCode*
			* Icon code


----------

## Retailer List by Airport

This function returns the list of retailers by Airport with their full information.

> /list/a/:apikey/e/:epoch/u/:sessionToken/airportIataCode/:airportIataCode/retailerType/:retailerType

##### Parameters

1. Standard API Auth parameters (a, e, u)
2. *airportIataCode*
	* Airport Iata Code
3. *retailertype*
	* Retailer Type value; set it to 0 if you want all

##### JSON Response Parameters

The response will be an array with all the elements of retailer/info API call


----------

## Retailer - Trending

This function returns basic information about the Retailers that are trending. You can narrow the list by a retailerType as well

> /trending/a/:apikey/e/:epoch/u/:sessionToken/airportiatacode/:airportiatacode/retailertype/:retailertype/limit/:limit

##### Parameters

1. Standard API Auth parameters (a, e, u)
2. *airportiatacode*
	* Airport Iata Code
3. *retailertype*
	* Retailer Type value; set it to 0 if you want all
4. *limit*
	* Limit number of records; set to 0 for all

##### JSON Response Parameters

The response will be an array with the following elements, with the sequence of popularity (highest first)

1. *retailerName*
	* Name of the Retailer
2. *retailerType*
	* Retailer Type, e.g. Food, Books & Mags, etc.
3. *airportIataCode*
	* 3 Letter code of the airport the Retailer belongs to, e.g. BWI
4. *openTimesMonday*
5. *openTimesTuesday*
6. *openTimesWednesday*
7. *openTimesThursday*
8. *openTimesFriday*
9. *openTimesSaturday*
10. *openTimesSunday*
	* Airport timezone specific opening time for the provided week and weekend day, e.g. 5:00 AM
11. *closeTimesMonday*
12. *closeTimesTuesday*
13. *closeTimesWednesday*
14. *closeTimesThursday*
15. *closeTimesFriday*
16. *closeTimesSaturday*
17. *closeTimesSunday*
	* Airport timezone specific closing time for the provided week and weekend day, e.g. 10:00 PM
18. *cuisineCategories*
	Sub-array, listing categories that apply to the Retailer's cuisine, e.g. American, Asian
19. *description*
	* Description of the Retailer
20. *searchTags*
	* Sub-array, listing keywords that will result in this retailer to be shown in searches
21. *hasDelivery*
	* Boolean, listing if the Retailer offers Delivery with Airport Sherpa
22. *hasPickup*
	* Boolean, listing if the Retailer offers Pickup with Airport Sherpa	
23. *imageBackground*
	* URL to the background image for the Retailer
24. *imageLogo*
	* URL to the logo image for the Retailer
25. *isChain*
	* Boolean, listing if the Restaurant is a Chain
26. *terminal*
	* Airport Terminal where the Retailer is located, e.g. A, B, C
27. *concourse*
	* Airport Concourse where the Retailer is located; blank if none listed
28. *gate*
	* Airport Gate where the Retailer is located, e.g. 1, 2, 3
29. *locationDisplayName*
	* Display name of where the retailer is located
30. *geoPointLocation*
	* Geo Point location with a subarray with two elements: longitude and latitude
31. *locationId*
	* Object Id for TerminalGateMap location
32. *uniqueId*
	* Unique Id associated with the Retailer; to be used for subsequent Retailer and Order transactions


----------

## Retailer - Ping

This function returns a status listing if the POS system of the  information about the Retailer.

> /ping/a/:apikey/e/:epoch/u/:sessionToken/retailerId/:retailerId

##### Parameters

1. Standard API Auth parameters (a, e, u)
2. *retailerId*
	* Unique Id of the Retailer

##### JSON Response Parameters

There will be one variable response for the POS system. If the retailer doesn't support an integration with Airport Sherpa, an error AS_500 will be returned.

1. *isAccepting*
	* true=POS is active, false=POS is inactive hence orders should *not* be processed; this will show false if the retailer is closed (early or on time)
2. *isClosed*
	* true=Closed, false=Open; will show true (closed) if closed early
3. *pingStatusDescription*
	* Shows user-descriptive text to be shown regarding the ping; only shows error messages and can be added even if available=true (e.g. ping successful but retailer closing time is soon and hence won't accept orders now)
4. *available*
	* Same as isAccepting; added for backward incompatible

----------

## Retailer - Ping All

This function returns a status listing if the POS system of the  information about the Retailer.

> /ping/all/a/:apikey/e/:epoch/u/:sessionToken

##### Parameters

1. Standard API Auth parameters (a, e, u)

##### JSON Response Parameters

Array of pairs, keys are retailer UniqueIds

1. *isAccepting*
	* true=POS is active, false=POS is inactive hence orders should *not* be processed; this will show false if the retailer is closed (early or on time)
2. *isClosed*
	* true=Closed, false=Open; will show true (closed) if closed early
3. *pingStatusDescription*
	* Shows user-descriptive text to be shown regarding the ping; only shows error messages and can be added even if available=true (e.g. ping successful but retailer closing time is soon and hence won't accept orders now)

----------

## Retailer - Are Tips Allowed?

This function returns a status listing if the POS system of the  information about the Retailer.

> /tipCheck/a/:apikey/e/:epoch/u/:sessionToken/retailerId/:retailerId

##### Parameters

1. Standard API Auth parameters (a, e, u)
2. *retailerId*
	* Unique Id of the Retailer

##### JSON Response Parameters

There will be one variable response for the POS system. If the retailer doesn't support an integration with Airport Sherpa, an error AS_500 will be returned.

1. *allowed*
	* 1=Yes, 0=No


----------

## Retailer - Types

Lists all types that are available in the Retailer table.

> /type/a/:apikey/e/:epoch/u/:sessionToken

##### Parameters

1. Standard API Auth parameters (a, e, u)

##### JSON Response Parameters

1. *retailerType*
	* Type of Retailer
2. *displayOrder*
	* Display Sequence
3. *iconCode*
	* Font Awesome Icon name

	
----------

## Retailer - Price Category

Lists all types that are available in the Retailer table.

> /priceCategory/a/:apikey/e/:epoch/u/:sessionToken

##### Parameters

1. Standard API Auth parameters (a, e, u)

##### JSON Response Parameters

1. *retailerPriceCategory*
	* Type of Retailer Price Category; This is a numeric value 1, 2, 3, 4
2. *retailerPriceCategorySign*
	* Type of Retailer Price Category, it shows $, $$, $$$, $$$$
3. *displayOrder*
	* Display Sequence
4. *iconCode*
	* Font Awesome Icon name; N/A for price categories


----------

## Retailer - Category

Lists all Category that are available in the Retailer table.

> /category/a/:apikey/e/:epoch/u/:sessionToken

##### Parameters

1. Standard API Auth parameters (a, e, u)

##### JSON Response Parameters

1. *retailerCategory*
	* Category of Retailer
2. *displayOrder*
	* Display Sequence
3. *iconCode*
	* Font Awesome Icon name
4. *retailerType*
	* This is a sub-array listing following attributes:
		1. *retailerType*
			* Shows values such as Food, Retail, etc.
		2. *displayOrder*
			* Retailer Type display sequence
		3. *iconCode*
			* Font Awesome Icon name
	
----------

## Retailer - foodSeatingType

Lists all types of Food Seating that are available in the Retailer table, e.g. Sit down, Kiosk, etc.

> /foodSeatingType/a/:apikey/e/:epoch/u/:sessionToken

##### Parameters

1. Standard API Auth parameters (a, e, u)

##### JSON Response Parameters

1. *retailerFoodSeatingTyoe*
	* Food Seating Type of Retailer
2. *displayOrder*
	* Display Sequence
3. *iconCode*
	* Font Awesome Icon name


----------

## Get Fullfillmet Info for Retailers

This function returns a delivery and pickup times and fees for retailers in an airport. This information is also provided in the /bydistance APIs

Two versions:

List of all retailers:
> /retailer/fullfillmentInfo/a/:apikey/e/:epoch/u/:sessionnToken/airportIataCode/:airportIataCode/toLocationId/:toLocationId

For a specific retailer:
> /retailer/fullfillmentInfo/a/:apikey/e/:epoch/u/:sessionnToken/airportIataCode/:airportIataCode/toLocationId/:toLocationId/retailerId/:retailerId


##### Parameters

1. Standard API Auth parameters (a, e, u)
2. *airportIataCode*
	* Airport IATA Code
3. *toLocationId*
	* Location Id of the user's desired delivery location; when no location is available, use 0
4. *retailerId*
	* Optional: the retailer unqiue id if needed for a specific retailer only; use in Menu / Retailer screen

##### JSON Response Parameters

1. *objectId*
	* Object ID of the Retailer from the Parse Retailer Class. This will be the key for the array elements.

	* fullfillmentInfo
		* This is a sub-array listing the following items:

		* i=internal, with a sub-array of:
			* ping=true/false
			* pingStatusDescription

		* d=delivery, with a sub-array of:
			* isAvailable
				* true/false if Delivery is available
			* fullfillmentFeesInCents
				* Provides the delivery fees in cents. The value should only be shown if isDeliveryAvailable flag is set to true
			* fullfillmentTimeEstimateInSeconds
				* Provides the delivery time estimate. For example, 30 mins. This value would represent 30*60 = 1800 seconds.
		* p=pickup, with a sub-array of:
			* isAvailable
				* true/false if Pickup is available
			* fullfillmentFeesInCents
				* Provides the pickup fees in cents. The value should only be shown if isPickupAvailable flag is set to true
			* fullfillmentTimeEstimateInSeconds
				* Provides the pickup time estimate. For example, 30 mins. This value would represent 30*60 = 1800 seconds.

----------

## QA Menu - Identify the retailerId

This endpoint returns the retailerId when provided the correct id (alphanumeric) and passcode (numeric)

> /retailer/qa/identify/a/:apikey/e/:epoch/u/:sessionToken/id/:id/passcode/:passcode

##### Parameters

1. Standard API Auth parameters (a, e, u=0)
2. *id*
	* Alphanumeric value representing a random username
3. *passcode*
	* 6 digit passcode

##### JSON Response Parameters

1. *retailerId*
	* Retailer id to be used for menu API calls


----------

## QA Retailer - Info

This function returns list of items available for a Retailer for the QA app.

> /retailer/qa/info/a/:apikey/e/:epoch/u/:sessionToken/retailerId/:retailerId

##### Parameters

1. Standard API Auth parameters (a, e, u=0)
2. *retailerId*
	* Unique Id of the Retailer

##### JSON Response Parameters

Same as /retailer/info


----------

## QA Menu - Item List

This function returns list of items available for a Retailer for the QA app.

> /retailer/qa/menu/a/:apikey/e/:epoch/u/:sessionToken/retailerId/:retailerId

##### Parameters

1. Standard API Auth parameters (a, e, u=0)
2. *retailerId*
	* Unique Id of the Retailer

##### JSON Response Parameters

Same as /retailer/menu


----------

## QA Menu - Item Modifiers

This function returns list of items available for a Retailer for the QA app.

> /retailer/qa/menu/a/:apikey/e/:epoch/u/:sessionToken/retailerId/:retailerId/itemId/:itemId

##### Parameters

1. Standard API Auth parameters (a, e, u=0)
2. *retailerId*
	* Unique Id of the Retailer

##### JSON Response Parameters

Same as /retailer/qa/menu/itemId


----------

## Retailer Explore by Airport

This function returns the list of retailers by Airport with their full information.

> /explore/a/:apikey/e/:epoch/u/:sessionToken/airportIataCode/:airportIataCode/retailerType/:retailerType

##### Parameters

1. Standard API Auth parameters (a, e, u)
2. *airportIataCode*
	* Airport Iata Code
3. *retailertype*
	* Retailer Type value; set it to 0 if you want all

##### App Cache Rules: 

*Cache for 15 mins. Allow for Gzipped response.*

##### JSON Response Parameters

The response will be an array with the following elements, listing different attributes of the Retailer.

1. *tiles*
	* Sub-array includes multiple lists that need to be displayed as a set of horizontal tiles. Each list provides an array of items or retailers.

	1. *attributes*
		* Sub-array that provides details on how the tile group should be displayed
			1. *type*
				* Two potential values, r = retailer, i = items, indicating the type of list this is
			2. *filterId*
				* (Optional) Listed for retailers. If provided, show the *See All* link on the top right of the tile list, linked to /retailer/filtered endpoint
			3. *title*
				* Title to be displayed
			4. *description*
				* Description to be displayed
			5. *titleIcon*
				* (Optional) Icon to be displayed on the left side of the title, if provided.
					1. *lib*
						* Name of the font library to be used. Allowed list includes
							1. *fa*
								* Font Awesome
							2. *ion*
								* Ionicons
							3. *typ*
								* Typicons
							4. *line*
								* Linecons
							5. *zurb*
								* Foundation Icon Fonts 3
					2. *code*
						* Icon code
			6. *listId*
				* Alphanumeric Id associated with this list. Use this for Mixpanel events.

	2. *list*
		* Sub-array with the items/retailers in the list
			1. *itemId*
				* Item Id (if this is an item list)
			2. *retailerId*
				* Retailer Id (this value is provided for both item and retailer lists)
			3. *displayName*
				* Name of the item/retailer to be displayed
			4. *imageURL*
				* Image to be displayed on the title
			5. *tooltip*
				* Sub-array of text that needs to be listed underneath the name of the item/retailer. Each array represent one string that should be displayed in a sequence separated by a (vertical middle aligned) dot
					1. *textDisplay*
						* Text to be displayed
					2. *hexColor*
						* Hex code of the color (without #), e.g. 000000 = black color
					3. *icon*
						* (Optional) Icon to be displayed on the left side of the text, if provided.
							1. *lib*
								* Name of the font library to be used. Allowed list includes
									1. *fa*
										* Font Awesome
									2. *ion*
										* Ionicons
									3. *typ*
										* Typicons
									4. *line*
										* Linecons
									5. *zurb*
										* Foundation Icon Fonts 3
							2. *code*
								* Icon code
			6. *highlight*
				* (Optional) Sub-array with details of the text to be displayed (when provided) as highlight
					1. *textDisplay*
						* Text to be displayed
					2. *hexColor*
						* Hex code of the color (without #), e.g. 000000 = black color
					3. *icon*
						* (Optional) Icon to be displayed on the left side of the text, if provided.
							1. *lib*
								* Name of the font library to be used. Allowed list includes
									1. *fa*
										* Font Awesome
									2. *ion*
										* Ionicons
									3. *typ*
										* Typicons
									4. *line*
										* Linecons
									5. *zurb*
										* Foundation Icon Fonts 3
							2. *code*
								* Icon code

2. *retailers*
	* List of all retailers. This list will only provide available retailers, and skips any that are offline. This sub-array will list items in the sequence they should be listed.
		1. *retailerName*
			* Name of the Retailer
		2. *description*
			* Description of the Retailer
		3. *uniqueId*
			* Unique Id associated with the Retailer; to be used for subsequent Retailer and Order transactions
		4. *imageHighlightURL*
			* (Optional) URL to the background image for the Retailer
		5. *imageLogoURL*
			* URL to the logo image for the Retailer
		6. *fulfillmentEstimates*
			* This will be a sub-array
				1. *deliveryEstimateInMins*
					* Numeric value in Mins
				2. *pickupEstimateInMins*
					* Numeric value in Mins
		7. *retailerFilters*
			* Filter attributes, to be used for the filter feature
				1. *retailerType*
					* This will be a sub-array	
						1. *retailerType*
							* Retailer Type, e.g. Food, Books & Mags, etc.
						2. *displayOrder*
							* Display Order
						3. *iconCode*
							* Icon code
				2. *retailerPriceCategory*
					* This will be a sub-array
						1. *retailerPriceCategory*
							* Retailer Price Category, e.g. 1, 2, 3, 4
						2. *retailerPriceCategorySign*
							* $, $$, $$$, $$$$
						3. *displayOrder*
							* Display Order
						4. *iconCode*
							* Icon code
						4. *iconLib*
							* Name of the icon library
				3. *retailerCategory*
					* This will be a sub-array
						1. *retailerCategory*
							* Retailer Category
						2. *displayOrder*
							* Display Order
						3. *iconCode*
							* Icon code
						4. *iconLib*
							* Name of the icon library
				4. *retailerFoodSeatingType*
					* This will be a sub-array
						1. *retailerFoodSeatingType*
							* Retailer Food Seating Type
						2. *displayOrder*
							* Display Order
						3. *iconCode*
							* Icon code
						4. *iconLib*
							* Name of the icon library
				5. *cuisineCategories*
					* This will be a sub-array
						1. *cuisineCategoryType*
							* Retailer's cuisine, e.g. American, Asian
						2. *displayOrder*
							* Display Order
						3. *iconCode*
							* Icon code
						4. *iconLib*
							* Name of the icon library
				6. *serviceAvailability*
					* This will be a sub-array
						1. *p*
							* Pickup Available = true/false
						2. *d*
							* Delivery available = true/false
				7. *location*
					* This will be a sub-array
						1. *terminal*
							* Airport Terminal where the Retailer is located, e.g. A, B, C
						2. *concourse*
							* Airport Concourse where the Retailer is located; blank if none listed
						3. *gate*
							* Airport Gate where the Retailer is located, e.g. 1, 2, 3
						4. *locationDisplayName*
							* Display name of where the retailer is located
						5. *locationId*
							* Object Id for TerminalGateMap location
						6. *geoPointLocation*
							* Geo Point location with a subarray with two elements: 
								1. *longitude*
									* Longitude
								2. *latitude*
									* Latitude
				8. *searchTags*
					* Sub-array, listing keywords that will result in this retailer to be shown in searches
				9. *isChain*
					* true / false
				10. *openAndCloseTimes*
					* Times for each day when the store will be open and closed. Airport timezone specific  time for the provided week and weekend day, e.g. 5:00 AM. Day 1 = Sunday.
						1. *openD1*
						2. *openD2*
						3. *openD3*
						4. *openD4*
						5. *openD5*
						6. *openD6*
						7. *openD7*
						8. *closeD1*
						9. *closeD2*
						10. *closeD3*
						11. *closeD4*
						12. *closeD5*
						13. *closeD6*
						14. *closeD7*

----------

## Retailer Filtered List

This function returns the list of retailers by Airport with their full information.

> /filtered/a/:apikey/e/:epoch/u/:sessionToken/filterId/:filterId

##### Parameters

1. Standard API Auth parameters (a, e, u)
2. *filterId*
	* Filter Id to be used

##### App Cache Rules: 

*Cache for 15 mins. Allow for Gzipped response.*

##### JSON Response Parameters

The response will be an array with the following elements, listing different attributes of the Retailer.

* List of all retailers. This list will only provide available retailers, and skips any that are offline. This sub-array will list items in the sequence they should be listed.
	1. *retailerName*
		* Name of the Retailer
	2. *description*
		* Description of the Retailer
	3. *uniqueId*
		* Unique Id associated with the Retailer; to be used for subsequent Retailer and Order transactions
	4. *imageHighlightURL*
		* (Optional) URL to the background image for the Retailer
	5. *imageLogoURL*
		* URL to the logo image for the Retailer
	6. *fulfillmentEstimates*
		* This will be a sub-array
			1. *deliveryEstimateInMins*
				* Numeric value in Mins
			2. *pickupEstimateInMins*
				* Numeric value in Mins
	7. *retailerFilters*
		* Filter attributes, to be used for the filter feature
			1. *retailerType*
				* This will be a sub-array	
					1. *retailerType*
						* Retailer Type, e.g. Food, Books & Mags, etc.
					2. *displayOrder*
						* Display Order
					3. *iconCode*
						* Icon code
			2. *retailerPriceCategory*
				* This will be a sub-array
					1. *retailerPriceCategory*
						* Retailer Price Category, e.g. 1, 2, 3, 4
					2. *retailerPriceCategorySign*
						* $, $$, $$$, $$$$
					3. *displayOrder*
						* Display Order
					4. *iconCode*
						* Icon code
					4. *iconLib*
						* Name of the icon library
			3. *retailerCategory*
				* This will be a sub-array
					1. *retailerCategory*
						* Retailer Category
					2. *displayOrder*
						* Display Order
					3. *iconCode*
						* Icon code
					4. *iconLib*
						* Name of the icon library
			4. *retailerFoodSeatingType*
				* This will be a sub-array
					1. *retailerFoodSeatingType*
						* Retailer Food Seating Type
					2. *displayOrder*
						* Display Order
					3. *iconCode*
						* Icon code
					4. *iconLib*
						* Name of the icon library
			5. *cuisineCategories*
				* This will be a sub-array
					1. *cuisineCategoryType*
						* Retailer's cuisine, e.g. American, Asian
					2. *displayOrder*
						* Display Order
					3. *iconCode*
						* Icon code
					4. *iconLib*
						* Name of the icon library
			6. *serviceAvailability*
				* This will be a sub-array
					1. *p*
						* Pickup Available = true/false
					2. *d*
						* Delivery available = true/false
			7. *location*
				* This will be a sub-array
					1. *terminal*
						* Airport Terminal where the Retailer is located, e.g. A, B, C
					2. *concourse*
						* Airport Concourse where the Retailer is located; blank if none listed
					3. *gate*
						* Airport Gate where the Retailer is located, e.g. 1, 2, 3
					4. *locationDisplayName*
						* Display name of where the retailer is located
					5. *locationId*
						* Object Id for TerminalGateMap location
					6. *geoPointLocation*
						* Geo Point location with a subarray with two elements: 
							1. *longitude*
								* Longitude
							2. *latitude*
								* Latitude
			8. *searchTags*
				* Sub-array, listing keywords that will result in this retailer to be shown in searches
			9. *isChain*
				* true / false
			10. *openAndCloseTimes*
				* Times for each day when the store will be open and closed. Airport timezone specific  time for the provided week and weekend day, e.g. 5:00 AM. Day 1 = Sunday.
					1. *openD1*
					2. *openD2*
					3. *openD3*
					4. *openD4*
					5. *openD5*
					6. *openD6*
					7. *openD7*
					8. *closeD1*
					9. *closeD2*
					10. *closeD3*
					11. *closeD4*
					12. *closeD5*
					13. *closeD6*
					14. *closeD7*

----------

## Featured Retailers Explore by Airport (for Homescreen)

This function returns featured retailers lists (one or many) of retailers by Airport.

> /featured/a/:apikey/e/:epoch/u/:sessionToken/airportIataCode/:airportIataCode/retailerType/:retailerType

##### Parameters

1. Standard API Auth parameters (a, e, u)
2. *airportIataCode*
	* Airport Iata Code

##### App Cache Rules: 

*Cache for 15 mins.*

##### JSON Response Parameters

The response will be an array with the following elements, listing one or more tiles (horizontal lists).

1. *tiles*
	* Sub-array includes multiple lists that need to be displayed as a set of horizontal tiles. Each list provides an array of retailers.

	1. *attributes*
		* Sub-array that provides details on how the tile group should be displayed
			1. *filterId*
				* (Optional) Listed for retailers. If provided, show the *See All* link on the top right of the tile list, linked to /retailer/filtered endpoint
			2. *title*
				* Title to be displayed
			3. *description*
				* Description to be displayed
			4. *titleIcon*
				* (Optional) Icon to be displayed on the left side of the title, if provided.
					1. *lib*
						* Name of the font library to be used. Allowed list includes
							1. *fa*
								* Font Awesome
							2. *ion*
								* Ionicons
							3. *typ*
								* Typicons
							4. *line*
								* Linecons
							5. *zurb*
								* Foundation Icon Fonts 3
					2. *code*
						* Icon code
			5. *listId*
				* Alphanumeric Id associated with this list. Use this for Mixpanel events.

	2. *list*
		* Sub-array with the retailers in the list
			1. *retailerId*
				* Retailer Id
			2. *displayName*
				* Name of the retailer to be displayed
			3. *imageURL*
				* Image to be displayed on the title
			4. *tooltip*
				* Sub-array of text that needs to be listed underneath the name of the retailer. Each array represent one string that should be displayed in a sequence separated by a (vertical middle aligned) dot
					1. *textDisplay*
						* Text to be displayed
					2. *hexColor*
						* Hex code of the color (without #), e.g. 000000 = black color
					3. *icon*
						* (Optional) Icon to be displayed on the left side of the text, if provided.
							1. *lib*
								* Name of the font library to be used. Allowed list includes
									1. *fa*
										* Font Awesome
									2. *ion*
										* Ionicons
									3. *typ*
										* Typicons
									4. *line*
										* Linecons
									5. *zurb*
										* Foundation Icon Fonts 3
							2. *code*
								* Icon code
			5. *highlight*
				* (Optional) Sub-array with details of the text to be displayed (when provided) as highlight
					1. *textDisplay*
						* Text to be displayed
					2. *hexColor*
						* Hex code of the color (without #), e.g. 000000 = black color
					3. *icon*
						* (Optional) Icon to be displayed on the left side of the text, if provided.
							1. *lib*
								* Name of the font library to be used. Allowed list includes
									1. *fa*
										* Font Awesome
									2. *ion*
										* Ionicons
									3. *typ*
										* Typicons
									4. *line*
										* Linecons
									5. *zurb*
										* Foundation Icon Fonts 3
							2. *code*
								* Icon code

----------

## Log events

This method should be called asyc'ly to log retailer and item events when user taps from Homescreen or Retailer List (skip events from Menu). This is in addition to any Mixpanel events.

> /log/a/:apikey/e/:epoch/u/:sessionToken/type/:type/id/:id

##### Parameters

1. Standard API Auth parameters (a, e, u)
2. *type*
	* Retailer, item
3. *id*
	* retailerUniqueId or itemUniqueId
4. *listId*
	* Id of the list under which this item/retailer was listed in

##### JSON Response Parameters

1. *logged* 
	* true/false

----------

## Curated  Lists

This endpoint provides curated lists plus full retailer list for the homescreen

> /curatedLists/a/:a/e/:e/u/:u/airportIataCode/:airportIataCode/deliveryLocationId/:deliveryLocationId/flightId/:flightId/requestedFullFillmentTimestamp/:requestedFullFillmentTimestamp

##### Parameters

1. Standard API Auth parameters (a, e, u)
2. *airportIataCode*
	* airportIataCode of the isReady airport
3. *deliveryLocationId*
	* Delivery location Id, use the following rules to identify the ID
		* Does user has a flight selected from an isReady airport?
			* Yes:
				* Does the flight have a departure/arrival gate populated for the isReady airport?
					* Yes:
						* Did the user override the flight departure gate to select a diff delivery location?
							* Yes:
								* Use the overridden location id as the Delivery Location
							* No:					
								* Use the flight departure/arrival gate location id as the Delivery Location
					* No:
						* Ask user to select Delivery location

			* No:
				* Ask user to select Delivery location
4. *flightId*
	* Select Flight Identifier, set to 0 if no flight is selected
5. *requestedFullFillmentTimestamp*
	* For immediate ordering, set to 0

##### JSON Response Parameters

1. *curatedLists* 
	* Sub-array of lists in the order they should be sequenced
		1. *id*
			* List Identifier
		2. *type*
			* retailer, item
		3. *name*
			* Name of List to display
		4. *description*
			* Description of the list to be shown (i) icon
		5. *cardType*
			* large, small
		6. *elementCount*
			* If elmentCount is greater than the elements, then show "SEE ALL" link
		7. *elements*
			* Sub-array of elements (retailers or items) to be shown
				1. *fullfillmentEstimateTextDisplay*
					* Text to display for the estimate. The backend will decide to show Pickup or Delivery estimate
				2. *ping*
					* true / false (to show closed or open status)
				3. *spotlight*
					* Text to list under the icon
				4. *spotlightIcon*
					* Icon
				4. *spotlightIconURL*
					* Icon URL
				5. *description*
					* Description
				6. *retailerId*
					* Retailer Unique Id
				7. *retailerName*
					* Retailer Name
				8. *retailerLocationDisplay*
					* Retailer's Location
				9. *retailerLogoImageURL*
					* Retailer Logo URL
				10. *imageURL*
					* Image URL that is to be displayed
				11. *itemId*
					* If type of list is ITEM, else this will be null
				12. *itemName*
					* If type of list is ITEM, else this will be null
				12. *itemPriceDisplay*
					* If type of list is ITEM, else this will be null
				14. *itemTags*
					* If type of list is ITEM, else this will be null
				15. *itemPrice*
					* Item Price in cents
				16. *itemImageThumbURL*
					* Thumbnail URL of the image
				17. *allowedThruSecurity*
					* Indicates if the item can be taken thru security
				18. *itemImageURL*
					* URL of the image
					
2. *retailers* 
	* Sub-array of lists all retailers in the sequence of display. The elements are same as /retailer/info


----------

## Curated Lists by Id

This endpoint provides ALL elements for a given curated list

> /curatedLists/a/:a/e/:e/u/:u/airportIataCode/:airportIataCode/deliveryLocationId/:deliveryLocationId/flightId/:flightId/requestedFullFillmentTimestamp/:requestedFullFillmentTimestamp/listId/:listId

##### Parameters

1. Standard API Auth parameters (a, e, u)
2. *airportIataCode*
	* airportIataCode of the isReady airport
3. *deliveryLocationId*
	* Delivery location Id, use same rules as the endpoint above
4. *flightId*
	* Select Flight Identifier
5. *requestedFullFillmentTimestamp*
	* For immediate ordering, set to 0
6. *listId*
	* List identifier to be expanded on

##### JSON Response Parameters

1. *curatedLists* 
	* Same as endpoint above




