<?php

require 'dirpath.php';
require $dirpath . 'lib/initiate.inc.php';
require $dirpath . 'lib/errorhandlers.php';

use Parse\ParseClient;
use Parse\ParseQuery;
use Parse\ParseObject;
use Parse\ParseUser;
use Parse\ParseFile;

use Httpful\Request;

$GLOBALS['useWebAPISaltKey'] = true;


// Save Contact form from the Website
$app->get('/contact/a/:apikey/e/:epoch/u/:sessionToken/name/:name/email/:email/comments/:comments/deviceId/:deviceId',
    'apiAuthForWebAPI',
    function ($apikey, $epoch, $sessionToken, $name, $email, $comments, $deviceId) {

        // Save to Database
        $email = sanitizeEmail($email);

        $contactForm = new ParseObject("ContactSubmission");
        $contactForm->set('name', $name);
        $contactForm->set('email', $email);
        $contactForm->set('comments', $comments);
        $contactForm->set('deviceId', $deviceId);
        $contactForm->set('source', 'Website');
        $contactForm->save();

        // Slack it
        // Post to wh-contact-form
        $slack = new SlackMessage($GLOBALS['env_SlackWH_contactForm'], 'env_SlackWH_contactForm');
        $slack->setText("Website Contact" . " (" . date("M j, g:i a", time()) . ")");

        $attachment = $slack->addAttachment();
        $attachment->addField("ENV:", $GLOBALS['env_EnvironmentDisplayCode'], false);
        $attachment->addField("Name:", $name, true);
        $attachment->addField("Email:", $email, true);
        $attachment->addField("Comments:", $comments, false);
        // $attachment->addField("IP:", $deviceId, false);

        try {

            $slack->send();
        } catch (Exception $ex) {

            json_error("AS_1054", "",
                "Slack post failed informing Contact Form! Post Array=" . json_encode($attachment->getAttachment()) . " -- " . $ex->getMessage(),
                1);
        }

        // Create Zendesk ticket
        $messageParameters = [
            "subject" => "Website Contact",
            "body" => $comments,
            "customerName" => $name,
            "customerEmail" => $email
        ];
        $zenDeskTicketId = postTicketToZendesk($messageParameters, $contactForm->getObjectId());
        $contactForm->set("zendeskTicketId", strval($zenDeskTicketId));
        $contactForm->save();
    });

// Save Beta signup from the website
$app->get('/signup/a/:apikey/e/:epoch/u/:sessionToken/email/:email/deviceId/:deviceId', 'apiAuthForWebAPI',
    function ($apikey, $epoch, $sessionToken, $email, $deviceId) {

        $email = sanitizeEmail($email);

        // Find previous beta request for the user
        $query = new ParseQuery("BetaInvites");
        $query->equalTo("userEmail", trim($email));
        $objParseQueryBetaInvites = $query->find();

        $postToSlack = true;

        // If Invite is NOT found, let caller know
        if (count_like_php5($objParseQueryBetaInvites) < 1) {

            $objParseBetaRequests = new ParseObject("BetaInvites");
            $objParseBetaRequests->set("userEmail", $email);
            $objParseBetaRequests->set("source", "Website");
            $objParseBetaRequests->set("isActive", false);
            $objParseBetaRequests->set("deviceId", trim($deviceId));
            $objParseBetaRequests->set("remoteIPAddr", trim($deviceId));
            $objParseBetaRequests->save();
        } else {
            if ($objParseQueryBetaInvites[0]->get("isActive") == false
                && strcmp($objParseQueryBetaInvites[0]->get("remoteIPAddr"), $deviceId) != 0
            ) {

                $objParseQueryBetaInvites[0]->set("remoteIPAddr", $deviceId);
                $objParseQueryBetaInvites[0]->save();
            } else {

                $postToSlack = false;
            }
        }

        if ($postToSlack == true) {

            // Post to wh-contact-form
            $slack = new SlackMessage($GLOBALS['env_SlackWH_contactForm'], 'env_SlackWH_contactForm');
            $slack->setText("Beta Signup" . " (" . date("M j, g:i a", time()) . ")");

            $attachment = $slack->addAttachment();
            $attachment->addField("ENV:", $GLOBALS['env_EnvironmentDisplayCode'], false);
            $attachment->addField("Source:", "website", true);
            $attachment->addField("Email:", $email, true);
            // $attachment->addField("IP:", $deviceId, false);

            try {

                $slack->send();
            } catch (Exception $ex) {

                json_error("AS_1054", "",
                    "Slack post failed informing Web signup Form! Post Array=" . json_encode($attachment->getAttachment()) . " -- " . $ex->getMessage(),
                    1);
            }
        } else {

            // Log event, no exit and no response
            json_error("AS_1054", "", "Beta signup from website failed", 2, 1);
        }
    });

// Add Web Token to saved list
$app->get('/token/add/a/:apikey/e/:epoch/u/:sessionToken/token/:token', 'apiAuthForWebAPI',
    function ($apikey, $epoch, $sessionToken, $token) {

        setCache('__WEBTOKEN__' . $token, 1, 0);
    });

// Check Web Token from saved list
$app->get('/token/check/a/:apikey/e/:epoch/u/:sessionToken/token/:token', 'apiAuthForWebAPI',
    function ($apikey, $epoch, $sessionToken, $token) {

        if (doesCacheExist('__WEBTOKEN__' . $token)) {

            $responseArray = array("used" => 1);
        } else {

            $responseArray = array("used" => 0);
        }

        json_echo(
            json_encode($responseArray)
        );
    });

// Log download
$app->get('/logdownload/a/:apikey/e/:epoch/u/:sessionToken/referralCode/:referralCode/appPlatform/:appPlatform/deviceId/:deviceId',
    'apiAuthForWebAPI',
    function ($apikey, $epoch, $sessionToken, $referralCode, $appPlatform, $deviceId) {

        // Save to Parse Table
        $zWebsiteDownloads = new ParseObject("zWebsiteDownloads");
        $zWebsiteDownloads->set("referralCode", $referralCode);
        $zWebsiteDownloads->set("IPAddr", $deviceId);
        $zWebsiteDownloads->set("appPlatform", $appPlatform);
        $zWebsiteDownloads->save();

        // Create a queue request
        try {

            //$workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueConsumerName']);
            $workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueMidPriorityAsynchConsumerName']);
            $workerQueue->sendMessage(
                array(
                    "action" => "log_website_download",
                    "content" =>
                        array(
                            "objectId" => $zWebsiteDownloads->getObjectId()
                        )
                ),
                60
            );
        } catch (Exception $ex) {

            json_error("AS_3021", "", "Log Download failed! " . $referralCode . "-" . $deviceId . "-" . $appPlatform,
                1);
        }

        exit;
    });


// Log rating click
$app->get('/logratingrequestclick/a/:apikey/e/:epoch/u/:sessionToken/ratingRequestId/:ratingRequestId/clickSource/:clickSource/deviceId/:deviceId',
    'apiAuthForWebAPI',
    function ($apikey, $epoch, $sessionToken, $ratingRequestId, $clickSource, $deviceId) {

        $orderRatingRequests = parseExecuteQuery(["objectId" => $ratingRequestId], "OrderRatingRequests", "", "",
            ["userDevice"], 1);

        if (count_like_php5($orderRatingRequests) > 0) {

            // Create a queue request
            try {

                // Save to Parse Table
                $zAppRatingRequestClicks = new ParseObject("zAppRatingRequestClicks");
                $zAppRatingRequestClicks->set("orderRatingRequest", $orderRatingRequests);
                $zAppRatingRequestClicks->set("IPAddr", $deviceId);
                $zAppRatingRequestClicks->set("clickSource", $clickSource);
                $zAppRatingRequestClicks->save();

                //$workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueConsumerName']);
                $workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueMidPriorityAsynchConsumerName']);
                $workerQueue->sendMessage(
                    array(
                        "action" => "log_website_rating_click",
                        "content" =>
                            array(
                                "objectId" => $zAppRatingRequestClicks->getObjectId()
                            )
                    ),
                    60
                );
            } catch (Exception $ex) {

                json_error("AS_3021", "", "Log rating failed! " . $ratingRequestId . "-" . $deviceId, 1);
            }

            if ($orderRatingRequests->get('userDevice')->get('isIos') == true) {

                $responseArray = array("appPlatform" => 'iOS');
            } else {
                if ($orderRatingRequests->get('userDevice')->get('isAndroid') == true) {

                    $responseArray = array("appPlatform" => 'Android');
                } else {
                    $responseArray = array("appPlatform" => 'Web');
                }
            }
        } else {

            $responseArray = array("appPlatform" => '');
        }

        json_echo(
            json_encode($responseArray)
        );
    });
$app->notFound(function () {

    json_error("AS_005", "", "Incorrect API Call.");
});

$app->run();

?>
