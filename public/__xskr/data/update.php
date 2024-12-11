<?php

require 'dirpath.php';
require $dirpath . 'vendor/autoload.php';
require $dirpath . 'core/token.php';

$epoch = microtime(true) * 1000;
$epoch = generateEpoch($epoch);
$response = getpage($env_BaseURL . '/metadata/airports'
    . '/a/' . generateAPIToken($epoch)
    . '/e/' . $epoch
    . '/u/' . '0'
);

$activeAirports = json_decode($response, true)['json_resp_message'];

if (isset($_GET['airportIataCode'])) {
    ini_set('precision', 14);
    $epoch = microtime(true) * 1000;

    // Airport List


    $epoch = generateEpoch($epoch);
    $response = json_decode(
        getpage($env_BaseURL . '/data/update'
            . '/a/' . generateAPIToken($epoch)
            . '/e/' . $epoch
            . '/u/' . '0'
            . '/airportIataCode/' . $_GET['airportIataCode']
        ), true);
}


?>
<!DOCTYPE html>
<html>
<head>
    <!-- META -->
    <title>Data Update</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <meta name="description" content=""/>

    <!-- CSS -->
    <link rel="stylesheet" type="text/css" href="../css/kickstart.css" media="all"/>
    <link rel="stylesheet" type="text/css" href="../css/style.css" media="all"/>

    <link rel="icon" type="image/png" href="/images/favicon.png">

    <!-- Javascript -->
    <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>
    <script type="text/javascript" src="../js/kickstart.js"></script>
</head>
<body>
<div class="grid">
    <table class="noborder" cellspacing="0" cellpadding="0">
        <thead>
        <tr>
            <th>
                <h4>Data update</h4>
                <?php

                if ($env_EnvironmentDisplayCode == 'DEV') {

                    $buttonColor = 'blue';
                } else {
                    if ($env_EnvironmentDisplayCode == 'TEST') {

                        $buttonColor = 'green';
                    } else {

                        $buttonColor = 'orange';
                    }
                }

                ?>
                <a class="small <?php echo($buttonColor); ?> button" href="#" onclick="return false"><i
                            class="fa"></i> <?php echo($env_EnvironmentDisplayCode); ?></a>

                <a class="small blue button" href="../index.php"><i class="fa"></i> HOME</a>
            </th>
            <th style="text-align: right">
                <a href='' onclick='self.reload();'><img src="../images/logo.png" width="70"/></a>
            </th>
        </tr>
        </thead>
    </table>
    <div>

        Please upload changes into S3 ayg-data-load bucket. Next click on airport that has been updated. That will
        trigger app data update (process can be monitored on slack).


        <ul>
            <?php foreach ($activeAirports as $airport): ?>
                <li>
                    <a href="./update.php?airportIataCode=<?php echo $airport['id'] ?>"><?php echo $airport['id'] ?>
                        : <?php echo $airport['name'] ?></a>
                    </li>
            <?php endforeach; ?>
        </ul>

    </div>
</div>

