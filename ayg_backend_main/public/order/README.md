# Airport Sherpa API Usage Guide

## Order

### Keys

> See root README.MD


## Overview

This wrapper API provides Order-related functionality, such as create, close, status, cart operations. To view the full usage view the documentation in README.MD of the API root directory.

1. Basic Concepts for Order/Cart processing:

	1. User will have access to multiple Restaurants and their menus
	2. At a given time the user can order from ONLY 1 restaurant
	3. If the user adds items to cart from one Restaurant, then he may NOT add items from another Restaurant
	4. Every time a user chooses a Restaurant, an Order Id must be requested from the API; an orderID is specific to the user and the retailer (i.e. restaurant) combination
	
Review the below sections to understand the usage.

Note: In this documentation Cart will be referred by Order that is currently not placed.

----------

## Initiate Order / Cart

*What does this do?*

Provides a unique Order Id for the given User and his/her selected Restaurant Id. This Order id is synonymous with a Cart Id. If an open order already exists, this API will return that orderId. Hence, you should cache the orderId, or call this API before starting any Order related functions.

*How this works?*

Every time an item is to added to a Cart, an order id must be present. Remember: the Order id works only for a given Retailer Id.

Every time a user requests an item to be added to Cart, following operations must be performed:

	1. Check if you have an Order ID for the selected Retailer Id in your cache; this cache must be cleared every 15 minutes to ensure you are using the latest Order ID
	2. If you don't have one, call this API to get one.
	3. If you have one, but it is of a different Retailer, then:
		1. Call the following Close API
		2. Call this API to get a new Order Id
	4. If you have one, and is of the same Retailer, then:
		1. Use the cache OrderId
	
*When can this scenario occur?*

	1. For example, if the User selects Restaurant-A and adds 2 items to cart
	2. The App would cache the OrderId received from the API during the Order Create request
	3. But if the user then switches to Restaurant-B, then the App should request a new Order Id from the API for the Restaurant-B, and request the API close the first order


Returns: This function returns an order Id for the given user and selected Retailer.

> /initiate/apikey/:apikey/epoch/:epoch/sessionToken/:sessionToken/retailer/:retailerId

##### Parameters

1. Standard API Auth parameters (apikey, epoch, sessionToken)
2. *retailerId*
	* Retailer Object Id or Retailer Unique Id (default)

##### JSON Response Parameters

The response will be the following element:

1. *orderId*
	* Alpha-numeric order ID for the given user and retailer combination; This value may be set to 0. If so, you will get openOrderId value
2. *status*
	* For a new order status will 1, for a pending order (not paid status)
3. *openOrderId*
	* If filled, this means that an existing Open order exists with another retailer. Before initiating an open this retailer, you must close the other one or complete (Paid) its workflow
4. *openRetailerId*
	* This indicates the retailerId associated with the openOrderId 
	

----------

## Get Open Order Id

*What does this do?*

Provides an open order id (if one exists) without needing a retailer id. This is useful when the user goes to the Cart screen and when there is no cached order id (e.g. no items so far have been added to the cart or app crashed and the cache was cleared)

> /getOpenOrder/apikey/:apikey/epoch/:epoch/sessionToken/:sessionToken

##### Parameters

1. Standard API Auth parameters (apikey, epoch, sessionToken)

##### JSON Response Parameters

The response will be the following element:

1. *orderId*
	* Alpha-numeric order ID for the given user and retailer combination; This value may be set to 0 if no open order is found!
2. *retailerId*
	* This indicates the retailerId associated with the orderId  (to be used when getting /retailer/info)

----------

## Close Order / Cart

*What does this do?*

Allows you to close an Order / Cart when the user switches Restaurants.

Returns: Status variable with value 1. Also throws errors.

> Variation 1: (Using retailerId) - If used, only Status = 1 (meaning not ordered or paid) orders can be closed
> /close/apikey/:apikey/epoch/:epoch/sessionToken/:sessionToken/retailer/:retailerId

> Variation 2: (Using orderId) - If used, any open, ordered, or paid (but not confirmed) order can be forcifully be closed
> /close/apikey/:apikey/epoch/:epoch/sessionToken/:sessionToken/orderId/:orderID

##### Parameters

1. Standard API Auth parameters (apikey, epoch, sessionToken)
2. *retailerId* (optional)
	* Retailer Unique Id
3. *orderId* (optional)
	* ORder Object Id

##### JSON Response Parameters

The response will be the following element:

1. *reset*
	* 1=means order was closed
	
----------

## Add Items to Order / Cart

*What does this do?*

Allows you to add an item along with its Modifier options to cart

Returns: Status variable with value 1. Also throws errors.

> /addItem/apikey/:apikey/epoch/:epoch/sessionToken/:sessionToken

*Note*: This is a POST API

##### Parameters

1. Standard API Auth parameters (apikey, epoch, sessionToken)
2. *orderId*
	* Order Object Identifier received during Initiate API call
3. *orderItemId*
	* When adding items, set this to 0
	* When updating items (and modifier options), send this value, which will be returned when you original add an item; this is also returned in the Summary API call
4. *uniqueRetailerItemId*
	* Item Identifier provided the retailer/menu API Call
5. *itemQuantity*
	* Quantity of the Item to be added
6. *itemComment*
	* Special instructions that will be sent with the item to the Retailer
7. *options*
	* This is a **JSON encoded string with an Array** of the following structure:
		1. *id*
			* Option Identifier that is received from the retailer/menu or order/summary API call
		2. *quantity*
			* Quantity of the Option to be added; Default this to 1 (for adding) or 0 (for removing/deleting)

##### JSON Response Parameters

The response will be the following element:

1. *orderItemObjectId*
	* This is a unique Identifier of the Item added to the cart; this must be used while deleting items; This ID is also sent as part of Summary API

	
----------

## Add Items to Order / Cart v2

*What does this do?*

Same /addItem but instead does only one URL Decode for Options array

Returns: Status variable with value 1. Also throws errors.

> /add2Cart/apikey/:apikey/epoch/:epoch/sessionToken/:sessionToken


----------

## Delete Items from Order / Cart

*What does this do?*

Allows you to delete an item from the Cart.

> /deleteItem/apikey/:apikey/epoch/:epoch/sessionToken/:sessionToken/orderId/:orderId/orderItemId/:orderItemId

##### Parameters

1. Standard API Auth parameters (apikey, epoch, sessionToken)
2. *orderId*
	* Order Object Identifier received during Initiate API call
3. *orderItemId*
	* When adding items or pulling summary, this ID is sent, and must be used here

##### JSON Response Parameters

The response will be the following element:

1. *deleted*
	* 1=Deleted, 0=error occurred


----------

## Apply or Remove Coupon

*What does this do?*

Allows you to apply a coupon to the Order. After a successful coupon application, you must call the Order summary API again to refresh the screen. But you must cache the Comments (see response) so it can be shown on the refreshed screen.

> /coupon/apikey/:apikey/epoch/:epoch/sessionToken/:sessionToken/orderId/:orderId/code/:code

##### Parameters

1. Standard API Auth parameters (apikey, epoch, sessionToken)
2. *orderId*
	* Order Object Identifier received during Initiate API call
3. *code*
	* Alpha-numeric code of the coupon

##### JSON Response Parameters

The response will be the following elements:

1. *applied*
	* 1=Coupon Applied, 0=error occurred
2. *comments*
	* Explained the action taken. E.g. "It is an invalid or expired coupon." will be displayed applied=0. This should be shown to the user.

**Note**: To remove the last coupon, simply set the code = 0
	
----------

## Apply or Remove Airport Employee Discount

*What does this do?*

Allows you to apply or remove airport employee discount. After a successful discount application, you must call the Order summary API again to refresh the screen.

> /discount/airportEmployee/apikey/:apikey/epoch/:epoch/sessionToken/:sessionToken/orderId/:orderId/apply/:apply

##### Parameters

1. Standard API Auth parameters (apikey, epoch, sessionToken)
2. *orderId*
	* Order Object Identifier received during Initiate API call
3. *apply*
	* 1=apply, 0=remove

##### JSON Response Parameters

The response will be the following elements:

1. *applied*
	* 1=Applied, 0=Removed
	
----------

## Apply or Remove Military discount

*What does this do?*

Allows you to apply or remove military discount. After a successful discount application, you must call the Order summary API again to refresh the screen.

> /discount/military/apikey/:apikey/epoch/:epoch/sessionToken/:sessionToken/orderId/:orderId/apply/:apply

##### Parameters

1. Standard API Auth parameters (apikey, epoch, sessionToken)
2. *orderId*
	* Order Object Identifier received during Initiate API call
3. *apply*
	* 1=apply, 0=remove

##### JSON Response Parameters

The response will be the following elements:

1. *applied*
	* 1=Applied, 0=Removed

----------

## Apply or Remove Tip

*What does this do?*

Allows you to apply a tip in % to the Order. Certain Retailers may not allow tip, hence the response code must be checked before displaying to user. After tip is applied, call Summary to get a revised view of the totals.

> /tip/apikey/:apikey/epoch/:epoch/sessionToken/:sessionToken/orderId/:orderId/tipPct/:tipPct

##### Parameters

1. Standard API Auth parameters (apikey, epoch, sessionToken)
2. *orderId*
	* Order Object Identifier received during Initiate API call
3. *tipPct*
	* Number (whole numbers) representing the percentage of tip. E.g. for 10%, this will be 10

##### JSON Response Parameters

The response will be the following elements:

1. *applied*
	* 1=Tip Applied, 0=Tip not applied (retailer doesn't allow it)

**Note**: To remove the tip, simply set the tipPct = 0
**Also Note**: To check if the retailer allows Tips (e.g. before showing the option to the user) call retailer/tipCheck
	

----------

## Order Active Count

*What does this do?*

Provides a count of Active Orders to be shown on the Track Orders icon.

> /activecount/apikey/:apikey/epoch/:epoch/sessionToken/:sessionToken

##### Parameters

1. Standard API Auth parameters (apikey, epoch, sessionToken)

##### JSON Response Parameters

The response will be an array with the following elements:

1. *count*
	* Count of Active (In Progress or Scheduled) orders

----------

## Order Listing

*What does this do?*

Provides a list of in-progress and completed orders for the user. This does NOT show any orders that are not yet submitted.

> /list/apikey/:apikey/epoch/:epoch/sessionToken/:sessionToken/type/:type

##### Parameters

1. Standard API Auth parameters (apikey, epoch, sessionToken)
2. *type*
	* c=completed, a=active

##### JSON Response Parameters

The response will be an array with the following elements:

1. *orderId*
	* Order Object Identifier
4. *retailerId*
	* Unique Id of the Retailer for which the order was placed
5. *retailerName*
	* Name of the Retailer for which the order was placed
6. *retailerAirportIataCode*
	* 3 letter code of the Airport where the order was placed
7. *retailerLocationId*
	* Location Id of the retailer
8. *retailerImageLogo*
	* URL to the Logo of the Retailer
9. *orderStatus*
	* Status of the Order. This can be: IN-PROGRESS, PICKUP, and COMPLETED
10. *orderStatusCode*
	* Status code of the Order
11. *orderStatusDeliveryCode*
	* For delivery orders, this will show delivery status code of the Order
11. *orderStatusCategoryCode*
	* Status category code of the Order
12. *fullfillmentETATimestamp*
	* Time of Delivery/Pickup Unix timestamp
14. *fullfillmentETATimeDisplay*
	* Time of Delivery/Pickup formatted per the Airport timezone
15. *fullfillmentETATimezoneShort*
	* Short Timezone of the ETA timestamp
16. *fullfillmentType*
	* This will show a value of p=Pickup or d=Delivery; this value should be displayed on the Status bar in the Order Summary screen
17. *orderSubmitAirportTimeDisplay*
	* Order Submit Time: For In-progress orders, this will show Airport Local time, e.g. 9:30 PM; for Completed orders it will show the date, e.g. 2/1/16
18. *orderSubmitTimestampUTC*
	* Timestamp (UNIX in number of seconds) listing the Creation time for the Order
19. *etaRangeEstimateDisplay*
	* Shows fullfillment time in a mins range, e.g. 15 - 25 mins

----------

## Order Status

*What does this do?*

Responds with an array listing times of the key status of the Order. E.g. It will list when the Order was submitted, when it was paid, and ready for pickup.

Note: This API must be called every 2 minutes (and update the view) until the **status** with value 99 is not received


> /status/apikey/:apikey/epoch/:epoch/sessionToken/:sessionToken/orderId/:orderId

##### Parameters

1. Standard API Auth parameters (apikey, epoch, sessionToken)
2. *orderId*
	* Order Object Identifier received during Initiate API call

##### JSON Response Parameters

The response will be an array with the following sub-array elements:

1. *internal*
	1. *orderInternalStatus*
		* Same as /list
	2. *orderInternalStatusCode*
		* Same as /list
	3. *orderStatus*
		* Same as /list
	4. *orderStatusCode*
		* Same as /list
	5. *orderStatusCategoryCode*
		* Same as /list
	6. *orderStatusDeliveryCode*
		* Same as /list
	7. *etaTimestamp*
		* Timestamp for Order Pickup Ready or Delivery
	8. *etaTimezoneShort*
		* Short Timezone of the ETA timestamp
	9. *etaTimestampFormatted*
		* Formatted ETA timestamp
		* Formatted ETA timestamp
	10. *orderDeliveryLocationId*
		* Delivery Location id; blank for pickup orders
	11. *fromAirportIataCode*
		* Only provided for Delivery order, when flight info is available. From Airport IATA Code
	12. *toAirportIataCode*
		* Only provided for Delivery order, when flight info is available. To Airport IATA Code
	13. *airlineIataCode*
		* Only provided for Delivery order, when flight info is available. Airline IATA Code
	14. *flightNum*
		* Only provided for Delivery order, when flight info is available. Flight Num
	15. *lastknownDepartureTimestamp*
		* Only provided for Delivery order, when flight info is available. Last Known Departure Timestamp
	16. *lastknownDepartureTimestampDisplay*
		* Only provided for Delivery order, when flight info is available. Last Known Departure Timestamp that is display formatted
	17. *etaRangeEstimateDisplay*
		* Shows fullfillment time in a mins range, e.g. 15 - 25 mins

2. *status*
	1. *status*
		* Text status of the Order, e.g. Pickup
	2. *statusCode*
		* Code of the Order
	3. *statusCategoryCode*
		* Status category code of the Order
	3. *lastUpdateTimestampUTC*
		* UNIX timestamp (in UTC) of when the order reached this status.
	4. *lastUpdateAirportTime*
		* It is the Airport timezone formatted value for lastUpdateTimestampUTC

----------

## Order Status Full

*What does this do?*

Responds with an array listing times of the key status of the Order. E.g. It will list when the Order was submitted, when it was paid, and ready for pickup. This API provides the empty statuses for active order so that app can list the possible statuses.



> /statusFull/apikey/:apikey/epoch/:epoch/sessionToken/:sessionToken/orderId/:orderId

##### Parameters

1. Standard API Auth parameters (apikey, epoch, sessionToken)
2. *orderId*
	* Order Object Identifier received during Initiate API call

##### JSON Response Parameters

Same as /order/status, however, it will provide statuses with lastUpdateTimestampUTC = 0 when that status as has not yet reached.

----------

## Order Summary

*What does this do?*

Used for display Cart page or the Order History details page. Responds with details of all Items and their pricing information.

> /summary/apikey/:apikey/epoch/:epoch/sessionToken/:sessionToken/orderId/:orderId

##### Parameters

1. Standard API Auth parameters (apikey, epoch, sessionToken)
2. *orderId*
	* Order Object Identifier received during Initiate API call

##### JSON Response Parameters

The response will contain several sub-arrays.

1. *internal*
	* This sub-array will contain the following elements listing basic information about the order.
		1. *retailerUniqueId*
			* Unique Id representing the Retailer
		2. *orderId*
			* Order Object Identifier (it will be the same as sent by the API request)
		3. *orderIdDisplay*
			* Numerical Order Id to be used for display to User
		4. *orderStatusCode*
			* Order Status Code
		5. *orderStatusCategoryCode*
			* Status category code of the Order
		5. *orderDate*
			* Date of Order formatted per the Airport timezone
		6. *fullfillmentETATimestamp*
			* Time of Delivery/Pickup Unix timestamp
		7. *fullfillmentETATimeDisplay*
			* Time of Delivery/Pickup formatted per the Airport timezone
		8. *fullfillmentETATimezoneShort*
			* Short Timezone of the fullfillment ETA timestamp
		9. *fullfillmentType*
			* This will show a value of p=Pickup or d=Delivery; this value should be displayed on the Status bar in the Order Summary screen
		10. *orderSubmitAirportTimeDisplay*
			* Order Submit Time: For In-progress orders, this will show Airport Local time, e.g. 9:30 PM; for Completed orders it will show the date, e.g. 2/1/16
		11. *orderSubmitTimestampUTC*
			* Timestamp (UNIX in number of seconds) listing the Creation time for the Order
		12. *retailerName*
			* Name of the Retailer for which the order was placed
		14. *retailerAirportIataCode*
			* 3 letter code of the Airport where the order was placed
		15. *retailerImageLogo*
			* URL to the Logo of the Retailer
		16. *orderStatusDisplay*
			* Order Status for Display to user; if empty it means, order was not submiited
		17. *retailerLocation*
			* Location of where the retailer is
		18. *deliveryLocation*
			* Populated only for delivery orders; location of the delivery
		19. *orderNotAllowedThruSecurity*
			* true=Order not allowed through security / false = allowed through security. If true, indicates at least one item in the order is NOT allowed through security
		20. *deliveryName*
			* Name of the delivery that is assigned to the order, if it is pickup order or delivery is not assigned - empty string
            
2. *items*
	* This sub-array will contain further *sub-arrays* with the following elements describing the item in the Cart 
		1. *itemOrderId*
			* Unique Id representing the Item in the Cart
		2. *itemId*
			* Unique Id representing the Item Id associated with the retailer
		3. *itemQuantity*
			* Quantity of the Item added
		4. *itemComment*
			* Special Instructions comments added
		5. *itemQuantity*
			* Quantity of the Item added
		6. *itemName*
			* Name of the Item
		7. *itemDescription*
			* Item Description
		8. *itemImageURL*
			* If an image of the item exists, a URL pointing to it will be returned; else a blank value would be returned
		8. *itemImageThumbURL*
			* If a thumbnail image of the item exists, a URL pointing to it will be returned; else a blank value would be returned
		9. *itemPrice*
			* Price in cents (of One Item, i.e. per unit) WITHOUT a $ notation
		10. *itemPriceDisplay*
			* Price (string) in dollars (of One Item, i.e. per unit) with a $ notation; this value should be displayed to the user
		11. *itemTotalPriceDisplay*
			* Price (string) in dollars (of total number of items of this type, i.e. quantity adjusted) with a $ notation; this value should be displayed to the user
		12. *itemTotalPrice*
			* Price (in cents) of total number of items of this type, i.e. quantity adjusted
		13. *itemTotalPriceWithModifiers*
			* Price (in cents) of total number of items of this type, i.e. quantity adjusted + total price of modifiers underneath this item
		14. *itemTotalPriceWithModifiersDisplay*
			* Price (in dollars with a $ notation) of total number of items of this type, i.e. quantity adjusted; i.e. Item sub total
		15. *allowedThruSecurity*
			* true = Item allowed through security, false = Item NOT allowed through security
		16. *restrictOrderTimeInSecsStart*
			* See /retailer/menu for details
		17. *restrictOrderTimeInSecsEnd*
			* See /retailer/menu for details
		18. *options*
			* This will list further sub-arrays, each listing the Options selected for the item
				1. *optionId*
					* Option Identifier associated with the Item
				2. *optionName*
					* Option Name to be displayed (e.g. Tomato, Cheese)
				3. *optionQuantity*
					* Option Quantity added
				4. *pricePerUnit*
					* Price in cents (for One Option, i.e. per unit) WITHOUT $ notation for the Option per Quantity
				5. *pricePerUnitDisplay*
					* Price (in dollars of One Option, i.e. per unit) with $ notation for the Option per Quantity
				6. *priceTotalDisplay*
					* Price (in dollars of total number of options of this type, i.e. quantity adjusted) with $ notation for the Option per Quantity
				7. *priceTotal*
					* Price (in cents) of total number of options of this type, i.e. quantity adjusted
				8. *modifierName*
					* Modifier Name to be displayed (e.g. Toppings)
3. *totals*
	* This sub-array contains the following elements listing the Total dollar values for the Cart / Order
		1. *CouponDisplay*
			* Optional. It will be shown if there is a coupon applied to the Order. If so this will contain a price (string) in dollars with a $ notation; this value should be displayed to the user with a negative sign. E.g. value sent could be $1.80, therefore should be displayed as ($1.80) in red color indicating negative value.
		2. *Coupon*
			* Optional. It will be shown if there is a coupon applied to the Order. If so this will contain a number in cents identifying the coupon savings for the user; this should be used for any calculations
		3. *CouponCodeApplied*
			* Optional. It will be shown if there is a coupon applied to the Order. This will show the code that was applied.
		4. *AirportSherpaFeeDisplay*
			* Lists any fee applied by Airport Sherpa to the order. It will contain a price (string) in dollars with a $ notation; this value should be displayed to the user.
		5. *AirportSherpaFee*
			* Lists any fee applied by Airport Sherpa to the order
		6. *AirEmployeeDiscountDisplay*
			* Lists any discounts applied for an apporved Airport Employee; this value should be displayed to the user. Show this section and value if AirEmployeeDiscount (next field) is greater than 0.
		7. *AirEmployeeDiscount*
			* Lists any discounts applied for an apporved Airport Employee (in cents)
		8. *TaxesDisplay*
			* Taxes applied (string) in dollars with a $ notation; this value should be displayed to the user
		9. *Taxes*
			* Taxes applied (number) in cents; this should be used for any calculations.
		10. *TipsDisplay*
			* Total tip, in dollars with a $ notation; this value should be displayed to the user.
		11. *Tips*
			* Total tip, (number) in cents; this should be used for any calculations.
		12. *TipsPCT*
			* It will be shown if there is a tip applied to the Order. It will list the percentage of tip applied. E.g. 15%
		13. *TotalDisplay*
			* Total due, coupon value and tax adjusted, in dollars with a $ notation; this value should be displayed to the user.
		14. *Total*
			* Total, coupon value and tax adjusted, (number) in cents; this should be used for any calculations.
4. *payment*
	* This sub-array contains the following elements listing the information of the Payment Method used
		1. *paymentType*
			* Type of payment method used, e.g. CreditCard
		2. *paymentTypeName*
			* Name of the payment method used, e.g. MasterCard
		3. *paymentTypeId*
			* For credit cards its the last 4 digits of the card used
		4. *paymentTypeIconURL*
			* URL to Icon used for the Payment Type used, e.g. for MasterCard, it will be the master card icon


----------

## Order Summary v.2

*What does this do?*

New Version - Used for display Cart page or the Order History details page. Responds with details of all Items and their pricing information.

> /summarize/apikey/:apikey/epoch/:epoch/sessionToken/:sessionToken/orderId/:orderId

##### Parameters

1. Standard API Auth parameters (apikey, epoch, sessionToken)
2. *orderId*
	* Order Object Identifier received during Initiate API call

##### JSON Response Parameters

The response will contain several sub-arrays.

1. *internal* - Same as before
	* This sub-array will contain the following elements listing basic information about the order.
		1. *retailerUniqueId*
			* Unique Id representing the Retailer
		2. *orderId*
			* Order Object Identifier (it will be the same as sent by the API request)
		3. *orderIdDisplay*
			* Numerical Order Id to be used for display to User
		4. *orderStatusCode*
			* Order Status Code
		5. *orderStatusCategoryCode*
			* Status category code of the Order
			* 100 = Order is in Scheduled or Submitted status
			* 200 = Order has been pushed to retailer or is being prepared by retailer
			* 400 = Order is completed
			* 600 = Order has been canceled
			* 900 = Order needs customer input
		5. *orderDate*
			* Date of Order formatted per the Airport timezone
		6. *fullfillmentETATimestamp*
			* Time of Delivery/Pickup Unix timestamp
		7. *fullfillmentETATimeDisplay*
			* Time of Delivery/Pickup formatted per the Airport timezone
		8. *fullfillmentETATimezoneShort*
			* Short Timezone of the fullfillment ETA timestamp
		9. *fullfillmentType*
			* This will show a value of p=Pickup or d=Delivery; this value should be displayed on the Status bar in the Order Summary screen
		10. *orderSubmitAirportTimeDisplay*
			* Order Submit Time: For In-progress orders, this will show Airport Local time, e.g. 9:30 PM; for Completed orders it will show the date, e.g. 2/1/16
		11. *orderSubmitTimestampUTC*
			* Timestamp (UNIX in number of seconds) listing the Creation time for the Order
		12. *retailerName*
			* Name of the Retailer for which the order was placed
		14. *retailerAirportIataCode*
			* 3 letter code of the Airport where the order was placed
		15. *retailerImageLogo*
			* URL to the Logo of the Retailer
		16. *orderStatusDisplay*
			* Order Status for Display to user; if empty it means, order was not submiited
		17. *retailerLocation*
			* Location of where the retailer is
		18. *deliveryLocation*
			* Populated only for delivery orders; location of the delivery
		19. *orderNotAllowedThruSecurity*
			* true=Order not allowed through security / false = allowed through security. If true, indicates at least one item in the order is NOT allowed through security
		20. *deliveryName*
			* Name of the delivery that is assigned to the order, if it is pickup order or delivery is not assigned - empty string
		21. *couponCodeApplied*
			* Optional. It will be shown if there is a coupon applied to the Order. This will show the code that was applied.
		22. *couponAppliedByDefault*
			* True/False. If set to try, the use should NOT be allowed to remove the coupon as it was applied by default (e.g. a signup promo available for first order only)
		23. *cartUpdateMessage*
			* Populated with a message if an update is available for the cart. E.g. an item in the cart was removed.
            
2. *items* - Same as before
	* This sub-array will contain further *sub-arrays* with the following elements describing the item in the Cart 
		1. *itemOrderId*
			* Unique Id representing the Item in the Cart
		2. *itemId*
			* Unique Id representing the Item Id associated with the retailer
		3. *itemQuantity*
			* Quantity of the Item added
		4. *itemComment*
			* Special Instructions comments added
		5. *itemQuantity*
			* Quantity of the Item added
		6. *itemName*
			* Name of the Item
		7. *itemDescription*
			* Item Description
		8. *itemImageURL*
			* If an image of the item exists, a URL pointing to it will be returned; else a blank value would be returned
		8. *itemImageThumbURL*
			* If a thumbnail image of the item exists, a URL pointing to it will be returned; else a blank value would be returned
		9. *itemPrice*
			* Price in cents (of One Item, i.e. per unit) WITHOUT a $ notation
		10. *itemPriceDisplay*
			* Price (string) in dollars (of One Item, i.e. per unit) with a $ notation; this value should be displayed to the user
		11. *itemTotalPriceDisplay*
			* Price (string) in dollars (of total number of items of this type, i.e. quantity adjusted) with a $ notation; this value should be displayed to the user
		12. *itemTotalPrice*
			* Price (in cents) of total number of items of this type, i.e. quantity adjusted
		13. *itemTotalPriceWithModifiers*
			* Price (in cents) of total number of items of this type, i.e. quantity adjusted + total price of modifiers underneath this item
		14. *itemTotalPriceWithModifiersDisplay*
			* Price (in dollars with a $ notation) of total number of items of this type, i.e. quantity adjusted; i.e. Item sub total
		15. *allowedThruSecurity*
			* true = Item allowed through security, false = Item NOT allowed through security
		16. *restrictOrderTimeInSecsStart*
			* See /retailer/menu for details
		17. *restrictOrderTimeInSecsEnd*
			* See /retailer/menu for details
		18. *options*
			* This will list further sub-arrays, each listing the Options selected for the item
				1. *optionId*
					* Option Identifier associated with the Item
				2. *optionName*
					* Option Name to be displayed (e.g. Tomato, Cheese)
				3. *optionQuantity*
					* Option Quantity added
				4. *pricePerUnit*
					* Price in cents (for One Option, i.e. per unit) WITHOUT $ notation for the Option per Quantity
				5. *pricePerUnitDisplay*
					* Price (in dollars of One Option, i.e. per unit) with $ notation for the Option per Quantity
				6. *priceTotalDisplay*
					* Price (in dollars of total number of options of this type, i.e. quantity adjusted) with $ notation for the Option per Quantity
				7. *priceTotal*
					* Price (in cents) of total number of options of this type, i.e. quantity adjusted
				8. *modifierName*
					* Modifier Name to be displayed (e.g. Toppings)

5. *payment* - Same as before
	* This sub-array contains the following elements listing the information of the Payment Method used
		1. *paymentType*
			* Type of payment method used, e.g. CreditCard
		2. *paymentTypeName*
			* Name of the payment method used, e.g. MasterCard
		3. *paymentTypeId*
			* For credit cards its the last 4 digits of the card used
		4. *paymentTypeIconURL*
			* URL to Icon used for the Payment Type used, e.g. for MasterCard, it will be the master card icon

4. *data* - New
	* This array lists all the data indexes of all those listed in the sequence sub-array. Each sub-array will include the following items:

		1. *textDisplay*
			* Value to be displayed as the column name
		2. *categoryDisplay*
			* If provided, categorize the rows under this name, e.g. "Fees"
		3. *valueDisplay*
			* Value to be displayed for this column. NOTE: If this value is null or blank, then don't display this row
		4. *displaySequence*
			* Sequence in which this rows should be shown
		5. *infoTitleDisplay*
			* Title of the info display popup
		6. *infoDisplay*
			* If this is provided, show an (i) icon next to the textDisplay, which when tapped will open a popup with the infoTitleDisplay value as the title and a Ok button. This sub-array (one element per line) should be shown in sequence and centered aligned
			1. *infoTitleDisplay*
				* Text of the popup
		7. *textDisplayHexColor*
			* If provided set this HEX code color for the Text of column. If value is blank, use default color.
		8. *categoryDisplayHexColor*
			* If provided set this HEX code color for the Category of column. If value is blank, use default color.
		9. *valueDisplayHexColor*
				* If provided set this HEX code color for the value of column. If value is blank, use default color.
		10. *totalIndicator*
			* Indicates if the line item is for Total, therefore must be shown in bold with its special formaating

5. *discounts* - New
	* This array lists all the discount toggles that should be displayed. The same structure as *data* element is used, with one addition fieldDisplayRules

		1. *fieldDisplayRules*
			1. *eligible*
				* If true, show the row, else hide
			2. *applied*
				* If true, show toggle as enabled
			3. *discountToggleEndpoint*
				* The name of the endpoint to call for the toggle, /order/discount/[discountToggleEndpoint]/...

----------

## Order Cart Item Count

*What does this do?*

Provides count of items in the cart.

> /count/apikey/:apikey/epoch/:epoch/sessionToken/:sessionToken/orderId/:orderId

##### Parameters

1. Standard API Auth parameters (apikey, epoch, sessionToken)
2. *orderId*
	* Order Object Identifier received during Initiate API call

##### JSON Response Parameters

The response will be an array with the following elements:

1. *count*
	* Numerical value listing items in the cart


----------

## Order Submit (Part 1 - Get fullfillment quote)

*What does this do?*

Before placing the order, gets fullfillment information

> /getFullfillmentQuote/apikey/:apikey/epoch/:epoch/sessionToken/:sessionToken/orderId/:orderId/deliveryLocation/:deliveryLocation/requestedFullFillmentTimestamp:/requestedFullFillmentTimestamp

*Note*: This is a POST API

##### Parameters

1. Standard API Auth parameters (apikey, epoch, sessionToken)
2. *orderId*
	* Order Object Identifier received during Initiate API call
3. *deliveryLocation*
	* Location Id from TerminalGateMap; if not requesting delivery, set this to 0
4. *requestedFullFillmentTimestamp*
	* Set to 0; In future this will be used for scheduling ordering

##### JSON Response Parameters

The response will be an array with the following elements:

* d=delivery, with a sub-array of:
	* isAvailable
		* true/false if Delivery is available for this retailer
	* isNotAvailableReason
		* When isAvailable=false, this shows the error message you should show
	* isAvailableAtDeliveryLocation
		* true/false if Delivery is available for the provided delivery location; this may be true/false regardless of what the value is for isAvailable
	* fullfillmentFeesInCents
		* Provides the delivery fees in cents. The value should only be shown if isDeliveryAvailable flag is set to true
	* fullfillmentFeesDisplay
		* Formatted for display
	* fullfillmentTimeEstimateInSeconds (deprecated)
		* Provides the delivery time estimate. For example, 30 mins. This value would represent 30*60 = 1800 seconds.
	* fullfillmentTimeRangeEstimateDisplay
		* Display text for the estimate, e.g. 15 - 20 mins
	* totalDisplay
		* Total due (with fullfillmentFee added), coupon value and tax adjusted, in dollars with a $ notation; this value should be displayed to the user.
	* totalInCents
		* Total, coupon value and tax adjusted, (number) in cents; this should be used for any calculations.
	* disclaimerText
		* Shows any disclaimer text to be shown under the Delivery tab. This can be empty.
	* requiresDeliveryInstructions
		* If true, customer must fill in instructions. This will be true for locations such as Pre-security A, E, etc.
* p=pickup, with a sub-array of:
	* isAvailable
		* true/false if Pickup is available
	* isNotAvailableReason
		* When isAvailable=false, this shows the error message you should show
	* fullfillmentFeesInCents
		* Provides the pickup fees in cents. The value should only be shown if isPickupAvailable flag is set to true
	* fullfillmentFeesDisplay
		* Formatted for display
	* fullfillmentTimeEstimateInSeconds (deprecated)
		* Provides the pickup time estimate. For example, 30 mins. This value would represent 30*60 = 1800 seconds.
	* fullfillmentTimeRangeEstimateDisplay
		* Display text for the estimate, e.g. 15 - 20 mins
	* totalDisplay
		* Total due (with fullfillmentFee added), coupon value and tax adjusted, in dollars with a $ notation; this value should be displayed to the user.
	* totalInCents
		* Total, coupon value and tax adjusted, (number) in cents; this should be used for any calculations.
	* disclaimerText
		* Shows any disclaimer text to be shown under the Pickup tab. This can be empty.


----------

## Order Submit (Part 2 - Validating Order)

*What does this do?*

Validates the order for various conditions before placing the order

> /submit/validation/apikey/:apikey/epoch/:epoch/sessionToken/:sessionToken

*Note*: This is a POST API

##### Parameters

Same as /submit (Part 3)


##### JSON Response Parameters

The response will be an array with the following elements:

1. *validation*
	* true=No alerts, proceed with Part#3, false=there are alerts to display
2. *alerts*
	* This is a sub-array with one or more alerts, all of which should be shown individually
	1. *alert_code*
		* The code that identifies which image to show Mr. Sherpa. One or more messages will come together. For those messages that are Warnings only, show them one by one, with a Continue button to allow user to progress thru, with the last message offering "Place Order" button. However, if there are Error messages, then do not show Place Order button, instead show button to take user to cart, with "Review Cart" button
			* no_upcoming_flights - There no upcoming flights
				* Warning only: User can continue to order
			* delivery_after_boarding - Delivery will be completed after Boarding starts
				* Warning only: User can continue to order
			* pickup_after_boarding - Pickup + Walk time to Gate will be completed after Boarding starts
				* Warning only: User can continue to order
			* walk_to_depgate_thru_security_with_unallowed_items - After Delivery, user can't walk to their departure gate with unllowed items, as the walk from Delivery Location to Departure Gate requires going through security
				* Warning only: User can continue to order
			* pickup_thru_security_with_unallowed_items - Pickup warning as order contains unallowed items through security
				* Warning only: User can continue to order	
			* delivery_thru_security_with_unallowed_items - Delivery can't be completed as order contains unallowed items through security
				* Error: User can NOT continue to order and must remove offending items; give user option to return to cart
			* items_not_available_at_this_time - Items in order that cannot be ordered this time
				* Error: User can NOT continue to order and must remove offending items; give user option to return to cart
			* not_a_valid_fullfillmenttype_of_coupon - Not a <delivery/pickup> coupon code!
                * Warning only: Show warning to user that coupon used is not a valid for different type of fullfillment.
            							
	2. *alert_title*
		* Title of message to display to user
	3. *alert_message*
		* Message to display to user
	4. *allow_user_to_continue*
		true=All user to continue, false=user must return to the cart to update

----------

## Order Submit (Part 3 - Final submission)

*What does this do?*

Places the order after the payment has been processed

> /submit/apikey/:apikey/epoch/:epoch/sessionToken/:sessionToken

*Note*: This is a POST API

##### Parameters

1. Standard API Auth parameters (apikey, epoch, sessionToken)
2. *orderId*
	* Order Object Identifier received during Initiate API call
3. *fullfillmentType*
	* p=pickup, d=delivery
4. *deliveryLocation*
	* Location Id from TerminalGateMap of where the order needs to be deliverd; for pickup orders provide a value of 0
5. *deliveryInstructions*
	* Instructions for the Sherpa; for pickup orders set a value of 0
6. *requestedFullFillmentTimestamp*
	* To be used when scheduling orders; for immediate orders set to a value of 0
7. *paymentToken*
	* Token of Payment Method to be charged

##### JSON Response Parameters

The response will be an array with the following elements:

1. *ordered*
	* 1=Placed, 0=Error occurred
2. *orderId*
	* Order Id of the new order; will be the same as submitted
3. *fullfillmentTypeDisplay*
	* Shows one of the two values: p=Pickup or d=Delivery
4. *fullfillmentETATimestamp*
	* Timestamp (Number of seconds since 1970 - UNIX Timestamp); This needs to be converted to Local Airport's timezone (available in the Airports Class in Parse)
5. *fullfillmentETATimeDisplay*
	* Pre-formatted Time per the local Airport timezone, e.g. 9:30 PM
6. *fullfillmentLocation*
	* Location Object Id from Terminal Gate Map, identifying the location of the pickup / delivery
7. *fullfillmentTimeRangeEstimateDisplay*
	* Display the ETA in mins range, e.g. 15 - 25 mins

----------

## Order Help

*What does this do?*

Submits the help form for an order

> /help/apikey/:apikey/epoch/:epoch/sessionToken/:sessionToken/orderId/:orderId/comments/:comments

*Note*: This is a POST API

##### Parameters

1. Standard API Auth parameters (apikey, epoch, sessionToken)
2. *orderId*
	* Order Object Identifier received during Initiate API call
3. *comments*
	* Comments for the help

##### JSON Response Parameters

The response will be an array with the following elements:

* saved
	* 1=saved;0=failed
	
	
----------

## Order Rating - rate

> /rate/a/:apikey/e/:epoch/u/:sessionToken

### Notes:

1. Submission method is POST

##### Parameters

1. Standard API Auth parameters (a, e, u)

Following parameters are to be sent via POST

2. overAllRating *int*
    * rating value, can be 1,2,3,4,5 or -1 when user want to skip rating
3. feedback *string*
    * comment
4. orderId *string*
    * orderId

##### JSON Response Parameters

1. status *bool*
    * true when success, other case error will be returned


----------

## Order Rating - get last rating for an order

> '/getLastRating/a/:apikey/e/:epoch/u/:sessionToken/orderId/:orderId

### Notes:

1. Submission method is GET

##### Parameters

1. Standard API Auth parameters (a, e, u)

2. orderId *string*
    Id of the order that we want to get last rating
    
##### JSON Response Parameters

1. rating *int*
    * rating value, can be -1,1,2,3,4,5

2. ratingCreatedAt *DateTime*
    * creating DateTime

3. feedback *string*
    * comment added by user

4. orderFullfillmentType *string*
    * order Fullfillment Type, can be "d" or "p"

5. deliveryFirstName *string*
    * name of the delivery connected to the order


###### When there was no rating before response will be with "empty" response which is
1. rating = 0
2. ratingCreatedAt = null
3. feedback = null
4. orderFullfillmentType = null
5. deliveryFirstName = null

    

