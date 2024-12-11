<?php

require 'dirpath.php';
require $dirpath . 'vendor/autoload.php';
require $dirpath . 'core/token.php';

ini_set('precision', 14);
$epoch = microtime(true)*1000;

$errorMsg = "";
if(isset($_POST['step']) && $_POST['step'] == 1) {

    // JMD
    if(empty($_POST['select-retailers'])
        || count($_POST['select-retailers']) == 0) {

        $errorMsg .= "No retailers selected.";
    }
    else if(empty($_POST['select-terminalconcourses'])
        || count($_POST['select-terminalconcourses']) == 0) {

        $errorMsg .= "No Terminal and Concourses selected.";
    }
    else if(empty($_POST['select-airports'])) {

        $errorMsg .= "No Airport selected.";
    }

    if(empty($errorMsg)) {

        $parameters = ["select-airports" => $_POST["select-airports"],
            "select-retailers" => implode(",", $_POST["select-retailers"]),
            "select-terminalconcourses" => implode(",", $_POST["select-terminalconcourses"]),
            "select-adjustment-direction" => $_POST["select-adjustment-direction"],
            "select-adjustment-minutes" => $_POST["select-adjustment-minutes"]
        ];

        $response = \Httpful\Request::post($env_BaseURL . '/delivery/adjusttimes'
            . '/a/' . generateAPIToken($epoch)
            . '/e/' . $epoch
            . '/u/' . '0')
            ->body(http_build_query($parameters))
            ->expectsJson()
            ->sendIt();

        if($response == null || empty($response) || $response->hasBody() == false) {

            $error_message = 'Reload the page and try again.';
        }
        else {

            $response = json_decode($response->__toString(), true);
        }

        if(isset($response['json_resp_status']) && intval($response['json_resp_status']) == 0) {

            $errorMsg = $response['json_resp_message'];
        }
//			else {
//
//				Header("Location: deliverytimes.php#table-existing");
//				exit;
//			}
    }
}
else {

    Header("Location: deliverytimes.php");
    exit;
}

?>
<!DOCTYPE html>
<html>
<head>
    <!-- META -->
    <title>Delivery - Times Override</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <meta name="description" content="" />

    <!-- CSS -->
    <link rel="stylesheet" type="text/css" href="../css/kickstart.css" media="all" />
    <link rel="stylesheet" type="text/css" href="../css/style.css" media="all" />
    <link rel="stylesheet" type="text/css" href="../library/selectize/css/selectize.default.css" media="all" />

    <link rel="icon" type="image/png" href="/images/favicon.png">

    <!-- Javascript -->
    <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>
    <script type="text/javascript" src="../js/kickstart.js"></script>
    <script type="text/javascript" src="../library/selectize/js/standalone/selectize.min.js"></script>
</head>
<body>
<div class="grid">
    <table class="noborder" cellspacing="0" cellpadding="0">
        <thead>
        <tr>
            <th>
                <h4>Delivery Times Override</h4>
                <?php

                if($env_EnvironmentDisplayCode == 'DEV') {

                    $buttonColor = 'blue';
                }
                else if($env_EnvironmentDisplayCode == 'TEST') {

                    $buttonColor = 'green';
                }
                else {

                    $buttonColor = 'orange';
                }

                ?>
                <a class="small <?php echo($buttonColor); ?> button" href="#" onclick="return false"><i class="fa"></i> <?php echo($env_EnvironmentDisplayCode); ?></a>
                <a class="small blue button" href="../index.php"><i class="fa"></i> HOME</a>
            </th>
            <th style="text-align: right">
                <a href='' onclick='self.reload();'><img src="../images/logo.png" width="70" /></a>
            </th>
        </tr>
        </thead>
    </table>

    <!-- Tabs -->
    <ul class="tabs right">
        <li><a href="#table-add">Add Override</a></li>
    </ul>

    <!-- Tabs: Add -->
    <div id="table-add" class="tab-content">
        <div class="col_12">
            <br /><br />
            <div id="link-wrapper-message-deliverytimes" style="display: inline">
                <?php

                if(empty($errorMsg)) {
                    ?>
                    <h4 id="link-wrapper-message-text-deliverytimes" style="text-transform: inline" class="inprocess">
                        <script>
                            setTimeout(function () {
                                window.location.href = "deliverytimes.php#table-existing";
                            }, 4000);
                        </script>
                        <br />
                        <div align="center">Requested, redirecting...</div>
                    </h4>
                    <h7>
                        <div align="center">(if it doesn't redirect in 5 secs, <a href="deliverytimes.php#table-existing">click here</a>...)</div>
                    </h7>

                    <?php
                }
                else {
                    ?>

                    <div align="center">
                        <h5 id="link-wrapper-message-text-deliverytimes" style="text-transform: inline" class="inprocess">
                            <?php echo($errorMsg); ?>
                            <br /><br />
                            <a href="deliverytimes.php">&lt;&lt; go back</a>
                        </h5>
                    </div>
                    <?php

                }
                ?>

            </div>
        </div>
    </div>
    <!-- Tabs: Add - End -->

</div> <!-- End Grid -->

</body>
</html>