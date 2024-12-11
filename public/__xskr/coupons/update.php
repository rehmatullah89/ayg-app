<?php

require 'dirpath.php';
require $dirpath . 'vendor/autoload.php';
require $dirpath . 'core/token.php';




if (isset($_GET['action'])&&($_GET['action']=='update')) {
    ini_set('precision', 14);
    $epoch = microtime(true) * 1000;


    $epoch = generateEpoch($epoch);
    $response = json_decode(
        getpage($env_BaseURL . '/coupons/update'
            . '/a/' . generateAPIToken($epoch)
            . '/e/' . $epoch
            . '/u/' . '0'
        ), true);

}


?>
<!DOCTYPE html>
<html>
<head>
    <!-- META -->
    <title>Coupons Update</title>
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
                <h4>Coupons update</h4>
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

        Please upload changes into S3 ayg-data-load bucket. Next click on "Update". That will
        trigger app data update (process can be monitored on slack).


        <ul>
                <li>
                    <a href="./update.php?action=update">Update</a>
                    </li>
        </ul>

    </div>
</div>

