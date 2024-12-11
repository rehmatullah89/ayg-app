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

require __DIR__ . '/../../lib/initiate.inc.php';
require __DIR__ . '/../../lib/errorhandlers.php';

use App\Consumer\Controllers\InfoController;
use Parse\ParseClient;
use Parse\ParseQuery;
use Parse\ParseGeoPoint;
use Parse\ParseObject;
use Parse\ParseUser;

use Parse\ParseFile;

use Httpful\Request;

// Airports List
$app->get('/airports/list/a/:apikey/e/:epoch/u/:sessionToken', \App\Consumer\Middleware\UserAuthMiddleware::class . '::apiAuth',
    function ($apikey, $epoch, $sessionToken) {

        // Check if already have cache for this
        getRouteCache();

        $responseArray = getAirportList();

        json_echo(
            setRouteCache([
                "jsonEncodedString" => json_encode($responseArray),
                "expireInSeconds" => $GLOBALS['parseClassAttributes']['Airports']['ttl']
            ])
        );
    });

// Airports Find
$app->get('/airports/find/a/:apikey/e/:epoch/u/:sessionToken/airportIataCode/:airportIataCode', \App\Consumer\Middleware\UserAuthMiddleware::class . '::apiAuth',
    function ($apikey, $epoch, $sessionToken, $airportIataCode) {

        // Check if already have cache for this
        getRouteCache();

        // $objParseQueryAirports = parseExecuteQuery(array("airportIataCode" => $airportIataCode), "Airports");
        $objParseQueryAirports = getAirportByIataCode($airportIataCode);

        if (count_like_php5($objParseQueryAirports) == 0) {

            json_error("AS_5200", "", "Invalid Airport Code provided for Airport Find function - " . $airportIataCode);
        }

        $geoLatLong["lat"] = $objParseQueryAirports->get('geoPointLocation')->getLatitude();
        $geoLatLong["lon"] = $objParseQueryAirports->get('geoPointLocation')->getLongitude();

        // Get weather from cache
        $responseArrayWeather = getAirportWeatherFromCache($airportIataCode, $geoLatLong);

        $responseArray = $objParseQueryAirports->getAllKeys();
        $responseArray["objectId"] = $objParseQueryAirports->getObjectId();
        $responseArray["imageBackground"] = preparePublicS3URL($responseArray["imageBackground"],
            getS3KeyPath_ImagesAirportBackground(), $GLOBALS['env_S3Endpoint']);

        $responseArray["geoPointLocation"] = array(
            "longitude" => $objParseQueryAirports->get('geoPointLocation')->getLongitude(),
            "latitude" => $objParseQueryAirports->get('geoPointLocation')->getLatitude()
        );
        $responseArray["weather"] = $responseArrayWeather;

        json_echo(
            setRouteCache([
                "jsonEncodedString" => json_encode($responseArray),
                "expireInSeconds" => $GLOBALS['parseClassAttributes']['Airports']['ttl']
            ])
        );
    });

// Airport Weather
$app->get('/airports/weather/a/:apikey/e/:epoch/u/:sessionToken/airportIataCode/:airportIataCode', \App\Consumer\Middleware\UserAuthMiddleware::class . '::apiAuth',
    function ($apikey, $epoch, $sessionToken, $airportIataCode) {

        // $objParseQueryAirports = parseExecuteQuery(array("airportIataCode" => $airportIataCode), "Airports");
        $objParseQueryAirports = getAirportByIataCode($airportIataCode);

        if (count_like_php5($objParseQueryAirports) == 0) {

            json_error("AS_5200", "", "Invalid Airport Code provided for Airport Find function - " . $airportIataCode);
        }

        $geoLatLong["lat"] = $objParseQueryAirports->get('geoPointLocation')->getLatitude();
        $geoLatLong["lon"] = $objParseQueryAirports->get('geoPointLocation')->getLongitude();

        // Get weather from cache
        $responseArray["weather"] = getAirportWeatherFromCache($airportIataCode, $geoLatLong);

        json_echo(
            json_encode($responseArray)
        );
    });

// Airports near Geo Location
$app->get('/airports/near/a/:apikey/e/:epoch/u/:sessionToken/latitude/:latitude/longitude/:longitude', \App\Consumer\Middleware\UserAuthMiddleware::class . '::apiAuth',
    function ($apikey, $epoch, $sessionToken, $latitude, $longitude) {

        // Check if already have cache for this
        getRouteCache();

        $nearGeoPoint = new ParseGeoPoint(floatval($latitude), floatval($longitude));

        // Find airports within 1 mile of the Location
        $query = new ParseQuery("Airports");
        $query->withinMiles("geoPointLocation", $nearGeoPoint, 1);
        $objParseQueryAirportsWithin1Mile = $query->find();

        $withinOneMile = array();
        foreach ($objParseQueryAirportsWithin1Mile as $insideAirport) {

            $withinOneMile[] = $insideAirport->get('airportIataCode');
        }

        // Find airports within 10 miles of the Location
        $query = new ParseQuery("Airports");
        $query->withinMiles("geoPointLocation", $nearGeoPoint, 10);
        $objParseQueryAirportsWithin10Miles = $query->find();

        $withinTenMiles = array();
        foreach ($objParseQueryAirportsWithin10Miles as $insideAirport) {

            $withinTenMiles[] = $insideAirport->get('airportIataCode');
        }

        // Find all airports ordered by distance from this location
        $query = new ParseQuery("Airports");
        $query->near("geoPointLocation", $nearGeoPoint);
        $objParseQueryAirports = $query->find();

        $responseArray = array();
        $i = 0;
        foreach ($objParseQueryAirports as $airport) {

            $responseArray[$i] = $airport->getAllKeys();
            $responseArray[$i]["geoPointLocation"] = array(
                "longitude" => $airport->get('geoPointLocation')->getLongitude(),
                "latitude" => $airport->get('geoPointLocation')->getLatitude()
            );
            $responseArray[$i]["objectId"] = $airport->getObjectId();
            $responseArray[$i]["imageBackground"] = preparePublicS3URL($responseArray[$i]["imageBackground"],
                getS3KeyPath_ImagesAirportBackground(), $GLOBALS['env_S3Endpoint']);

            // Initialize
            $responseArray[$i]["withinOneMile"] = false;
            $responseArray[$i]["withinTenMiles"] = false;

            // If this airport was within 1 mile, mark it with a flag
            if (in_array($airport->get('airportIataCode'), $withinOneMile)) {

                $responseArray[$i]["withinOneMile"] = true;
            }

            // If this airport was within 1 mile, mark it with a flag
            if (in_array($airport->get('airportIataCode'), $withinTenMiles)) {

                $responseArray[$i]["withinTenMiles"] = true;
            }

            $i++;
        }

        json_echo(
            setRouteCache([
                "jsonEncodedString" => json_encode($responseArray),
                "expireInSeconds" => $GLOBALS['parseClassAttributes']['Airports']['ttl']
            ])
        );
    });

// Airport Ads
$app->get('/airports/ads/a/:apikey/e/:epoch/u/:sessionToken/airportIataCode/:airportIataCode', \App\Consumer\Middleware\UserAuthMiddleware::class . '::apiAuth',
    function ($apikey, $epoch, $sessionToken, $airportIataCode) {

        // Check if already have cache for this
        getRouteCache();

        $objParseQueryAds = parseExecuteQuery(array("airportIataCode" => $airportIataCode, "isActive" => true),
            "RetailerAds", "", "", ["retailer"]);

        $i = 0;
        $responseArray = [];
        foreach ($objParseQueryAds as $ads) {

            $responseArray[$i]["retailerUniqueId"] = $ads->get('retailer')->get('uniqueId');
            $responseArray[$i]["displaySeconds"] = $ads->get('displaySeconds');
            $responseArray[$i]["imageAd"] = preparePublicS3URL($ads->get('imageAd'),
                getS3KeyPath_ImagesRetailerAds($ads->get('airportIataCode')), $GLOBALS['env_S3Endpoint']);

            $i++;
        }

        json_echo(
            setRouteCache([
                "jsonEncodedString" => json_encode($responseArray),
                "expireInSeconds" => "EOD"
            ])
        );
    });

// Airlines
$app->get('/airlines/list/a/:apikey/e/:epoch/u/:sessionToken', \App\Consumer\Middleware\UserAuthMiddleware::class . '::apiAuth',
    \App\Consumer\Controllers\InfoController::class . ':getAirlineList'
);
/*$app->get('/airlines/list/a/:apikey/e/:epoch/u/:sessionToken', \App\Consumer\Middleware\UserAuthMiddleware::class . '::apiAuth',
    function ($apikey, $epoch, $sessionToken) {

        // Check if already have cache for this
        getRouteCache();

        $responseArray = array();
        $objParseQueryAirlines = parseExecuteQuery(array(), "Airlines", "", "topRanked");

        $i = 0;
        foreach ($objParseQueryAirlines as $airline) {

            $responseArray[$i] = $airline->getAllKeys();
            $responseArray[$i]["objectId"] = $airline->getObjectId();
            $i++;
        }

        json_echo(
            setRouteCache([
                "jsonEncodedString" => json_encode($responseArray),
                "expireInSeconds" => $GLOBALS['parseClassAttributes']['Airports']['ttl']
            ])
        );
    });*/

// Terminal Gate Map - Nearest
$app->get('/gatemap/near/a/:apikey/e/:epoch/u/:sessionToken/latitude/:latitude/longitude/:longitude', \App\Consumer\Middleware\UserAuthMiddleware::class . '::apiAuth',
    function ($apikey, $epoch, $sessionToken, $latitude, $longitude) {

        // Check if already have cache for this
        getRouteCache();

        $nearGeoPoint = new ParseGeoPoint(floatval($latitude), floatval($longitude));

        $query = new ParseQuery("TerminalGateMap");
        $query->near("geoPointLocation", $nearGeoPoint);
        $query->equalTo("includeInGateMap", true);
        $objParseQueryTGM = $query->first();

        $responseArray = $objParseQueryTGM->getAllKeys();
        $responseArray["objectId"] = $objParseQueryTGM->getObjectId();
        $responseArray["geoPointLocation"] = array(
            "longitude" => $objParseQueryTGM->get('geoPointLocation')->getLongitude(),
            "latitude" => $objParseQueryTGM->get('geoPointLocation')->getLatitude()
        );
        unset($responseArray["includeInGateMap"]);

        json_echo(
            setRouteCache([
                "jsonEncodedString" => json_encode($responseArray),
                "expireInSeconds" => $GLOBALS['parseClassAttributes']['TerminalGateMap']['ttl']
            ])
        );
    });

// Terminal Gate Map
$app->get('/gatemap/a/:apikey/e/:epoch/u/:sessionToken/airportIataCode/:airportIataCode', \App\Consumer\Middleware\UserAuthMiddleware::class . '::apiAuth',
    function ($apikey, $epoch, $sessionToken, $airportIataCode) {

        // Check if already have cache for this

        getRouteCache();

        $responseArray = getAirportTerminalGateMap($airportIataCode);
        //json_echo(json_encode($responseArray));
        //die();

        json_echo(
            setRouteCache([
                "jsonEncodedString" => json_encode($responseArray),
                "expireInSeconds" => $GLOBALS['parseClassAttributes']['TerminalGateMap']['ttl']
            ])
        );
    });


// Airports List
$app->get('/request',
    function () {
        session_start();
        if (!isset($_SESSION['allowed']) || $_SESSION['allowed'] !== 'yes') {
            echo '
            <form method="post" action=""><input type="password" name="password">
            <input type="submit" name="submit">
            </form>
            ';
            die();
        }
        echo 'connected';

    })->name('info-request');;

// Airports List
$app->post('/request',
    function () use ($app) {
        session_start();
        if (isset($_POST['password'])) {
            if ($_POST['password'] === 's3cr3tp4$$word') {
                $_SESSION['allowed'] = 'yes';
            } else {
                if (isset($_SESSION['allowed'])) {
                    unset($_SESSION['allowed']);
                }
            }
        }
        $app->response->redirect($app->urlFor('info-request'), 301);
    });



$app->get('/tips/values/a/:apikey/e/:epoch/u/:sessionToken',
    \App\Consumer\Middleware\UserAuthMiddleware::class . '::apiAuth',
    \App\Consumer\Controllers\InfoController::class . ':getTipsValues'
);




$app->notFound(function () {
    json_error("AS_005", "", "Incorrect API Call.");
});

$app->run();

?>
