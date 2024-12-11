<?php
namespace App\Tablet\Services;

use App\Tablet\Dtos\SlackMessageAttachment;
use App\Tablet\Entities\OrderTabletHelpRequest;
use SlackMessage;


/**
 * Class SlackContactService
 * @package App\Tablet\Services
 */
class SlackOrderHelpRequestService extends SlackService
{
    public function __construct($url, $identifier)
    {
        parent::__construct($url, $identifier);
    }

    /**
     * prepare slack message for order help request based on OrderTabletHelpRequest Entity and sends message
     *
     * @param OrderTabletHelpRequest $orderTabletHelpRequest
     * @return bool
     */
    public function sendSlackMessage(OrderTabletHelpRequest $orderTabletHelpRequest)
    {
        $airportIataCode = $orderTabletHelpRequest->getOrder()->getRetailer()->getAirportIataCode();

        $customer = $orderTabletHelpRequest->getOrder()->getUser()->getFirstName() . ' ' . $orderTabletHelpRequest->getOrder()->getUser()->getLastName();
        $retailer = $orderTabletHelpRequest->getOrder()->getRetailer()->getRetailerName().
            ' ('.$orderTabletHelpRequest->getOrder()->getRetailer()->getAirportIataCode().
            ' '.$orderTabletHelpRequest->getOrder()->getRetailer()->getLocation()->getGateDisplayName().')';
        $orderIdInternal=$orderTabletHelpRequest->getOrder()->getId();
        $orderIdUser=$orderTabletHelpRequest->getOrder()->getOrderSequenceId();
        $comments=$orderTabletHelpRequest->getContent();


        $slackMessage = createOrderHelpChannelSlackMessageByAirportIataCode($airportIataCode);
        $slackMessage->setText($customer . " (" . date("M j, g:i a", time()) . ")");
        $attachment = $slackMessage->addAttachment();
        $attachment->addField("Customer:", $customer, false);
        $attachment->addField("Retailer:", $retailer, false);
        $attachment->addField("Order Id (Internal):", $orderIdInternal, true);
        $attachment->addField("Order Id (User):", $orderIdUser, true);
        $attachment->addField("Comments:", $comments, false);
        $slackMessage->send();
    }
}
