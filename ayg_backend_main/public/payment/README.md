# Airport Sherpa API Usage Guide

## Payment

### Keys

> See root README.MD


## Overview

This wrapper API provides Payment-related functionality, such as save transaction id, save and get customer payment id operations. To view the full usage view the documentation in README.MD of the API root directory.


----------

## Get Token

*What does this do?*

Generates a Braintree token. If any existing customer is present (from a previous run of /payment/save), then it will be used for generating a token, else non-customer id specific token will be generated

Returns: String, Token

> /token/apikey/:apikey/epoch/:epoch/sessionToken/:sessionToken

##### Parameters

1. Standard API Auth parameters (apikey, epoch, sessionToken)

##### JSON Response Parameters

The response will be the following element:

1. *token*
	* Token from Braintree

----------

## List of Payment methods stored for the user

*What does this do?*

This method should be called to get the AES encrypted list of payment methods stored of the user

> /list/apikey/:apikey/epoch/:epoch/sessionToken/:sessionToken

##### Parameters

1. Standard API Auth parameters (apikey, epoch, sessionToken)

	
##### JSON Response Parameters

The response will be the following element which is Json_encoded (with AES encrypted data):

1. *paymentTypes*
	* This text when decrypted contains a JSON array with the following items
		1. *token*
			* Identifier of the Payment Method
		2. *paymentType*
			* Type of payment method used, e.g. credit_card, paypal_account
		3. *paymentTypeName*
			* Name of the payment method used, e.g. MasterCard
		4. *paymentTypeId*
			* For credit cards its the last 4 digits of the card used; for PayPal it will be the Email address of the Paypal account
		5. *paymentTypeIconURL*
			* URL to Icon used for the Payment Type used, e.g. for MasterCard, it will be the master card icon
		6. *expired*
			* Y=Expired, N=Not expired; expired cards should be shown in red color
			

----------

## Create Payment method for the user

*What does this do?*

This method should be called to create and save a payment method

> /create/apikey/:apikey/epoch/:epoch/sessionToken/:sessionToken/paymentMethodNonce/:paymentMethodNonce

##### Parameters

1. Standard API Auth parameters (apikey, epoch, sessionToken)
2. *paymentMethodNonce*
	* Payment Method Nonce from Braintree for the created Payment Method
	
##### JSON Response Parameters

The response will be the following element:

1. *created*
	* 1=Yes, 0=No
2. *token*
	* Temporary (will be replaced by the paymentTypes object attribute)
3. *paymentTypes*
	* This text when decrypted contains a JSON array with the following items for the newly created Payment Method
		1. *token*
			* Identifier of the Payment Method
		2. *paymentType*
			* Type of payment method used, e.g. credit_card, paypal_account
		3. *paymentTypeName*
			* Name of the payment method used, e.g. MasterCard
		4. *paymentTypeId*
			* For credit cards its the last 4 digits of the card used; for PayPal it will be the Email address of the Paypal account
		5. *paymentTypeIconURL*
			* URL to Icon used for the Payment Type used, e.g. for MasterCard, it will be the master card icon
		6. *expired*
			* Y=Expired, N=Not expired; expired cards should be shown in red color

----------

## Delete Payment method stored for the user

*What does this do?*

This method should be called to delete a payment method

> /delete/apikey/:apikey/epoch/:epoch/sessionToken/:sessionToken/token/:token

##### Parameters

1. Standard API Auth parameters (apikey, epoch, sessionToken)
2. *token*
	* Token received for the Payment Method from the /list API
	
##### JSON Response Parameters

The response will be the following element:

1. *deleted*
	* 1=Yes, 0=No
