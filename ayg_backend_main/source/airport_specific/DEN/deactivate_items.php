<?php
$_SERVER['REQUEST_METHOD']='';
$_SERVER['REMOTE_ADDR']='';
$_SERVER['REQUEST_URI']='';
$_SERVER['SERVER_NAME']='';

	require 'dirpath.php';
    $fullPathToBackendLibraries = "../";
	require_once $fullPathToBackendLibraries . 'lib/initiate.inc.php';
	
	// require 'parsedataload_functions.php';

$x = explode('airport_specific/',__DIR__);
$airportIataCode=substr($x[1],0,3);

$toDeactivate = [
    'RetailerItems',
	'RetailerItemModifiers',
	'RetailerItems',
];

foreach ($toDelete as $class){
    try{
        $query = new \Parse\ParseQuery($class);
        $res = $query->find();

        for ($i = 0; $i < count($res); $i++) {
            $object = $res[$i];
            $object->destroy();
        }
        echo $class." destroyed".PHP_EOL;

    } catch (\Parse\ParseException $ex) {
        echo $ex->getMessage();
    }
}


?>
