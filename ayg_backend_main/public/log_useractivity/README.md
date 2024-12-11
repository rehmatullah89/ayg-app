# Airport Sherpa API Usage Guide

## Log - User Activity 

### Keys

> See root README.MD


## Overview

This wrapper API is to be used for logging certain user activities, e.g. Retailer Visits.

--------

## Retailer Visit

Logs the retailer visits. Only unique visits in last 15 minutes are logged.

> /log_useractivity/rvisits/apikey/:apikey/epoch/:epoch/parseuserid/:parseuserid/retailer/:uniqueRetailerId/airportiatacode/:airportIataCode

##### Parameters

1. Standard API Auth parameters (apikey, epoch, parseuserid)
2. *airportIataCode*
	* IATA Airport Code of which the list the Retailer belongs to
3. *uniqueRetailerId*
	* Retailer's uniqueId (not objectId) that was visited

##### JSON Response Parameters

The response will be either an error code or string response.

1. *logged*
	* with a value of 1 indicating the visit was logged

