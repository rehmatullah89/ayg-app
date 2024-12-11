# Airport Sherpa API Usage Guide

## Internal Operations

### Keys

> See root README.MD


----------
## Contact (POST)

Without Allow Contact flag
> /contact/a/:apikey/e/:epoch/u/:userid/deviceId/:deviceId/comments/:comments

With Allow Contact flag
> /contact/a/:apikey/e/:epoch/u/:userid/deviceId/:deviceId/comments/:comments/allowContact/:allowContact

With Allow Contact flag and with Contact Name and Email (this doesn't require user to be logged in)
> /contact/a/:apikey/e/:epoch/u/:userid/deviceId/:deviceId/comments/:comments/allowContact/:allowContact/contactName/:contactName/contactEmail/:contactEmail

##### Parameters

1. Standard API Auth parameters (a, e, u)
2. *deviceId*
	* Device Id
3. *comments*
	* URL encoded Comments from the User
4. *allowContact*
	* 1=Yes, 0=No
5. *contactName*
	* Name of the user
6. *contactEmail*
	* Email address of the user
	
##### JSON Response Parameters

The response will be the following element:

1. *saved*
	* 1=Saved, 0=Not Saved

----------

## Bug report (POST)

> /bug/a/:apikey/e/:epoch/u/:userid

*What does this do?*

Saves a bug report

*Note* This request requires you send a POST (unlike others as GET) request so the request can accommodate the Image being sent. You MUST use the URL path that includes apikey, epoch and userid in the URL and not through POST.

##### Parameters

1. Standard API Auth parameters (a, e, u)
2. *deviceId*
	* Device Id
3. *deviceType*
	* Device Type
4. *appVersion*
	* App version
5. *buildVersion*
	* App build version
6. *iOSVersion*
	* iOS version on the device
7. *bugSeverity*
	* This must be a number with values 1,2,3,4 (1 being critical)
8. *bugCategory*
	* String text of the Bug category (url encoded)
9. *description*
	* Description from the user
10. *screenshot*
	* Base64 encoded binary image file. Only PNG files are accepted.

##### JSON Response Parameters

The response will be the following element:

1. *saved*
	* 1=Saved, 0=Not Saved

	
----------

## Get Minimum App version requirement

*What does this do?*

Provides the minimum App version required for the API to work. If lower than this value, the user should be asked to upgrade with a blocker screen

##### Parameters

1. Standard API Auth parameters (a, e, u)

##### JSON Response Parameters

The response will be the following element:

1. *minAppVersionReqForAPI*
	* Value of the version required, e.g. 0.1.0 (this will be a string with two periods)
