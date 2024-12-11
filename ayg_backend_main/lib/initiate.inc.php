<?php

if (getenv('env_EnvironmentDisplayCode') == 'PROD') {
    error_reporting(0);
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 'On');
}
//error_reporting(E_ALL);
//ini_set('display_errors', 'On');

date_default_timezone_set('America/New_York');

// Fetch inside heroku variables
$env_InHerokuRun = getenv('env_InHerokuRun');

// Initializing the variable
$devErrorFilePath = "";


/////////////// DEV ONLY USE ///////////////
// DEV Override to see log messages (aka what goes on stderror) on console; set to 1 to activate
// It will deactivate if env_InHerokuRun = Y
$turnOnHerokuErrorsOnLocal = getenv('turnOnHerokuErrorsOnLocal');

// IN Development, load local config
if (strcasecmp($env_InHerokuRun, "Y") != 0) {

    require $dirpath . 'local/localenv.php';
}
///////////////////////////////////////////


// if(!function_exists('money_format')) {

require $dirpath . 'lib/function_money_format.php';
// }

// Set Environment Variables
// S3 Paths
$env_S3Path_PublicImages = 'images';

$env_S3Path_PublicImagesAirportBackground = 'airport/bg';
$env_S3Path_PublicImagesDirection = 'direction';

$env_S3Path_PublicImagesCouponLogo = 'coupon/logo';

$env_S3Path_PublicImagesAirportSpecific = 'images/_as';
$env_S3Path_PublicImagesRetailerLogo = 'retailer/logo';
$env_S3Path_PublicImagesRetailerBackground = 'retailer/bg';
$env_S3Path_PublicImagesRetailerItem = 'retailer/item';
$env_S3Path_PublicImagesRetailerAds = 'retailer/ads';
$env_S3Path_PublicImagesPartnerLogo = 'partner/logo';

$env_S3Path_PublicImagesUserSubmitted = 'images/_us';
$env_S3Path_PublicImagesUserSubmittedBug = 'bug';
$env_S3Path_PublicImagesUserSubmittedProfile = 'profile';

$env_S3Path_PublicImagesAppIconsFontAwesome = 'images/icons/fa';

$env_S3Path_Logs = 'logs';
$env_S3Path_PrivateFiles = 'files';
$env_S3Path_PrivateFilesInvoice = 'private/invoice';
$env_S3Path_PrivateFilesAirEmployee = 'private/employmentCard';

$env_S3Path_PrivateMenu = 'private/menus';
$env_S3Path_PrivateMenuLog = 'logs';
$env_S3Path_PrivateMenuImages = 'images';
$env_S3Path_PrivateMenuInternal = 'internal';
$env_S3Path_PrivateMenuPartner = 'partner';

$env_FlightStatsAPIURLPrefix_Status = 'https://api.flightstats.com/flex/flightstatus/rest/v2/json/flight/status/';
$env_FlightStatsAPIURLPrefix_Schedule = 'https://api.flightstats.com/flex/schedules/rest/v1/json/flight/';
$env_FlightAwareAPIURLPrefix_Status = 'http://flightxml.flightaware.com/soap/FlightXML2';
// $env_OmnivoreAPIURLPrefix 				= 'https://api.omnivore.io/0.1/locations/';
$env_OmnivoreAPIURLPrefix = 'https://api.omnivore.io/1.0/locations/';
$env_AuthyPhoneStartURL = 'https://api.authy.com/protected/json/phones/verification/start';
$env_AuthyPhoneCheckURL = 'https://api.authy.com/protected/json/phones/verification/check';
$env_OneSignalAddDeviceURL = 'https://onesignal.com/api/v1/players';
$env_OneSignalNotificationsURL = 'https://onesignal.com/api/v1/notifications';
$env_SlackPingAPIURLPrefix = 'https://slack.com/api/users.getPresence';
$env_MobiLockAPIURLPrefix = 'https://scalefusion.com/api/v1/devices/';
$env_MobiLockAPIURLAlaramSuffix = '/send_alarm.json';
$env_MobiLockAPIURLBatterySuffix = '/battery.json';
$env_MobiLockAPIURLMessageSuffix = 'broadcast_message.json';
$env_MobiLockAPIAllInfoURL = 'https://scalefusion.com/api/v1/devices.json';

$env_APIMaintenanceFlag = getenv('env_APIMaintenanceFlag');
$env_APIMaintenanceMessage = getenv('env_APIMaintenanceMessage');
// JMD
$env_isOpenForGeneralAvailability		= getenv('env_isOpenForGeneralAvailability');
$env_PasswordHashSalt					= getenv('env_PasswordHashSalt');

$env_AppRestAPIKeySalt 					= getenv('env_AppRestAPIKeySalt');
$env_AppRestAPIKeySaltIos 				= getenv('env_AppRestAPIKeySaltIos');
$env_AppRestAPIKeySaltAndroid 			= getenv('env_AppRestAPIKeySaltAndroid');
$env_AppRestAPIKeySaltWebsite 			= getenv('env_AppRestAPIKeySaltWebsite');

$env_RetailerPOSAppRestAPIKeySalt = getenv('env_RetailerPOSAppRestAPIKeySalt');
$env_PartnerRestAPIKeySalt = getenv('env_PartnerRestAPIKeySalt');
$env_WebRestAPIKeySalt = getenv('env_WebRestAPIKeySalt');
$env_OpsRestAPIKeySalt = getenv('env_OpsRestAPIKeySalt');
// JMD
$env_AppRestAPIKeyForLoadTesting = getenv('env_AppRestAPIKeyForLoadTesting');
$env_RestAPITokenExpiryInMins = getenv('env_RestAPITokenExpiryInMins');
$env_TripITOAuthConsumerKey = getenv('env_TripITOAuthConsumerKey');
$env_TripITOAuthConsumerSecret = getenv('env_TripITOAuthConsumerSecret');


$env_FlightStatsAppId = getenv('env_FlightStatsAppId');
$env_FlightStatsAppKey = getenv('env_FlightStatsAppKey');

$env_FlightAwareUsername = getenv('env_FlightAwareUsername');
$env_FlightAwareAppKey = getenv('env_FlightAwareAppKey');

// JMD
$env_ParseServerURL = getenv('env_ParseServerURL');
$env_ParseApplicationId = getenv('env_ParseApplicationId');
$env_ParseRestAPIKey = getenv('env_ParseRestAPIKey');
$env_ParseMasterKey = getenv('env_ParseMasterKey');
$env_ParseMount = getenv('env_ParseMount');
$env_GCSQLDbHost = getenv('env_GCSQLDbHost');
$env_GCSQLDbName = getenv('env_GCSQLDbName');
$env_GCSQLDbUser = getenv('env_GCSQLDbUser');
$env_GCSQLDbPass = getenv('env_GCSQLDbPass');
$env_HerokuPGDbHost = getenv('env_HerokuPGDbHost');
$env_HerokuPGDbName = getenv('env_HerokuPGDbName');

$env_HerokuPGDbUser = getenv('env_HerokuPGDbUser');
$env_HerokuPGDbPass = getenv('env_HerokuPGDbPass');
$env_SlimLogLevel = getenv('env_SlimLogLevel');
$env_OmnivoreAPIKey = getenv('env_OmnivoreAPIKey');
$env_BraintreeEnvironment = getenv('env_BraintreeEnvironment');
$env_BraintreeMerchantId = getenv('env_BraintreeMerchantId');
$env_BraintreePublicKey = getenv('env_BraintreePublicKey');
$env_BraintreePrivateKey = getenv('env_BraintreePrivateKey');
$env_DeliveryFeesInCentsDefault = intval(getenv('env_DeliveryFeesInCentsDefault'));
$env_PickupFeesInCentsDefault = intval(getenv('env_PickupFeesInCentsDefault'));

$env_PingRetailerIntervalInSecs = getenv('env_PingRetailerIntervalInSecs');
$env_PingRetailerReportIntervalInSecs = intval(getenv('env_PingRetailerReportIntervalInSecs'));
$env_PingSlackGraceMultiplier = getenv('env_PingSlackGraceMultiplier');
$env_PingRetailerWBatteryCheckIntervalInSecs = getenv('env_PingRetailerWBatteryCheckIntervalInSecs');
$env_PingSlackDeliveryIntervalInSecs = getenv('env_PingSlackDeliveryIntervalInSecs');
$env_OrderSlackDeliveryDelaysCheckIntervalInSecs = getenv('env_OrderSlackDeliveryDelaysCheckIntervalInSecs');
$env_OrderRetailerDelaysCheckIntervalInSecs	= getenv('env_OrderRetailerDelaysCheckIntervalInSecs');
$env_InvDelayBuzzToTabletIntervalInSecs	= getenv('env_InvDelayBuzzToTabletIntervalInSecs');
$env_DeliveryMaxActiveOrders				= getenv('env_DeliveryMaxActiveOrders');
$env_DeliveryStatusUpdateMinIntervalInSecs= getenv('env_DeliveryStatusUpdateMinIntervalInSecs');
$env_TabletBuzzActive					= (getenv('env_TabletBuzzActive') === 'true');
$env_RetailerEarlyCloseMinWaitInSecs	= intval(getenv('env_RetailerEarlyCloseMinWaitInSecs'));
$env_S3AccessKey						= getenv('env_S3AccessKey');
$env_S3AccessSecret						= getenv('env_S3AccessSecret');
$env_S3BucketName						= getenv('env_S3BucketName');
$env_S3Endpoint							= getenv('env_S3Endpoint');
$env_S3BucketNameExtPartner				= getenv('env_S3BucketNameExtPartner');
$env_S3EndpointExtPartner				= getenv('env_S3EndpointExtPartner');
$env_CognitoAppClientId            		= getenv('env_CognitoAppClientId');
$env_CognitoAppClientSecret         	= getenv('env_CognitoAppClientSecret');
$env_CognitoUserPoolId              	= getenv('env_CognitoUserPoolId');
$env_CognitoKey                     	= getenv('env_CognitoKey');
$env_CognitoSecret                  	= getenv('env_CognitoSecret');


/** done */
$env_PaymentResponseEncryptionKey 		= getenv('env_PaymentResponseEncryptionKey');
$env_PaymentResponseEncryptionKeyIos 		= getenv('env_PaymentResponseEncryptionKeyIos');
$env_PaymentResponseEncryptionKeyAndroid 		= getenv('env_PaymentResponseEncryptionKeyAndroid');
$env_PaymentResponseEncryptionKeyWebsite 		= getenv('env_PaymentResponseEncryptionKeyWebsite');


/** done */
$env_TokenEncryptionKey 				= getenv('env_TokenEncryptionKey');
$env_TokenEncryptionKeyIos 				= getenv('env_TokenEncryptionKeyIos');
$env_TokenEncryptionKeyAndroid 				= getenv('env_TokenEncryptionKeyAndroid');
$env_TokenEncryptionKeyWebsite 				= getenv('env_TokenEncryptionKeyWebsite');

$env_StringInMotionEncryptionKey 		= getenv('env_StringInMotionEncryptionKey');
$env_StringInMotionEncryptionKeyIos 		= getenv('env_StringInMotionEncryptionKeyIos');
$env_StringInMotionEncryptionKeyAndroid 		= getenv('env_StringInMotionEncryptionKeyAndroid');
$env_StringInMotionEncryptionKeyWebsite 		= getenv('env_StringInMotionEncryptionKeyWebsite');


$env_RetailerPOSStringInMotionEncryptionKey = getenv('env_RetailerPOSStringInMotionEncryptionKey');
$env_DeliveryStringInMotionEncryptionKey = getenv('env_DeliveryStringInMotionEncryptionKey');

$env_GooglePrintRefreshToken = getenv('env_GooglePrintRefreshToken');
$env_GooglePrintClientId = getenv('env_GooglePrintClientId');
$env_GooglePrintClientSecret = getenv('env_GooglePrintClientSecret');
$env_GoogleMapsKey = getenv('env_GoogleMapsKey');
$env_EnvironmentDisplayCode = getenv('env_EnvironmentDisplayCode');
$env_EnvironmentDisplayCodeNoProd = getenv('env_EnvironmentDisplayCodeNoProd');
$env_SlackWH_orderNotifications = getenv('env_SlackWH_orderNotifications');
$env_SlackWH_orderProcErrors = getenv('env_SlackWH_orderProcErrors');
$env_SlackWH_ordersBalVariance = getenv('env_SlackWH_ordersBalVariance');
$env_SlackWH_orderInvPrintDelay = getenv('env_SlackWH_orderInvPrintDelay');
$env_SlackWH_orderForcedClosed = getenv('env_SlackWH_orderForcedClosed');
$env_Slack_tokenPOSApp = getenv('env_Slack_tokenPOSApp');
$env_Slack_tokenPing = getenv('env_Slack_tokenPing');
$env_MobiLock_APIKey = getenv('env_MobiLock_APIKey');
$env_SlackWH_posPingFail = getenv('env_SlackWH_posPingFail');
$env_SlackWH_deliveryPingFail = getenv('env_SlackWH_deliveryPingFail');
$env_SlackWH_bugReports = getenv('env_SlackWH_bugReports');
$env_SlackWH_contactForm = getenv('env_SlackWH_contactForm');
$env_SlackWH_newUserSignup = getenv('env_SlackWH_newUserSignup');
$env_SlackWH_orderHelp = getenv('env_SlackWH_orderHelp');
$env_SlackWH_counterAlerts = getenv('env_SlackWH_counterAlerts');
$env_SlackWH_deadletterAlerts = getenv('env_SlackWH_deadletterAlerts');
$env_SlackWH_userActions = getenv('env_SlackWH_userActions');
$env_SlackWH_menuUpdates = getenv('env_SlackWH_menuUpdates');
$env_OpenWeatherMapAPIURL = getenv('env_OpenWeatherMapAPIURL');
$env_OpenWeatherMapAPIKey = getenv('env_OpenWeatherMapAPIKey');
$env_TwilioSID = getenv('env_TwilioSID');
$env_TwilioToken = getenv('env_TwilioToken');
$env_TwilioPhoneNumber = getenv('env_TwilioPhoneNumber');
$env_CacheEnabled = (getenv('env_CacheEnabled') === 'true');
$env_CacheRedisURL = getenv('env_CacheRedisURL');
$env_CacheSSLCA = getenv('env_CacheSSLCA');
$env_CacheSSLCert = getenv('env_CacheSSLCert');
$env_CacheSSLPK = getenv('env_CacheSSLPK');
$env_EmailFromAddress = getenv('env_EmailFromAddress');
$env_EmailFromName = getenv('env_EmailFromName');
$env_EmailTemplatePath = getenv('env_EmailTemplatePath');
$env_SendGridAPIKey = getenv('env_SendGridAPIKey');

$env_SocketLabsClientInjectionApiKey = getenv('env_SocketLabsClientInjectionApiKey');
$env_SocketLabsClientServerId = getenv('env_SocketLabsClientServerId');


$env_SherpaExternalAPIURL				= getenv('env_SherpaExternalAPIURL');
$env_SQSDeliveryPrimaryQueueName		= getenv('env_SQSDeliveryPrimaryQueueName');
$env_SQSDeliveryDeadLetterQueueName		= getenv('env_SQSDeliveryDeadLetterQueueName');
$env_SQSConsumerPrimaryQueueName		= getenv('env_SQSConsumerPrimaryQueueName');
$env_SQSConsumerPrimaryQueueName		= getenv('env_SQSConsumerPrimaryQueueName');

$env_SQSConsumerDeadLetterQueueName		= getenv('env_SQSConsumerDeadLetterQueueName');
$env_SQSConsumerQueueURL				= getenv('env_SQSConsumerQueueURL');
$env_SQSConsumerAWSKey					= getenv('env_SQSConsumerAWSKey');
$env_SQSConsumerAWSSecret				= getenv('env_SQSConsumerAWSSecret');
$env_IronMQDeliveryPrimaryQueueName		= getenv('env_IronMQDeliveryPrimaryQueueName');
$env_IronMQDeliveryDeadLetterQueueName	= getenv('env_IronMQDeliveryDeadLetterQueueName');
$env_IronMQConsumerPrimaryQueueName		= getenv('env_IronMQConsumerPrimaryQueueName');
$env_IronMQConsumerDeadLetterQueueName	= getenv('env_IronMQConsumerDeadLetterQueueName');
$env_IronMQConfig						= getenv('env_IronMQConfig');
$env_RabbitMQConfig						= getenv('env_RabbitMQConfig');
$env_RabbitMQConfigUseSSL				= (getenv('env_RabbitMQConfigUseSSL') === 'true');
$env_RabbitMQConsumerPrimaryQueueName	= getenv('env_RabbitMQConsumerPrimaryQueueName');
$env_RabbitMQConsumerSlackNotificationQueueName	= getenv('env_RabbitMQConsumerSlackNotificationQueueName');
$env_RabbitMQConsumerPushAndSmsQueueName	= getenv('env_RabbitMQConsumerPushAndSmsQueueName');
$env_RabbitMQConsumerEmailQueueName		= getenv('env_RabbitMQConsumerEmailQueueName');
$env_RabbitMQConsumerFlightQueueName	= getenv('env_RabbitMQConsumerFlightQueueName');


$env_RabbitMQConsumerPrimaryMidPriorityAsynchQueueName = getenv('env_RabbitMQConsumerPrimaryMidPriorityAsynchQueueName');
$env_RabbitMQAPIUrl = getenv('env_RabbitMQAPIUrl');
$env_RabbitMQDeliveryPrimaryQueueName = getenv('env_RabbitMQDeliveryPrimaryQueueName');
$env_RabbitMQDeliveryDeadLetterQueueName = getenv('env_RabbitMQDeliveryDeadLetterQueueName');


$env_RabbitMQConsumerPrimaryMidPriorityAsynchQueueName = getenv('env_RabbitMQConsumerPrimaryMidPriorityAsynchQueueName');
$env_RabbitMQConsumerDataEdit = getenv('env_RabbitMQConsumerDataEdit');
$env_RabbitMQConsumerRetailersEdit = getenv('env_RabbitMQConsumerRetailersEdit');
$env_RabbitMQConsumerCouponsEdit = getenv('env_RabbitMQConsumerCouponsEdit');


$env_workerQueueConsumerDeadLetterLPInSecs = getenv('env_workerQueueConsumerDeadLetterLPInSecs');
$env_workerQueueConsumerLPInSecs = getenv('env_workerQueueConsumerLPInSecs');
$env_AuthyAPIKey = getenv('env_AuthyAPIKey');

$env_OneSignalAppId = getenv('env_OneSignalAppId');
$env_OneSignalRestKey = getenv('env_OneSignalRestKey');
$env_OneSignalTestType = getenv('env_OneSignalTestType');
$env_OneSignalTemplateIds = getenv('env_OneSignalTemplateIds');

$env_TabletAppDefaultPingIntervalInSecs = getenv('env_TabletAppDefaultPingIntervalInSecs');

$env_DefaultOrderTimeWindowBeforeFlight = getenv('env_DefaultOrderTimeWindowBeforeFlight');
$env_DeliveryTimeWindowIncrements = getenv('env_DeliveryTimeWindowIncrements');
$env_MinTimeWindowBeforeFlight = getenv('env_MinTimeWindowBeforeFlight');
$env_mysqlLogsDataBaseHost = getenv('env_mysqlLogsDataBaseHost');
$env_mysqlLogsDataBaseName = getenv('env_mysqlLogsDataBaseName');
$env_mysqlLogsDataBaseUser = getenv('env_mysqlLogsDataBaseUser');
$env_mysqlLogsDataBasePassword = getenv('env_mysqlLogsDataBasePassword');
$env_mysqlLogsDataBasePort = getenv('env_mysqlLogsDataBasePort');

$env_mysqlSessionsDataBaseHost = getenv('env_mysqlSessionsDataBaseHost');
$env_mysqlSessionsDataBaseName = getenv('env_mysqlSessionsDataBaseName');
$env_mysqlSessionsDataBaseUser = getenv('env_mysqlSessionsDataBaseUser');
$env_mysqlSessionsDataBasePassword = getenv('env_mysqlSessionsDataBasePassword');
$env_mysqlSessionsDataBasePort = getenv('env_mysqlSessionsDataBasePort');

$env_mysqlDataDataBaseHost = getenv('env_mysqlDataDataBaseHost');
$env_mysqlDataDataBaseName = getenv('env_mysqlDataDataBaseName');
$env_mysqlDataDataBaseUser = getenv('env_mysqlDataDataBaseUser');
$env_mysqlDataDataBasePassword = getenv('env_mysqlDataDataBasePassword');
$env_mysqlDataDataBasePort = getenv('env_mysqlDataDataBasePort');


$env_AllowCreditsForPickup = (getenv('env_AllowCreditsForPickup') === 'true');
$env_AllowCreditsForDelivery = (getenv('env_AllowCreditsForDelivery') === 'true');
$env_APIKeyMaxUsage = intval(getenv('env_APIKeyMaxUsage'));

$env_AllowDuplicateAccounts = (getenv('env_AllowDuplicateAccounts') === 'true');
$env_AllowDuplicatePhoneRegistration = (getenv('env_AllowDuplicatePhoneRegistration') === 'true');
$env_AllowDuplicatePaymentMethod = (getenv('env_AllowDuplicatePaymentMethod') === 'true');
$env_AllowDuplicateCouponUsageByDevice = (getenv('env_AllowDuplicateCouponUsageByDevice') === 'true');
$env_LogQueueTransactionsToDB = (getenv('env_LogQueueTransactionsToDB') === 'true');
$env_BlockDevicesAtRegistration = (getenv('env_BlockDevicesAtRegistration') === 'true');
$env_AllowDuplicateAccountsForReferral = (getenv('env_AllowDuplicateAccountsForReferral') === 'true');
$env_bufferBeforeOrderTimeInSecondsRange = intval(getenv('env_bufferBeforeOrderTimeInSecondsRange'));
$env_bufferForPrepTimeInSeconds = intval(getenv('env_bufferForPrepTimeInSeconds'));
$env_bufferForDeliveryTimeInSeconds = intval(getenv('env_bufferForDeliveryTimeInSeconds'));
$env_defaultDeliveryWalkTimeInSeconds = intval(getenv('env_defaultDeliveryWalkTimeInSeconds'));
$env_MinVersionServiceFeeiOS = getenv('env_MinVersionServiceFeeiOS');
$env_MinVersionServiceFeeAndroid = getenv('env_MinVersionServiceFeeAndroid');
$env_MinVersionServiceFeeWeb = getenv('env_MinVersionServiceFeeWeb');
$env_ServiceFeePCT = floatval(getenv('env_ServiceFeePCT'));
$env_AirportEmployeeDiscountEnabled = (getenv('env_AirportEmployeeDiscountEnabled') === 'true');
$env_MilitaryDiscountEnabled = (getenv('env_MilitaryDiscountEnabled') === 'true');
$env_UserReferralWelcomeImageFileName = getenv('env_UserReferralWelcomeImageFileName');
$env_UserReferralOfferInCents = intval(getenv('env_UserReferralOfferInCents'));
$env_UserReferralRewardInCents = intval(getenv('env_UserReferralRewardInCents'));
$env_UserReferralRewardEnabled = (getenv('env_UserReferralRewardEnabled') === 'true');
$env_UserReferralMinSpendInCentsForReward = intval(getenv('env_UserReferralMinSpendInCentsForReward'));
$env_UserReferralMinSpendInCentsForOfferUse = intval(getenv('env_UserReferralMinSpendInCentsForOfferUse'));
$env_UserReferralRulesLink = getenv('env_UserReferralRulesLink');
$env_UserReferralRewardExpireInSeconds = intval(getenv('env_UserReferralRewardExpireInSeconds'));

$env_UserReferralApplyCouponRestriction = (getenv('env_UserReferralApplyCouponRestriction') === 'true');
$env_UserReferralWaitInSecsBeforeAward = intval(getenv('env_UserReferralWaitInSecsBeforeAward'));
$env_IPStackKey = getenv('env_IPStackKey');
$env_LogUserActions = (getenv('env_LogUserActions') === 'true');

$env_LogUserActionsInSlack = (getenv('env_LogUserActionsInSlack') === 'true');
$env_LogUserActionsInSlackForAllLocations = (getenv('env_LogUserActionsInSlackForAllLocations') === 'true');
$env_SherpaWWWURL = getenv('env_SherpaWWWURL');
$env_HerokuSystemPath = getenv('env_HerokuSystemPath');
$env_HMSHostPassKey_SubscriptionKey_Menu = getenv('env_HMSHostPassKey_SubscriptionKey_Menu');
$env_HMSHostUsername_Menu = getenv('env_HMSHostUsername_Menu');
$env_HMSHostPassKey_Menu = getenv('env_HMSHostPassKey_Menu');

$env_HMSHostPassKey_SubscriptionKey_Ping = getenv('env_HMSHostPassKey_SubscriptionKey_Ping');
$env_HMSHostUsername_Ping = getenv('env_HMSHostUsername_Ping');
$env_HMSHostPassKey_Ping = getenv('env_HMSHostPassKey_Ping');
$env_HMSHostPassKey_SubscriptionKey_Order = getenv('env_HMSHostPassKey_SubscriptionKey_Order');
$env_HMSHostUsername_Order = getenv('env_HMSHostUsername_Order');
$env_HMSHostPassKey_Order = getenv('env_HMSHostPassKey_Order');
$env_HMSHostURL = getenv('env_HMSHostURL');
$env_HMSHostClientID = getenv('env_HMSHostClientID');
$env_RemoveSpecialCharsFromItemComment = (getenv('env_RemoveSpecialCharsFromItemComment') === 'true');
$env_deliveryUpAutoNotification = (getenv('env_deliveryUpAutoNotification') === 'true');

$env_deliveryUpAutoNotificationLookbackTimeInMins = intval(getenv('env_deliveryUpAutoNotificationLookbackTimeInMins'));
$env_OrderRatingRequestsMaxPerUser = intval(getenv('env_OrderRatingRequestsMaxPerUser'));
$env_OrderRatingRequestsMaxPerUserPerDay = intval(getenv('env_OrderRatingRequestsMaxPerUserPerDay'));
$env_UserSMSNotificationsEnabledOnSignup = (getenv('env_UserSMSNotificationsEnabledOnSignup') === 'true');
$env_ZendeskUsername = getenv('env_ZendeskUsername');
$env_ZendeskAPIToken = getenv('env_ZendeskAPIToken');
$env_ZendeskCreateTickets = (getenv('env_ZendeskCreateTickets') === 'true');
$env_S3LogsAccessKey = getenv('env_S3LogsAccessKey');
$env_S3LogsAccessSecret = getenv('env_S3LogsAccessSecret');
$env_S3LogsRegion = getenv('env_S3LogsRegion');
$env_S3LogsBucketName = getenv('env_S3LogsBucketName');

$env_fullfillmentETALowInSecs = intval(getenv('env_fullfillmentETALowInSecs'));
$env_fullfillmentETAHighInSecs = intval(getenv('env_fullfillmentETAHighInSecs'));

$env_fullfillmentETALowInSecsForScheduled = intval(getenv('env_fullfillmentETALowInSecsForScheduled'));
$env_fullfillmentETAHighInSecsForScheduled = intval(getenv('env_fullfillmentETAHighInSecsForScheduled'));

$env_NoPingCheckForDeliveryUser = (getenv('env_NoPingCheckForDeliveryUser') === 'true');
// Not used
$env_LyftPromoEnabled = (getenv('env_LyftPromoEnabled') === 'true');
$env_ClearPromoEnabled = (getenv('env_ClearPromoEnabled') === 'true');


$env_invoice_cc_email = (string)(getenv('env_invoice_cc_email'));

$env_data_edit_notification_slack_webhook_url = (string)(getenv('env_data_edit_notification_slack_webhook_url'));
$env_retailer_edit_notification_slack_webhook_url = (string)(getenv('env_retailer_edit_notification_slack_webhook_url'));
$env_coupons_edit_notification_slack_webhook_url = (string)(getenv('env_coupons_edit_notification_slack_webhook_url'));


$env_RabbitMQConsumerDataEdit = (string)(getenv('env_RabbitMQConsumerDataEdit'));
$env_MenuUploadAWSS3AccessId = (string)(getenv('env_MenuUploadAWSS3AccessId'));
$env_MenuUploadAWSS3Secret = (string)(getenv('env_MenuUploadAWSS3Secret'));
$env_MenuUploadAWSS3Region = (string)(getenv('env_MenuUploadAWSS3Region'));
$env_MenuUploadAWSS3Bucket = (string)(getenv('env_MenuUploadAWSS3Bucket'));

$env_SessionTokenHashSalt = (string)(getenv('env_SessionTokenHashSalt'));


$env_DeliveryAppDefaultPingIntervalInSecs = (string)(getenv('env_DeliveryAppDefaultPingIntervalInSecs'));
$env_DeliveryAppDefaultNotificationSoundUrl = (string)(getenv('env_DeliveryAppDefaultNotificationSoundUrl'));
$env_DeliveryAppDefaultVibrateUsage = (string)(getenv('env_DeliveryAppDefaultVibrateUsage'));
$env_DeliveryBatteryCheckIntervalInSecs = (string)(getenv('env_DeliveryBatteryCheckIntervalInSecs'));
$env_DeliveryRestAPIKeySalt = (string)(getenv('env_DeliveryRestAPIKeySalt'));



$env_GrabSlackErrorChannelUrl = (string)getenv('env_GrabSlackErrorChannelUrl');

// Initialize Config
$__CONFIG = array();

define('AES_256_CBC', 'aes-256-cbc');

// Include files
// JMD
require $dirpath . 'lib/class_sqs.php';
require $dirpath . 'lib/class_ironmq.php';
require $dirpath . 'lib/class_rabbitmq.php';

require $dirpath . 'lib/class_workerqueue.php';
require $dirpath . 'lib/class_flight.php';

require $dirpath . 'lib/class_slack.php';

require $dirpath . 'lib/class_order.php';

require $dirpath . 'lib/class_retaileritem.php';
require $dirpath . 'lib/class_cognitoclient.php';
require $dirpath . 'lib/functions.php';

require $dirpath . 'lib/functions_errorhandling.php';
require $dirpath . 'lib/functions_parse.php';
// JMD

require $dirpath . 'lib/functions_apiauth.php';
require $dirpath . 'lib/functions_userauth.php';
require $dirpath . 'lib/functions_cache.php';
require $dirpath . 'lib/functions_s3.php';
require $dirpath . 'lib/functions_cognito.php';
// JMD
require $dirpath . 'lib/functions_looper.php';
require $dirpath . 'lib/functions_orders.php';
require $dirpath . 'lib/functions_partner.php';
require $dirpath . 'lib/functions_directions.php';
require $dirpath . 'lib/functions_flight.php';
require $dirpath . 'lib/functions_analytics.php';
require $dirpath . 'lib/gatemaps.inc.php';
require $dirpath . 'vendor/autoload.php';
require $dirpath . 'lib/slim.php';
require $dirpath . 'lib/variablestream.php';
require $dirpath . 'lib/initiate.parse.php';
require $dirpath . 'lib/initiate.redis.php';
require $dirpath . 'lib/tripit.php';
require $dirpath . 'lib/GoogleCloudPrint.php';
require $dirpath . 'lib/class_extPOSPartnerHMSHost.php';
require $dirpath . 'scheduled/_send_user_communication.php';
require $dirpath . 'lib/functions_loader.php';
require $dirpath . 'lib/function_menuloader.php';

require $dirpath . 'lib/factories.php';

// If non HTTPS call, fail
// Only check when being called in Heroku
if (!isSSL()
    && strcasecmp($env_InHerokuRun, "Y") == 0
    && !defined("WORKER")
    && !defined("WEBHOOK")
) {

    json_error("AS_022", "", "Non-SSL call made", 1);
}

// JMD
// Default error handlder
// require $dirpath . '/lib/errorhandlers.php';

// $app = new \Slim\Slim Route Cache Key, generated at run-time
// Slim Route Cache Key, overriden name so it can be called again
$GLOBALS['cacheSlimRouteKey'] = $GLOBALS['cacheSlimRouteNamedKey'] = '';

// Use App Salt as default
$GLOBALS['useWebAPISaltKey'] = false;

// reset user
$GLOBALS['user'] = [];
$GLOBALS['userPhones'] = [];

// reset worker queue connection
$GLOBALS['workerQueue'] = '';

// initialize
$GLOBALS['teminalGateMapArray'] = [];
$GLOBALS['airlinesArray'] = [];
$GLOBALS['airportsArray'] = [];

$GLOBALS['__DISTANCEMETRICS__'] = [];
$GLOBALS['__DIRECTIONSSUMMARY__'] = [];

$GLOBALS['__PINGRETAILERS_'] = [];
$GLOBALS['__PINGDUALCONFIGRETAILERS_'] = [];
$GLOBALS['__PING_SLACKDELIVERY_'] = [];
// JMD
$GLOBALS['__TIMEZONE_SHORTCODES__'] = [];
$GLOBALS["__RETAILERINFO_"] = [];
// AWS connectors
$GLOBALS['partnerPageName'] = '';
$GLOBALS['partner'] = '';
$GLOBALS['s3_client'] = [];
$GLOBALS['cognito_client'] = '';
$GLOBALS['menu_loader_S3_log_failed'] = false;

$GLOBALS['menu_loader_S3_log_backlog'] = "";
$GLOBALS['menu_loader_S3_log_backlog_counter'] = 0;
$GLOBALS['tabletOpenCloseLevels'] = [1 => "TABLET", 2 => "DASHBOARD", 3 => "SYSTEM"];

?>
