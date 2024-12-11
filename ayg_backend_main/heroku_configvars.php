<?php

	putenv("env_EnvironmentDisplayCode=PROD");
	putenv("env_EnvironmentDisplayCodeNoProd="); // Leave Empty for Prod

	// Back4App
	putenv("env_ParseApplicationId=");
	putenv("env_ParseMasterKey=");
	putenv("env_ParseRestAPIKey=");
	putenv("env_ParseServerURL=https://parseapi.back4app.com");
	putenv("env_ParseMount=/");


	putenv("env_bufferBeforeOrderTimeInSecondsRange=15");
	putenv("env_bufferForPrepTimeInSeconds=5");
	putenv("env_bufferForDeliveryTimeInSeconds=5");
	putenv("env_defaultDeliveryWalkTimeInSeconds=15");

	putenv("env_BraintreeEnvironment=production");
	putenv("env_BraintreeMerchantId=");
	putenv("env_BraintreePrivateKey=");
	putenv("env_BraintreePublicKey=");

	putenv("env_isOpenForGeneralAvailability=0");
	putenv("env_PingRetailerIntervalInSecs=60");
	putenv("env_PingRetailerReportIntervalInSecs=300");
	putenv("env_PingRetailerWBatteryCheckIntervalInSecs=3600");
	putenv("env_PingSlackDeliveryIntervalInSecs=60");
	putenv("env_OrderSlackDeliveryDelaysCheckIntervalInSecs=120");
	putenv("env_OrderRetailerDelaysCheckIntervalInSecs=120");
	putenv("env_InvDelayBuzzToTabletIntervalInSecs=60");
	putenv("env_DeliveryFeesInCentsDefault=299");
	putenv("env_PickupFeesInCentsDefault=0");
	putenv("env_DeliveryMaxActiveOrders=10");
	putenv("env_DeliveryStatusUpdateMinIntervalInSecs=10");
	putenv("env_TabletBuzzActive=false");
	putenv("env_PingSlackGraceMultiplier=2");
	putenv("env_S3AccessKey=");
	putenv("env_S3AccessSecret=");
	putenv("env_S3BucketName=ayg-p1");
	putenv("env_S3Endpoint=https://ayg-p1.s3-accelerate.amazonaws.com");

	putenv("env_SlackWH_orderForcedClosed=");
	putenv("env_SlackWH_orderInvPrintDelay=");
	putenv("env_SlackWH_orderNotifications=");
	putenv("env_SlackWH_orderProcErrors=");
	putenv("env_SlackWH_ordersBalVariance=");
	putenv("env_SlackWH_posPingFail=");
	putenv("env_SlackWH_deliveryPingFail=");

	putenv("env_SlackWH_bugReports=");
	putenv("env_SlackWH_contactForm=");
	putenv("env_SlackWH_newUserSignup=");
	putenv("env_SlackWH_orderHelp=");
	putenv("env_SlackWH_counterAlerts=");
	putenv("env_SlackWH_deadletterAlerts=");

	putenv("env_Slack_tokenPOSApp=");
	putenv("env_Slack_tokenPing=");
	putenv("env_MobiLock_APIKey=");

	putenv("env_PaymentResponseEncryptionKey=");
	putenv("env_TokenEncryptionKey=");
	putenv("env_StringInMotionEncryptionKey=");
	putenv("env_AppRestAPIKeySalt=");
	putenv("env_WebRestAPIKeySalt=");
	putenv("env_OpsRestAPIKeySalt=");
	putenv("env_PasswordHashSalt=");
	putenv("env_RestAPITokenExpiryInMins=200");
	putenv("env_SlimLogLevel=DEBUG");
	putenv("env_OmnivoreAPIKey=");
	putenv("env_TwilioSID=");
	putenv("env_TwilioToken=");
	putenv("env_TwilioPhoneNumber=");
	putenv("env_CacheEnabled=true");

	putenv("env_CacheRedisURL=redis://rediscloud:password@pub-redis-19644.us-east-1-4.2.ec2.garantiadata.com:19644");
	putenv("env_CacheSSLCA=");
	putenv("env_CacheSSLCert=");
	putenv("env_CacheSSLPK=");

	putenv("env_AppRestAPIKeyForLoadTesting=");

	putenv("env_EmailFromAddress=no-reply@ayg.com");
	putenv("env_EmailFromName=AYG");
	putenv("env_EmailTemplatePath=assets/email_templates/");
	putenv("env_SendGridAPIKey=");
	putenv("env_SherpaExternalAPIURL=https://ayg-ext.herokuapp.com");

	putenv("env_workerQueueConsumerDeadLetterLPInSecs=20");
	putenv("env_workerQueueConsumerLPInSecs=20");

	putenv("env_AuthyAPIKey=");
	putenv("env_OneSignalAppId=");
	putenv("env_OneSignalRestKey=");
	putenv("env_OneSignalTestType=");
	putenv("env_OneSignalTemplateIds=");

	putenv("env_APIMaintenanceFlag=0");
	putenv("env_APIMaintenanceMessage=");
	putenv('env_OpenWeatherMapAPIURL=http://api.openweathermap.org/data/2.5/forecast');
	putenv('env_OpenWeatherMapAPIKey=');

	// TripIt settings
	putenv("env_TripITOAuthConsumerKey=");
	putenv("env_TripITOAuthConsumerSecret=");

	// FlightStats settings
	putenv("env_FlightStatsAppId=");
	putenv("env_FlightStatsAppKey=");

	// FlightAware settings
	putenv("env_FlightAwareUsername=ayg");
	putenv("env_FlightAwareAppKey=");

	// Google Print
	putenv('env_GooglePrintRefreshToken=');
	putenv('env_GooglePrintClientId=');
	putenv('env_GooglePrintClientSecret=');
	
	// System Path in Heroku, used for Scheduled jobs
	putenv('env_HerokuSystemPath=');

	putenv("env_RabbitMQConfig=amqp://ayg:password@large-duckbill.rmq.cloudamqp.com:5672/ayg"); // No need
	putenv("env_RabbitMQConfigUseSSL=false");
	putenv("env_RabbitMQConsumerPrimaryQueueName=p1-rabbitmq-consumer");
	putenv("env_RabbitMQAPIUrl=https://ayg:password@large-duckbill.rmq.cloudamqp.com/api/queues/ayg/");
	putenv("env_RabbitMQDeliveryPrimaryQueueName=");
	putenv("env_RabbitMQDeliveryDeadLetterQueueName=");

	putenv("env_RetailerPOSStringInMotionEncryptionKey=");
	putenv("env_RetailerPOSAppRestAPIKeySalt=");

	putenv("env_QueueType=QueueIronMQ");
	putenv("env_TabletAppDefaultPingIntervalInSecs=20");
	putenv("env_TabletAppDefaultVibrateUsage=true");
	putenv("env_TabletAppDefaultNotificationSoundUrl=https://s3.amazonaws.com/ayg-p1/sounds/order_notification.mp3");
	putenv("env_TabletBatteryCheckIntervalInSecs=60");
	putenv("env_TabletAppMultipleRetailerLogoUrl=https://s3.amazonaws.com/ayg-p1/images/_as/BWI/retailer/logo/02e37acafd5557555efbcaa15082ae71_GenericRetailer.png");

	putenv("env_DeliveryStatusUpdateMinIntervalInSecs=5");
	putenv("env_DeliveryMaxActiveOrders=10");
	putenv("env_TabletBuzzActive=true");

	putenv("env_RetailerEarlyCloseMinWaitInSecs=300");


	putenv("env_mysqlLogsDataBaseHost=ayg-rds.c0hdpm1fcoqb.us-east-1.rds.amazonaws.com");
	putenv("env_mysqlLogsDataBaseName=ayg_rds_prod");
	putenv("env_mysqlLogsDataBaseUser=ayg_prod");
	putenv("env_mysqlLogsDataBasePassword=");
	putenv("env_mysqlLogsDataBasePort=3310");

	putenv("env_AllowCreditsForPickup=false");
	putenv("env_AllowCreditsForDelivery=true");

	putenv("env_APIKeyMaxUsage=30");

	putenv("env_AllowDuplicateAccounts=true");
	putenv("env_AllowDuplicatePhoneRegistration=true");
	putenv("env_AllowDuplicatePaymentMethod=true");
	putenv("env_AllowDuplicateCouponUsageByDevice=false");

	putenv("env_BlockDevicesAtRegistration=false");
	
	putenv("env_LogQueueTransactionsToDB=true");

	putenv("env_MinVersionServiceFeeiOS=1.5.9");
	putenv("env_MinVersionServiceFeeAndroid=0.0.0");
	putenv("env_ServiceFeePCT=0.1");

	putenv('env_GoogleMapsKey=');

	putenv('env_MilitaryDiscountEnabled=true');
	putenv('env_AirportEmployeeDiscountEnabled=false');

	putenv('env_UserReferralRewardExpireInSeconds=15811200');
	putenv('env_UserReferralMinSpendInCentsForReward=0');
	putenv('env_UserReferralWaitInSecsBeforeAward=86400');
	putenv('env_UserReferralRewardInCents=500');
	putenv('env_UserReferralOfferInCents=500');
	putenv('env_UserReferralWelcomeImageFileName=xuserreferral.png');
	putenv('env_ReferralMaxPerDay=0');
	putenv('env_UserReferralApplyCouponRestriction=true');
	putenv('env_InHerokuRun=N');
	putenv('env_UserReferralRulesLink=https://www.ayg.com/referral.html');
	putenv('env_AllowDuplicateAccountsForReferral=false');
	putenv('env_UserRefrralMinSpendInCentsForReward=0');
	putenv('env_UserReferralMinSpendInCentsForOfferUse=1000');
	putenv('env_UserReferralRewardEnabled=true');

	putenv('env_IPStackKey=');

	putenv('env_LogUserActions=true');
	putenv('env_LogUserActionsInSlack=true');
	putenv('env_LogUserActionsInSlackForAllLocations=true');
	putenv('env_SlackWH_userActions=');
	putenv('env_SlackWH_menuUpdates=');

	putenv('env_RemoveSpecialCharsFromItemComment=true');

	putenv('env_HMSHostURL=https://digitalmwprod.azure-api.net/v2');
	putenv('env_HMSHostClientID=');
	putenv('env_HMSHostUsername_Menu=contact+hms.integration@ayg.com');
	putenv('env_HMSHostPassKey_Menu=');
	putenv('env_HMSHostPassKey_SubscriptionKey_Menu=');

	putenv('env_HMSHostUsername_Ping=contact+hms.integration@ayg.com');
	putenv('env_HMSHostPassKey_Ping=');
	putenv('env_HMSHostPassKey_SubscriptionKey_Ping=');

	putenv('env_HMSHostUsername_Order=contact+hms.integration@ayg.com');
	putenv('env_HMSHostPassKey_Order=');
	putenv('env_HMSHostPassKey_SubscriptionKey_Order=');

	putenv('env_deliveryUpAutoNotification=true');
	putenv('env_deliveryUpAutoNotificationLookbackTimeInMins=60');

	putenv('env_CognitoAppClientId=');
	putenv('env_CognitoAppClientSecret=');
	putenv('env_CognitoUserPoolId=');

	putenv('env_CognitoKey=');
	putenv('env_CognitoSecret=');

	putenv('env_PartnerRestAPIKeySalt=');
	putenv('env_S3BucketNameExtPartner=ayg-extpartner-prod');
	putenv('env_S3EndpointExtPartner=https://ayg-extpartner-prod.s3-accelerate.amazonaws.com');



	putenv('env_OrderRatingRequestsMaxPerUser=2');
	putenv('env_OrderRatingRequestsMaxPerUserPerDay=1');
	putenv('env_UserSMSNotificationsEnabledOnSignup=true');

	putenv('env_ZendeskUsername=support@ayg.com');
	putenv('env_ZendeskAPIToken=');
	putenv('env_ZendeskCreateTickets=true');


	putenv('env_fullfillmentETALowInSecs=300');
	putenv('env_fullfillmentETAHighInSecs=300');
	putenv('env_LyftPromoEnabled=true');
?>