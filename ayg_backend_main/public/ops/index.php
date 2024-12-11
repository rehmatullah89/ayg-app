<?php
$allowedOrigins = [
	"http://ayg-deb.test",
	"https://ayg.ssasoft.com",
	"http://ec2-18-116-237-65.us-east-2.compute.amazonaws.com",
    "http://ec2-18-190-155-186.us-east-2.compute.amazonaws.com", // test
    "https://order.atyourgate.com", // prod
];

if (isset($_SERVER["HTTP_REFERER"]) && in_array(trim($_SERVER["HTTP_REFERER"],'/'), $allowedOrigins)) {
	header("Access-Control-Allow-Origin: " . trim($_SERVER["HTTP_REFERER"],'/'));
}

require 'dirpath.php';
require $dirpath . 'lib/initiate.inc.php';
require $dirpath . 'lib/errorhandlers.php';

use Parse\ParseClient;
use Parse\ParseQuery;
use Parse\ParseObject;
use Parse\ParseUser;
use Parse\ParseFile;
use Httpful\Request;

// Save Contact form from the App
$app->post('/contact/a/:apikey/e/:epoch/u/:sessionToken',
    \App\Consumer\Middleware\UserAuthMiddleware::class . '::apiAuthWithoutActiveAccess',
	//'apiAuthWithoutActiveAccess',
	function ($apikey, $epoch, $sessionToken) use ($app) {

	$postVars = array();
	
	$postVars['deviceId'] = $deviceId = $app->request()->post('deviceId');
	$postVars['comments'] = $comments = $app->request()->post('comments');

	// Optional
	$postVars['allowContact'] = $allowContact = $app->request()->post('allowContact');
	$postVars['contactName'] = $contactName = $app->request()->post('contactName');
	$postVars['contactEmail'] = $contactEmail = $app->request()->post('contactEmail');

	// Minimum required input
	if(empty($deviceId)
		|| empty($comments)) {
		
		json_error("AS_005", "", "Incorrect API Call. PostVars = " . var_dump_ob($postVars));
	}

	$contactArray = [];
	if(!empty($contactName)
		|| !empty($contactEmail)) {

		$contactArray = ["contactName" => $contactName, "contactEmail" => $contactEmail];
	}

	contactFormSubmission($deviceId, $comments, $allowContact, $contactArray);
});

// Bug report
$app->post('/bug/a/:apikey/e/:epoch/u/:sessionToken', \App\Consumer\Middleware\UserAuthMiddleware::class . '::apiAuth',
	function ($apikey, $epoch, $sessionToken) use ($app) {

	$postVars = array();
	
	// $postVars['apikey'] = $apikey = $app->request()->post('a');
	// $postVars['epoch'] = $epoch = $app->request()->post('e');
	// $postVars['sessionToken'] = $sessionToken = $app->request()->post('u');
	$postVars['deviceId'] = $deviceId = $app->request()->post('deviceId');
	$postVars['deviceType'] = $deviceType = $app->request()->post('deviceType');
	$postVars['description'] = $description = $app->request()->post('description');
	$postVars['buildVersion'] = $buildVersion = $app->request()->post('buildVersion');
	$postVars['iOSVersion'] = $iOSVersion = $app->request()->post('iOSVersion');
	$postVars['bugSeverity'] = $bugSeverity = $app->request()->post('bugSeverity');
	$postVars['bugCategory'] = $bugCategory = $app->request()->post('bugCategory');
	$postVars['appVersion'] = $appVersion = $app->request()->post('appVersion');
	$postVars['screenshot'] = $screenshot = $app->request()->post('screenshot');
	
	if(empty($deviceId)
		|| empty($deviceType)
		|| empty($description)
		|| (empty($buildVersion) && $buildVersion !== '0')
		|| empty($iOSVersion)
		|| empty($bugSeverity)
		|| empty($bugCategory)
		|| empty($appVersion)
		|| empty($screenshot)) {
		
		json_error("AS_005", "", "Incorrect API Call. PostVars = " . var_dump_ob($postVars));
	}
	
	// Register Stream wrapper
	stream_wrapper_register("var", "VariableStream") or json_error("AS_5101", "", "Stream Wrapper registry failed", 1);
	if(!check_base64_pngimage($screenshot)) {
	
		json_error("AS_5100", "", "Provided Bug screenshot is not a valid image.", 1);
	}
	
	$screenshot = base64_decode($screenshot);
	
	// Save Bug
	$bugReports = new ParseObject("BugReports");
	$bugReports->set('user', $GLOBALS['user']);
	$bugReports->set('deviceId', $deviceId);
	$bugReports->set('bugDescription', sanitize($description));
	$bugReports->set('buildVersion', $buildVersion);
	$bugReports->set('iOSVersion', $iOSVersion);
	$bugReports->set('deviceType', $deviceType);
	$bugReports->set('bugSeverity', intval($bugSeverity));
	$bugReports->set('bugCategory', $bugCategory);
	$bugReports->set('appVersion', $appVersion);
	$bugReports->save();

	// Upload Bug Image
	// $imageNew = ParseFile::createFromData($screenshot, "bugImage.png");
	// $imageNew->save();
	// $bugImageURL = cleanURL($imageNew->getURL());

	// S3 Upload Bug Image
	$s3_client = getS3ClientObject();
	$keyWithFolderPath = getS3KeyPath_ImagesUserSubmittedBug() . '/' . $bugReports->getObjectId() . '.png';
	$bugImageURL = S3UploadFileWithContents($s3_client, $GLOBALS['env_S3BucketName'], $keyWithFolderPath, $screenshot, true);

	if(is_array($bugImageURL)) {

		json_error($bugImageURL["error_code"], "", $bugImageURL["error_message_log"] . " Bug Image save failed", 1, 1);
	}

	// Update bug image name
	$bugReports->set('bugImageURL', $bugReports->getObjectId() . '.png');
	$bugReports->save();
	
	// Slack it
	$customerName = $GLOBALS['user']->get('firstName') . ' ' . $GLOBALS['user']->get('lastName');
	$submissionDateTime = date("M j, g:i a", time());

	$slack = new SlackMessage($GLOBALS['env_SlackWH_bugReports'], 'env_SlackWH_bugReports');
	$slack->setText($customerName . " (" . $submissionDateTime . ")");
	
	$attachment = $slack->addAttachment();

	if(intval($bugSeverity) == 1) {

		$attachment->setColorRejected();
	}
	else if(intval($bugSeverity) == 2) {

		$attachment->setColorNew();
	}
	else {

		$attachment->setColorAccepted();
	}
	
	$attachment->addField("v:", "iOS - v" . $appVersion, true);
	$attachment->addField("Sev:", $bugSeverity, true);
	$attachment->addField("Description:", $description, false);
	$attachment->addField("When:", $submissionDateTime, false);
	$attachment->addField("By:", $customerName, true);
	$attachment->addField("Category:", $bugCategory, true);
	$attachment->setAttribute("image_url", $bugImageURL);
	
	try {
		
		// Post to order help channel
		$slack->send();
	}
	catch (Exception $ex) {
		
		json_error("AS_1054", "", "Slack post failed bug report! Post Array=" . json_encode($attachment->getAttachment()) ." -- " . $ex->getMessage(), 1, 1);
	}

	$responseArray = array("saved" => "1");
	
	json_echo(
		json_encode($responseArray)
	);
});

// Get Min App version required for API
$app->get('/getMinAppVersion/a/:apikey/e/:epoch/u/:sessionToken', \App\Consumer\Middleware\UserAuthMiddleware::class . '::apiAuth',
	function ($apikey, $epoch, $sessionToken) {

	$minAppVersionReqForAPI = getConfigValue("minAppVersionReqForAPI");
	
	// Return its object id
	$responseArray = array("minAppVersionReqForAPI" => $minAppVersionReqForAPI);
	
	json_echo(
		json_encode($responseArray)
	);
});

$app->notFound(function () {
	
	json_error("AS_005", "", "Incorrect API Call.");
});

$app->run();

function contactFormSubmission($deviceId, $comments, $allowContact=1, $userInformation=array()) {

	$allowContact = intval($allowContact);
	
	// Save Client in Parse
	$contactForm = new ParseObject("ContactSubmission");

	if(count_like_php5($userInformation) > 0) {

		$contactForm->set('contactName', $userInformation["contactName"]);
		$contactForm->set('contactEmail', $userInformation["contactEmail"]);
		$customerName = $userInformation["contactName"];

		$messageParameters = ["subject" => "Support Requested", "body" => $comments, "customerName" => $customerName, "customerEmail" => $userInformation["contactEmail"]];
	}
	else {

		$contactForm->set('user', $GLOBALS['user']);
		$customerName = $GLOBALS['user']->get('firstName') . ' ' . $GLOBALS['user']->get('lastName');

		$messageParameters = ["subject" => "", "body" => $comments, "customerName" => 'No Reply Email Provided - ' . $customerName, "customerEmail" => "test+noReplyEmailProvided@airportsherpa.io"];
	}

	$contactForm->set('source', 'App');
	$contactForm->set('deviceId', $deviceId);
	$contactForm->set('comments', $comments);
	$contactForm->set('allowContact', boolval($allowContact));
	$contactForm->set('remoteIPAddr', getenv('HTTP_X_FORWARDED_FOR') . ' ~ ' . getenv('REMOTE_ADDR'));
	
	$contactForm->save();

	// Slack it
	$slack = new SlackMessage($GLOBALS['env_SlackWH_contactForm'], 'env_SlackWH_contactForm');
	$slack->setText("$customerName (" . date("M j, g:i a", time()) . ")");
	
	$attachment = $slack->addAttachment();
	$attachment->addField("ENV:", $GLOBALS['env_EnvironmentDisplayCode'], true);
	$attachment->addField("Comments:", $comments, true);
	$attachment->addField("Allow Contact:", (boolval($allowContact) ? 'Yes' : 'No'), true);
	
	try {
		
		$slack->send();
	}
	catch (Exception $ex) {
		
		json_error("AS_1054", "", "Slack post failed informing Cnntact Form submission! Post Array=" . json_encode($attachment->getAttachment()) ." -- " . $ex->getMessage(), 1, 1);
	}

	// Create Zendesk ticket
	$zenDeskTicketId = postTicketToZendesk($messageParameters, $contactForm->getObjectId());
	$contactForm->set("zendeskTicketId", strval($zenDeskTicketId));
	$contactForm->save();

	// Return its object id
	$responseArray = array("saved" => "1");
	
	json_echo(
		json_encode($responseArray)
	);
}

?>
