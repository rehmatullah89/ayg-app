<?php

function createOrderNotificationSlackMessageByAirportIataCode($airportIataCode){
    $airport = getAirportByIataCode($airportIataCode);


    if (empty($airport)) {
        return new SlackMessage($GLOBALS['env_SlackWH_orderNotifications'], 'env_SlackWH_orderNotifications');
    }

    if (!empty($airport->get('slack_order_notifications_webhook_url'))){
        return new SlackMessage($airport->get('slack_order_notifications_webhook_url'), 'env_SlackWH_orderNotifications-'.$airport->get('airportIataCode'));
    }

    // in case there is no webhook set, default one is used
    return new SlackMessage($GLOBALS['env_SlackWH_orderNotifications'], 'env_SlackWH_orderNotifications');
    //throw new ConfigKeyNotFoundException('slack_order_notifications_webhook_url not found for airport '.$airport->get('airportIataCode'));
}

function createOrderNotificationSlackMessage($orderId)
{
    // get airport from order
    $order = parseExecuteQuery(["objectId" => $orderId], "Order", "", "", ["Order.retailer", "Order.retailer.location"], 1);
    $airportIataCode = $order->get('retailer')->fetch()->get('location')->fetch()->get('airportIataCode');
    return createOrderNotificationSlackMessageByAirportIataCode($airportIataCode);
}

function createOrderNotificationSlackMessageBySequenceId($orderSequenceId)
{
    // get airport from order
    $order = parseExecuteQuery(["orderSequenceId" => $orderSequenceId], "Order", "", "", ["Order.retailer", "Order.retailer.location"], 1);
    $airportIataCode = $order->get('retailer')->fetch()->get('location')->fetch()->get('airportIataCode');
    return createOrderNotificationSlackMessageByAirportIataCode($airportIataCode);
}



function createOrderInvPrintDelaySlackMessageByAirportIataCode($airportIataCode){
    $airport = getAirportByIataCode($airportIataCode);

    if (empty($airport)) {
        return new SlackMessage($GLOBALS['env_SlackWH_orderInvPrintDelay'], 'env_SlackWH_orderInvPrintDelay');
    }

    if (!empty($airport->get('orderInvPrintDelaySlackChannel'))){
        return new SlackMessage($airport->get('orderInvPrintDelaySlackChannel'), 'env_SlackWH_orderInvPrintDelay-'.$airport->get('airportIataCode'));
    }

    // in case there is no webhook set, default one is used
    return new SlackMessage($GLOBALS['env_SlackWH_orderInvPrintDelay'], 'env_SlackWH_orderInvPrintDelay');
    //throw new ConfigKeyNotFoundException('slack_order_notifications_webhook_url not found for airport '.$airport->get('airportIataCode'));
}

function createPosPingFailSlackChannelSlackMessageByAirportIataCode($airportIataCode){

    $airport = getAirportByIataCode($airportIataCode);


    if (empty($airport)) {
        return new SlackMessage($GLOBALS['env_SlackWH_posPingFail'], 'env_SlackWH_posPingFail');
    }

    if (!empty($airport->get('posPingFailSlackChannel'))){
        return new SlackMessage($airport->get('posPingFailSlackChannel'), 'env_SlackWH_posPingFail-'.$airport->get('airportIataCode'));
    }

    // in case there is no webhook set, default one is used
    return new SlackMessage($GLOBALS['env_SlackWH_posPingFail'], 'env_SlackWH_posPingFail');
    //throw new ConfigKeyNotFoundException('slack_order_notifications_webhook_url not found for airport '.$airport->get('airportIataCode'));
}

function createOrderHelpChannelSlackMessageByAirportIataCode($airportIataCode){

    $airport = getAirportByIataCode($airportIataCode);


    if (empty($airport)) {
        return new SlackMessage($GLOBALS['env_SlackWH_orderHelp'], 'env_SlackWH_orderHelp');
    }

    if (!empty($airport->get('orderHelpSlackChannel'))){
        return new SlackMessage($airport->get('orderHelpSlackChannel'), 'env_SlackWH_orderHelp-'.$airport->get('airportIataCode'));
    }

    // in case there is no webhook set, default one is used
    return new SlackMessage($GLOBALS['env_SlackWH_orderHelp'], 'env_SlackWH_orderHelp');
    //throw new ConfigKeyNotFoundException('slack_order_notifications_webhook_url not found for airport '.$airport->get('airportIataCode'));
}

function createGrabPartnerErrorSlackMessage(){
    return new SlackMessage($GLOBALS['env_GrabSlackErrorChannelUrl'], 'env_GrabSlackErrorChannelUrl');
}
