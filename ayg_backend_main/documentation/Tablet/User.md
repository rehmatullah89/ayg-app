### sign Up
User can not sign Up to the application,
User is created manually in parse database (User table),
then user id need to be placed in the "user" column in "RetailerPOSConfig" table.
One user can be added to multiple retailers

---

### sign In
User created for retailer (see sign Up) can login to the application.

> method: POST

> url: /tablet/user/signin/a/:apikey/e/:epoch/u/:sessionToken

##### Parameters
1. Url: Standard API Auth parameters (a, e, u) 
* Use a value of 0 since no sessionToken is available so far
2. Post parameters

    1. email
    * email
    2. password 
    * Encrypted password using "String in Motion" key and then URL encoded
    3. type 
    * use "t" as tablet user
    4. deviceArray
	* Details about the Device in an JSON array that is base64 enconded and then URL encoded:
		* appVersion
			* Version of the App
		* isIos
			* 1=true or 0=false, indicating if the device is iOS
		* isAndroid
			* 1=true or 0=false, indicating if the device is Android
		* deviceType
			* Device Type
		* deviceModel
			* Device Model
		* deviceOS
			* Device OS version
		* deviceId 
			* Device Identifier
		* isPushNotificationEnabled
			* 1=true or 0=false, indicating if Push Notifications are enabled
		* pushNotificationId
			* Push notification id from the device (such as Apple specific id)
 		* geoLatitude
 			* Geo Location Latitude
		* geoLongitude
			* Geo Location Longitude
		* timezoneFromUTCInSeconds
			* Number of seconds the user's timezone is away from UTC (e.g. if behind UTC then the value must be negative)
		* country
			* Country code that is compliant with https://en.wikipedia.org/wiki/ISO_3166-1_alpha-2
		* isOnWifi
			* 1=true or 0=false, indicating if the user is on Wifi


##### Expected result schema
1. sessionToken *string*
2. retailerShortInfo *object*
    1. retailerName *string*
    2. retailerLocationName *string*
    3. retailerLogoUrl *string*
    4. userType *int* _possible values: 2 for OPs team, 1 for retailer_
3. config *object*
    1. pingInterval *int*
    2. notificationSoundUrl *string*
    3. notificationVibrateUsage  *bool*
    4. batteryCheckInterval *bool* 
    
---

### sign Out
Logged retailer user can sign out from the application.

> method: POST

> url: /tablet/user/signout/a/:apikey/e/:epoch/u/:sessionToken

##### Parameters
1. Url: Standard API Auth parameters (a, e, u) 
2. Post parameters:
    1. password 
    * Encrypted password using "String in Motion" key and then URL encoded

##### Expected result schema

1. status *bool* - always true when success



---

### Close business request
User (only retailer, not for ops team) can request closing business earlier.
That means that from now on, till the end of the day, no new Orders will be accepted in the consumer app.
Also by the amount of seconds from response (f.e. 300 = 5 minutes) application will show that business is closed.

> method: GET

> url: /tablet/user/closeBusiness/a/:apikey/e/:epoch/u/:sessionToken


##### Expected result schema

1. numberOfSecondsToClose *int* - indicates number of seconds in which applications should be closed



---

### Reopen business when it is closed
User (only retailer, not for ops team) reopen business when it is closed

> method: GET

> url: /tablet/user/reopenBusiness/a/:apikey/e/:epoch/u/:sessionToken


##### Expected result schema

1. status *bool* - true when success, error otherwise