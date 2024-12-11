### getting active orders
Logged user created for retailer (see sign Up) can get list of active orders
Active orders:
 - orders that have payment accepted, but was not confirmed by retailer
 - orders that was confirmed by retailer but not yet received by delivery or Consumer

> method: GET

> url: /tablet/order/getActiveOrders/a/:apikey/e/:epoch/u/:sessionToken/page/1/limit/1

##### Parameters
1. Url: Standard API Auth parameters (a, e, u) 
2. page - data are paginated, this parameter indicates which page we want to get (starts from 1)
3. limit - data are paginated, this parameter indicates how many orders are on one page


##### Expected result schema

1. ordersList - array of objects
    * orderId
    * orderSequenceId
    * orderStatusCode - int
    * orderStatusDisplay - string (to display in the app)
    * orderStatusCategoryCode - int
    * orderType
    * orderDateAndTime
    * retailerId
    * retailerName
    * retailerLocation
    * consumerName
    * mustPickupBy
    * numberOfItems
    * items   
        * array of order items:
            * retailerItemName
            * itemCategoryName
                * First category name
            * itemSecondCategoryName
                * Second category name (can be empty/null)
            * itemThirdCategoryName
                * Third category name (can be empty/null)
            * itemQuantity
            * options
                * array of selected options
                    * name _string_
                    * quantity _int_
                    * categoryName _string_
            * itemComments - special instructions

    * helpRequestPending (boolean)
    * discounts
        * array (can be empty) of discounts with each entry containing the following
            * discountTextDisplay
                * e.g. Airport Employee Discount
            * discountPercentageDisplay
                * e.g. 10% or empty (for older orders)
2. closeEarlyData 

    * isCloseEarlyRequested _bool_
        * this is set to *true* when retailer requested closing business, else it equals *false*
    * isClosedEarly _bool_
        * this is set to true when retailer has already closed business, else it equals *false*
3. pagination                                        
    * object - shown on paginated endpoints (page and amount in the input)
        * totalRecords - how many records are in total
        * interval - how many records are returned per page (the same like amount input)

        * totalPages - how many pages in total
        * currentPage - current page (the same like page input)

    
    
---


### getting past orders
Logged user created for retailer (see sign Up) can get list of past orders
Past orders:
- delivery orders that was already taken by delivery person
- picked up orders in which preparation time exceeded (should be taken by Consumer)

> method: GET

> url: /tablet/order/getPastOrders/a/:apikey/e/:epoch/u/:sessionToken/page/1/limit/1

##### Parameters
1. Url: Standard API Auth parameters (a, e, u) 
2. page - data are paginated, this parameter indicates which page we want to get (starts from 1)
3. limit - data are paginated, this parameter indicates how many orders are on one page


##### Expected result schema
1. ordersList - array of objects
    * orderId
    * orderSequenceId
    * orderStatusCode - int
    * orderStatusDisplay - string (to display in the app)
    * orderStatusCategoryCode - int
    * orderType
    * orderDateAndTime
    * retailerId
    * retailerName
    * retailerLocation
    * consumerName
    * mustPickupBy
    * numberOfItems
    * items   
        * array of order items:
            * retailerItemName
            * itemQuantity
            * itemCategoryName
                * First category name
            * itemSecondCategoryName
                * Second category name (can be empty/null)
            * itemThirdCategoryName
                * Third category name (can be empty/null)
            * options
                * array of selected options
                    * name _string_
                    * quantity _int_
                    * categoryName _string_
            * itemComments - special instructions
    * helpRequestPending (boolean)
    * discounts
        * array (can be empty) of discounts with each entry containing the following
            * discountTextDisplay
                * e.g. Airport Employee Discount
            * discountPercentageDisplay
                * e.g. 10% or empty (for older orders)
2. closeEarlyData 
    * isCloseEarlyRequested _bool_
        * this is set to *true* when retailer requested closing business, else it equals *false*
    * isClosedEarly _bool_
        * this is set to true when retailer has already closed business, else it equals *false*
3. pagination                                        
    * object - shown on paginated endpoints (page and amount in the input)
        * totalRecords - how many records are in total
        * interval - how many records are returned per page (the same like amount input)
        * totalPages - how many pages in total
        * currentPage - current page (the same like page input)            

    
---


### order confirmation
Logged user created for retailer (see sign Up) can confirm active order

> method: GET

> url: /tablet/order/confirm/a/:apikey/e/:epoch/u/:sessionToken/orderId/:orderId

##### Parameters
1. Url: Standard API Auth parameters (a, e, u) 
2. orderId - order Id (like "f8CwvjLNDW")

##### Expected result schema

1. order _changed order object_
    * orderId
    * orderSequenceId
    * orderStatusCode - int
    * orderStatusDisplay - string (to display in the app)
    * orderStatusCategoryCode - int
    * orderType
    * orderDateAndTime
    * retailerId
    * retailerName
    * retailerLocation
    * consumerName
    * mustPickupBy
    * numberOfItems
    * items   
        * array of order items:
            * retailerItemName
            * itemQuantity
            * options
                * array of selected options
                    * name _string_
                    * quantity _int_
                    * categoryName _string_
            * itemComments - special instructions
    * helpRequestPending (boolean)

---


### help request
Logged user created for retailer (see sign Up) can request help for active order

> method: POST

> url: /tablet/order/helpRequest/a/:apikey/e/:epoch/u/:sessionToken

##### Parameters
1. Url: Standard API Auth parameters (a, e, u) 
2. orderId - order Id (like "f8CwvjLNDW")
3. content - text message (help request)

##### Expected result schema
1. order _changed order object_
    * orderId
    * orderSequenceId
    * orderStatusCode - int
    * orderStatusDisplay - string (to display in the app)
    * orderStatusCategoryCode - int
    * orderType
    * orderDateAndTime
    * retailerId
    * retailerName
    * retailerLocation
    * consumerName
    * mustPickupBy
    * numberOfItems
    * items   
        * array of order items:
            * retailerItemName
            * itemQuantity
            * options
                * array of selected options
                    * name _string_
                    * quantity _int_
                    * categoryName _string_
            * itemComments - special instructions
    * helpRequestPending (boolean)
    
---