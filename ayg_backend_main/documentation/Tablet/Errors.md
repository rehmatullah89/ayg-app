### Description
When error appears in the application json with error schema is returned,

also HTTP code 400 or 401 is returned

##### Error schema

1. ordersList *string*
2. closeEarlyData *string*
3. pagination *string array* *returned only on dev envirement*                            

##### possible errors in the application

see ERRORCODES.md file in the main directory.

Errors that appears only in the retailer app starts with AS_5300