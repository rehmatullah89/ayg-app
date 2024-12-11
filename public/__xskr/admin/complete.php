<?php

	require 'dirpath.php';
	require $dirpath . 'vendor/autoload.php';
	require $dirpath . 'core/token.php';

	if(!isset($_GET['orderId'])) {

		die("Reload previous page");
	}

	$_GET['orderId'] = intval($_GET['orderId']);

	ini_set('precision', 14); 
	$epoch = microtime(true)*1000;
	$token = generateToken($epoch);

	$response = json_decode(
					getpage($env_BaseURL . '/order/completeWithAdmin'
					. '/a/' . generateAPIToken($epoch)
					. '/e/' . $epoch
					. '/u/' . '0'
					. '/orderId/' . $_GET['orderId']
					), true);

	if(isset($response["json_resp_status"])
		&& intval($response["json_resp_status"]) == 1) {

		$responseText = $response["json_resp_message"];
	}
	else {

		$responseText = "Failed. Try again.";
	}
?>
<!DOCTYPE html>
<html>
<head>
	<!-- META -->
	<title>Order - Admin Cancel Request</title>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
	<meta name="description" content="" />
	
	<!-- CSS -->
	<link rel="stylesheet" type="text/css" href="../css/kickstart.css" media="all" />
	<link rel="stylesheet" type="text/css" href="../css/style.css" media="all" /> 
	
    <link rel="icon" type="image/png" href="/images/favicon.png">
	
	<!-- Javascript -->
	<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>
	<script type="text/javascript" src="../js/kickstart.js"></script>
	<script>
	setTimeout(function () { window.location.href = "../order/index.php"; }, 3000);
	</script>
</head>
<body align="center">
	<h4><?php echo($responseText); ?>
	<br /><br />
	redirecting...
	<br /><br />
	<a href='../order/index.php'>Back to Orders</a>
	</h4>
</body>
</html>
