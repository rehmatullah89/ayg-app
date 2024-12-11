<?php

require_once 'dirpath.php';
require $dirpath . 'lib/functions_orders.php';
require $dirpath . 'lib/errorhandlers_scheduled.php';

use Parse\ParseClient;
use Parse\ParseQuery;
use Parse\ParseObject;
use Parse\ParseUser;

//deleteOldAPIKeysFromParse( new ParseQuery("APIKeyLog") );

deleteOldTripItSessionsFromParse( new ParseQuery("TripItSessions") );

?>