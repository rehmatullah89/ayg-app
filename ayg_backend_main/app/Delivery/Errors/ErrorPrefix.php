<?php

namespace App\Delivery\Errors;

class ErrorPrefix
{
    const APPLICATION_CONSUMER = 10;
    const APPLICATION_DELIVERY = 11;
    const APPLICATION_TABLET = 12;

    const CONTROLLER_MIDDLEWARE = 10;

    const CONTROLLER_ORDER = 11;
    const CONTROLLER_USER = 12;

    const CONTROLLER_RETAILER = 13;
    const CONTROLLER_PAYMENT = 14;
    const CONTROLLER_INTEGRATION = 15;
    const CONTROLLER_INFO = 16;
    const CONTROLLER_OPS = 17;
    const CONTROLLER_WEB = 18;
    const CONTROLLER_TRIP = 19;
}
