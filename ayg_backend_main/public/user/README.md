# Airport Sherpa API Usage Guide

## User

### Keys

> See root README.MD

----------

## Sign up

*What does this do?*

Creates a new account for the user.

The sign up process is a 4 step process:

1. /signup/usernameCheck
2. /signup
3. /addPhone
4. /verifyPhone

Notes:

* Until a Phone number is not active on the account the user will not be allowed to login
* All users are automatially signed up for Beta after sign up
* Following error codes must be handled approrpriately whenever *any* API is called:
	* AS_9001 - API is down
	* AS_9002 - App must be upgraded to a listed version
	* AS_9003 - Metadata must be cleared and user should be logged out
	* AS_015 - Session is no longer valid; force user to see the login screen (no error display required)
	* AS_024 - No verified phones on file, take user to add phone screen
	* AS_025 - Beta account not yet approved, show message
	* AS_026 - Account is not active, show message and then take user to login screen
	* AS_028 - User account has been locked, show message, then take user to login screen

* a, e, u - The value of **u** is represented by a sessionToken returned by /signup and /signin API calls. This value MUST be url encoded everytime calling any API

* Every time the app is opened, you must call /user/checkin API

* Unless listed as POST, all other API calls are GET method

* All values sent MUST be urlencoded

----------

## 1 - Sign up - Username check

> /user/signup/usernameCheck/a/:apikey/e/:epoch/u/:sessionToken/type/:type/email/:email

This API is to be called afer the user enter's the email address to validate if this not taken by another user.

### Notes:

1. This API call generates errors:

	* if provided email address is in use

2. Submission method is GET


##### Parameters

1. Standard API Auth parameters (a, e, u)
2. *u*
	* Use a value of 0 since no sessionToken is available so far
3. *type*
	* c=consumer or d=delivery
4. *email*
	* Email


##### JSON Response Parameters

The response will be the following element:

1. *isAvailable*
	* true=username is available, false=username is NOT available

----------

## 2 - Sign up - Submit user information

> /user/signup/a/:apikey/e/:epoch/u/:sessionToken

This API is to be called when submitting the user's information for an account creation.

### Notes:

1. This API call generates errors:
	* if provided first or last names are less than 3 characters long
	* the email address is invalid
	* another user has the same email address
	* Password rules failed - Must contain at least 8 characters, including at least 1 upper case, lower case letters and a number.

2. Submission method is POST, but the URL must be: /user/signup/a/:apikey/e/:epoch/u/:sessionToken

3. Password MUST be urlencoded after encrypted using "String in Motion" key


##### Parameters

1. Standard API Auth parameters (a, e, u)
2. *u*
	* Use a value of 0 since no sessionToken is available so far
3. *type*
	* c=consumer or d=delivery
4. *firstName*
	* First Name
5. *lastName*
	* Last Name
6. *password*
	* Encrypted password using "String in Motion" key and then URL encoded
7. *email*
	* Email
8. *deviceArray*
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


##### JSON Response Parameters

The response will be the following element:

1. *u*
	* Token value representing the session of the current user

----------

## 3 - Sign up - Add Phone

> /user/addPhone/a/:apikey/e/:epoch/u/:sessionToken/phoneCountryCode/:phoneCountryCode/phoneNumber/:phoneNumber

This API is to be called afer the user has been signed up, to add a new phone. If no active phone exists, the user will NOT be allowed to login. This API will send a 4 digit code to the phone via SMS.

For resending the token, use this API.

This API controls the number of attempts to a max of 50 / hour / phone number. In addition it caches a response for 10 seconds, which means it won't send another token for 10 seconds even if called again.

### Notes:

1. Submission method is GET


##### Parameters

1. Standard API Auth parameters (a, e, u)
2. *phoneCountryCode*
	* Country Code, such 1=US, 91=India
3. *phoneNumber*
	* Numeric phone number (no spaces or dashes)


##### JSON Response Parameters

The response will be the following element:

1. *phoneId*
	* Identifier for the Phone; to be used when validating the phone number.


----------

## 4 - Sign up - Verify Phone

> /user/verifyPhone/a/:apikey/e/:epoch/u/:sessionToken/phoneId/:phoneId/verifyCode/:verifyCode

This API is to be called afer a phone number has been added to verify the onwership.

This API controls the number of attempts to a max of 10 / hour / phone number.

### Notes:

1. Submission method is GET


##### Parameters

1. Standard API Auth parameters (a, e, u)
2. *phoneId*
	* Phone Identifier from /addPhone call
3. *verifyCode*
	* 4 Digit code entered by the user


##### JSON Response Parameters

The response will be the following element:

1. *verified*
	* true/false


----------
----------

## 4 - Sign In By Phone - for custom management system

> /user/signInByPhone/a/:apikey/e/:epoch/u/:sessionToken/phoneId/:phoneId/verifyCode/:verifyCode

This API is to be called afer a phone number has been added to verify the onwership.

It is user (with combination addPhone) as signIn process, only way to authenticate user

### Notes:

1. Submission method is GET


##### Parameters

1. Standard API Auth parameters (a, e, u)
2. *phoneId*
	* Phone Identifier from /addPhone call
3. *verifyCode*
	* 4 Digit code entered by the user


##### JSON Response Parameters

The response will be the following element:

1. *verified*
	* true/false


----------

## Add promo code

> /signup/promo/a/:apikey/e/:epoch/u/:sessionToken/couponCode/:couponCode

Promo codes are the codes that can be added during registration,
By this code we can track place from where user came

### Notes:

1. Submission method is GET


##### Parameters

1. Standard API Auth parameters (a, e, u)

2. couponCode *string*
    * Coupon Id provided by user
    
   
##### JSON Response Parameters

The response will be the following element: 

1. id *string*
    * UserCoupon or UserCredit Id from the database
2. type *string*
    * type of added bonus - can be "coupon" or "credit"
3. creditsInCents *int*
    * amount of credits - in cents - added to user's account (for coupon it will be always 0)
4. welcomeMessage *string*
    * message that should be displayed to user
5. welcomeMessageLogoURL *string*
    * URL to logo to be shown along with the welcome message
    
##### Errors related
1. AS_819 - *Invalid coupon provided*

----------

## Check in

> /user/checkin/a/:apikey/e/:epoch/u/:sessionToken

This API should be called every time the App is opened.

*Note*: No errors (even the standard global API key, login) errors will be produced, instead empty response of the array could be produced.

### Notes:

1. Submission method is POST


##### Parameters

1. Standard API Auth parameters (a, e, u)

Following parameters are to be sent via POST

2. *deviceArray*
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
			* 1=true or 0=false, indicating if push notification is enabled
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

##### JSON Response Parameters

The response will be the following element:

1. *email*
2. *firstName*
3. *lastName*
4. *phoneCountryCode*
5. *phoneNumber*
6. *isEmailVerified*
	* true/false; if false, means user has not clicked the link in the verify email sent. Hence, you must show the user an option to resend the email using /emailVerifyResend API
7. *isLoggedIn*
	* true/false; If the user was logged in, aka sessionToken was valid
8. *isBetaActive*
	* true/false; If the user was logged in
9. *isLocked*
	* true/false; If the account is locked
10. *isPhoneVerified*
	* true/false; if the Phone has been verified; if not verified, no phone information would be provided
11. *isActive*
	* true/false; if the account is active
12. *coreInstructionCode*
	* This could be AS_9002, AS_9003, AS_9005, AS_9006
14. *coreInstructionText*
	* Any text associated with the 9000 series coreCode that should be shown to the user
15. *userId*
	* Id of the user used for internal identification; use for analytics tagging
16. *defaultOrderTimeWindowBeforeFlight*
	* for order scheduling, e.g. 60 mins before selected flight. This will be the default selection by the app
17. *deliveryTimeWindowIncrements*
	* for order scheduling, e.g. 15 mins. This will be the increments the scheduled order time windows will be shown for. For example, delivery windows will be 10:00 to 10:15 pm, 10:15 to 10:30 pm
18. *minTimeWindowBeforeFlight*
	* for order scheduling, e.g. 30 mins before selected flight departure. This means we will offer delivery up to 30 mins before departure
19. *isReferralProgramEnabled*
	* True/false indicates if the referral program is enabled. If set to false, the "Refer & Earn" row under the more tab should be hidden


----------

## User Info

> /user/info/a/:apikey/e/:epoch/u/:u

*What does this do?*

Returns the User Profile information

##### Parameters

1. Standard API Auth parameters (a, e, u)

##### JSON Response Parameters

The response will be the following element:

1. *email*
2. *firstName*
3. *lastName*
4. *phoneCountryCode*
5. *phoneNumber*
6. *isEmailVerified*
	* true/false; if false, means user has not clicked the link in the verify email sent. Hence, you must show the user an option to resend the email using /emailVerifyResend API
7. *isLoggedIn*
	* true/false; If the user was logged in, aka sessionToken was valid
8. *isBetaActive*
	* true/false; If the user was logged in
9. *isLocked*
	* true/false; If the account is locked
10. *isPhoneVerified*
	* true/false; if the Phone has been verified; if not verified, no phone information would be provided
11. *isActive*
	* true/false; if the account is active
12. *isSMSNotificationsEnabled*
	* true/false; are SMS notifications are enabled
14. *isPushNotificationEnabled*
	* true/false; are Push Notifications are enabled
15. *availableCredits*
	* int; number of credits (in cents) that are available for the user
16. *availableCreditsDisplay*
	* string; number of credits (in display form like $0.35) that are available for the user
17. *isSMSNotificationsOptOut*
	* true/false; if set to true that means user has Opt'd out of SMS via Twilio

----------

## User - Profile Update (Name and SMSNotificationsEnabled preference)

> /user/profile/update/a/:apikey/e/:epoch/u/:sessionToken

*What does this do?*

Changes user's first and last name, along with updating SMSNotificationsEnabled (this is used when sending Order updates to user; if set to true, SMS will be sent else not). 

*NOTE*: You should also show the current state of push notifications in the profile update, allowing user to switch it off/on from the App directly. When Push Notifications are turned off from the App (not iOS), then simply send isPushNotificationEnabled=0.

This API controls the number of attempts to a max of 10 / hour / account.

### Notes:

1. Submission method is POST


##### Parameters

1. Standard API Auth parameters (a, e, u)

Following parameters are to be sent via POST

2. *firstName*
	* First Name
3. *lastName*
	* Last Name
4. *SMSNotificationsEnabled*
	* true/false

##### JSON Response Parameters

The response will be the following element:

1. *changed*
	* true/false
2. *optInMessage*
	* If provided, show user as a dialogue box


----------

## User - Apply for Airport or Airline Employee discount

> /user/profile/update/airEmployee/a/:apikey/e/:epoch/u/:sessionToken

*What does this do?*

Accepts user's information to apply for airport employee discount.

### Notes:

1. Submission method is POST


##### Parameters

1. Standard API Auth parameters (a, e, u)

Following parameters are to be sent via POST

2. *employerName*
	* Employer Name
3. *employeeSince*
	* Date in (YYYY-MM-DD) format
4. *employmentCardImage*
	* Base 64 encoded binary of the employment card image (similar to bug report submission)

##### JSON Response Parameters

The response will be the following element:

1. *applied*
	* true/false

----------

## User - Verify if pending request for Airline Employee discount

> /user/profile/update/airEmployee/a/:apikey/e/:epoch/u/:sessionToken

##### Parameters

1. Standard API Auth parameters (a, e, u)

##### JSON Response Parameters

The response will be the following element:

1. *status*
	* 0=Not applied yet, hence show the form, 1=Applied and awaiting response, 2=Rejected
2. *rejectionReason*
	* Only filled in for status=2, listing the reason; show the user and then disallow to apply again; offer to reach us via Contact us form


----------

## User - Password change

> /user/profile/changePassword/a/:apikey/e/:epoch/u/:sessionToken

*What does this do?*

Changes user's password from the Profile page (user must be logged in for this function)

This API controls the number of attempts to a max of 10 / hour / account.

### Notes:

1. Submission method is POST


##### Parameters

1. Standard API Auth parameters (a, e, u)

Following parameters are to be sent via POST

2. *oldPassword*
	* Encrypted current password using "String in Motion" key and then URL encoded

3. *newPassword*
	* Encrypted new password using "String in Motion" key and then URL encoded

##### JSON Response Parameters

The response will be the following element:

1. *changed*
	* true/false


----------

## User - Email & username change

> /user/profile/changeEmail/a/:apikey/e/:epoch/u/:sessionToken/newEmail/:newEmail

*What does this do?*

Changes user's email and username; this will only work if the current email is not verified

This API controls the number of attempts to a max of 10 / hour / account.


##### Parameters

1. Standard API Auth parameters (a, e, u)
2. *newEmail*
	* New Email address


##### JSON Response Parameters

The response will be the following element:

1. *changed*
	* true/false


----------

## User - Email Verify Resend

> /user/profile/emailVerifyResend/a/:apikey/e/:epoch/u/:sessionToken

*What does this do?*

This API is to be used from the Profile screen when the user's current email address has not been verified. You can identify if this is the case by looking at the response /profie/info API

This API controls the number of attempts to a max of 10 / hour / account.

##### Parameters

1. Standard API Auth parameters (a, e, u)

##### JSON Response Parameters

The response will be the following element:

1. *status*
	* true=email sent; false=email send failed


----------
----------

## Forgot Password

This process is made up of 3 API calls that need to be called in a squential pattern.

----------

## Forgot - Step 1 - Request Token

> /user/forgot/requestToken/a/:apikey/e/:epoch/u/:sessionToken/type/:type/email/:email

*What does this do?*

This is the 1/3 APIs that need to be called to reset a password. This step genreates/request a forgot token that is sent to the user via the email address on account. If the user provides an email address that is not on file, even then the response will be true. This is done to ensure, users can't identify which email addresses are on account.

The token sent to the user will be 6 digits long.

This API controls the number of attempts to a max of 10 / hour / email. In addition it caches a response for 10 seconds, which means it won't send another token for 10 seconds even if called again.

##### Parameters

1. Standard API Auth parameters (a, e, u)
2. *email*
	* Email address associated with the account that needs a password reset
3. *type*
	* Account type c=consumer, d=delivery

##### JSON Response Parameters

The response will be the following element:

1. *status*
	* true=regardless if the email is sent


----------

## Forgot - Step 2 - Validate Token

> /user/forgot/validateToken/a/:apikey/e/:epoch/u/:sessionToken/type/:type/email/:email/token/:token

*What does this do?*

This is the 2/3 APIs that need to be called to reset a password. This step validates the token the user enters after receiving the file. This token will be 6 digits long. 

This API controls the number of attempts to a max of 10 / hour / email.

##### Parameters

1. Standard API Auth parameters (a, e, u)
2. *email*
	* Email address associated with the account that needs a password reset
3. *type*
	* Account type c=consumer, d=delivery
4. *token*
	* 6 digit token entered by the user

##### JSON Response Parameters

The response will be the following element:

1. *status*
	* true=token correct; false=token incorrect



----------

## Forgot - Step 3 - Change Password

> /user/forgot/changePassword/a/:apikey/e/:epoch/u/:sessionToken

*What does this do?*

This is the 3/3 APIs that need to be called to reset a password. This step is where the password is changed. You should call this after Step 2 provides a valid response.

This API controls the number of attempts to a max of 10 / hour / email.

### Notes:

1. Submission method is POST


##### Parameters

1. Standard API Auth parameters (a, e, u)

Following parameters are to be sent via POST

2. *email*
	* Email address associated with the account that needs a password reset
3. *type*
	* Account type c=consumer, d=delivery
4. *token*
	* 6 digit token entered by the user
5. *newPassword*
	* New Password entered by the user; Encrypted new password using "String in Motion" key and then URL encoded

##### JSON Response Parameters

The response will be the following element:

1. *status*
	* true=password changed; false=not changed


----------

## Sign out

> /user/signout/a/:apikey/e/:epoch/u/:sessionToken

*What does this do?*

Logs out the user for the current session. If the user is signed in from another device, that won't be logged out.


##### Parameters

1. Standard API Auth parameters (a, e, u)

##### JSON Response Parameters

The response will be the following element:

1. *status*
	* true=logged out; false=log out failed


----------

## Add profile data for custom session managemet

> /user/addProfileData/a/:apikey/e/:epoch/u/:sessionToken

*What does this do?*

updates firstName, lastName and email for a user


##### Parameters

1. Standard API Auth parameters (a, e, u)

##### JSON Response Parameters

The response will be the following element:

1. *success*
	* true=updated; false=not updated (error will be send instead)

----------

## Referral Status

> /refer/a/:apikey/e/:epoch/u/:sessionToken

*What does this do?*

This is the 3/3 APIs that need to be called to reset a password. This step is where the password is changed. You should call this after Step 2 provides a valid response.

This API controls the number of attempts to a max of 10 / hour / email.


##### Parameters

1. Standard API Auth parameters (a, e, u)


##### JSON Response Parameters

The response will be the following element:

1. *referralCode*
	* Unique Referral code associated with the user
2. *totalEarnedDollarFormatted*
	* $ formatted number of the total earnings so far
3. *offerTextDisplay*
	* Welcome offer text, e.g. "Refer and get $5"
4. *sampleReferTextDisplay*
	* Sample message that can be shared in a pre-populated Text message or email
5. *sampleReferTitleDisplay*
	* Sample title/subect hat can be shared in a pre-populated Text message or email
6. *rulesOverviewTextDisplay*
	* Overview of the process that should be displayed on the screen
7. *referralProgramRulesLink*
	* Link to the rules page for the Referral Program


----------

## Install

> /user/install/a/:apikey/e/:epoch/u/:sessionToken/referral/:referral

This API should be called every time the App is opened.

*Note*: No errors (even the standard global API key, login) errors will be produced, instead empty response of the array could be produced.

### Notes:

1. Submission method is POST. Send this call async and not wait for the response.

##### Parameters


1. Standard API Auth parameters (a, e, u)
2. *referral* (in the URL)
	* Code provided by Google Play or App Store as a referral from where the install came from

Following parameters are to be sent via POST

3. *deviceArray* (same as /checkin)
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
			* 1=true or 0=false, indicating if push notification is enabled
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

##### JSON Response Parameters

The response will be the following element:

1. *logged*
	* True
