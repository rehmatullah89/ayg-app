<?php

	require 'dirpath.php';
	require $dirpath . 'vendor/autoload.php';
	require $dirpath . 'core/token.php';

	ini_set('precision', 14);
	$epoch = microtime(true)*1000;


	$response = json_decode(
					getpage($env_BaseURL . '/status/delivery'
					. '/a/' . generateAPIToken($epoch)
					. '/e/' . $epoch

					. '/u/' . '0'
					), true);
/*
		  <td class="hide-phone">
			<div id="link-wrapper-message-' . $deliveryId . '" style="display: none">
				<h7 id="link-wrapper-message-text-' . $deliveryId . '" style="text-transform: none" class="inprocess"></h7>
			</div>
			<div id="link-wrapper-button-' . $deliveryId . '">
				<a href="" id="' . $deliveryId . '" onclick="ajaxConnect(\'' . $urlLink . '\', \'' . $deliveryId . '\', \'Sending...\', \' you want to send text message to ' . addslashes($deliveryUser['info']['slackChannelName']) . '\');return false;"><i class="fa"></i>Ping Msg</a>
			</div>
		  </td>

 */


?>
<!DOCTYPE html>
<html>
<head>
	<!-- META -->
	<title>Delivery Status</title>
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
</head>
<body>
<div class="grid">
	<table class="noborder" cellspacing="0" cellpadding="0">
	  <thead>
		<tr>
		  <th>
			<h4>Delivery Status</h4>
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
		<li><a href="#table-delivery">Delivery (<?php echo(count($response["users"])); ?>)</a></li>
		<li><a href="#table-airports">Today's Coverage Times (<?php echo(count($response["airports"])); ?>)</a></li>
	</ul>

	<!-- Tabs: Delivery List -->
	<div id="table-delivery" class="tab-content">
	<div class="col_12">
	<table class="sortable" cellspacing="0" cellpadding="0">
	  <thead>
		<tr>
		  <th>User</th>
		  <th class="hide-phone">Phone Number</th>
		  <th class="hide-phone">Is Online?</th>
		  <th class="hide-phone">Active Orders</th>
		  <th class="hide-phone">Airport</th>
		  <th>Is Active?</th>
		  <th class="hide-phone">Last Seen</th>
		  <th class="hide-phone">Slack Login</th>
		  <th>&nbsp;</th>
		</tr>
	  </thead>
	  <tbody>
<?php

	if(count($response) < 1) {
		
echo('
		<tr>
		  <td colspan="10" style="text-align: center; color: #ddd"><h4>No Delivery users found!</h4></td>
		</tr>
');
	}

	foreach($response["users"] as $deliveryId => $deliveryUser) {

		ini_set('precision', 14); 
		$epoch = generateEpoch($epoch);
		$token = generateToken($epoch);
		$urlLink = '../process/?action=deliveryTestMsg&tokenEpoch=' . $epoch . '&token=' . $token . '&deliveryId=' . $deliveryId;

		if($deliveryUser['info']['isActive'] == true) {

			$urlLinkValidity = '../process/?action=deliveryDeactivate&tokenEpoch=' . $epoch . '&token=' . $token . '&deliveryId=' . $deliveryId;
			$textValidity = 'deactivate';
		}
		else  {

			$urlLinkValidity = '../process/?action=deliveryActivate&tokenEpoch=' . $epoch . '&token=' . $token . '&deliveryId=' . $deliveryId;
			$textValidity = 'activate';
		}

		
echo('
		<tr>
		  <td>' . $deliveryUser['info']['deliveryName'] . '</td>
		  <td class="hide-phone">' . $deliveryUser['info']['SMSPhoneNumber'] . '</td>
');

if($deliveryUser['ping']['isDeliveryOnline'] == true)
echo('
   		  <td style="text-align: left" class="hide-phone">
			<a class="small green button" href="#" onclick="return false"><i class="fa"></i> ONLINE</a>
		  </td>
');
else
echo('
   		  <td style="text-align: left" class="hide-phone">
			<a class="small red button" href="#" onclick="return false"><i class="fa"></i> OFFLINE</a>
		  </td>
');

echo('
		  <td class="hide-phone">' . $deliveryUser['info']['countOfActiveOrders'] . '</td>
		  <td class="hide-phone">' . $deliveryUser['info']['airportIataCode'] . '</td>
');

if($deliveryUser['info']['isActive'] == true)
echo('
   		  <td style="text-align: left">
			<a class="small green button" href="#" onclick="return false"><i class="fa"></i> YES</a>
		  </td>
');
else
echo('
   		  <td style="text-align: left">
			<a class="small red button" href="#" onclick="return false"><i class="fa"></i> NO</a>
		  </td>
');

echo('
		  <td><i style="font-size: 1px; color:#fff">"' . $deliveryUser['ping']['lastSeenTimestamp'] . '-</i>' . $deliveryUser['ping']['lastSeenTimestampFormatted'] . '</td>
		  <td class="hide-phone">' . $deliveryUser['info']['slackUsername'] . '</td>
		  <td class="hide-phone">
			<div id="link-wrapper-message-' . 'status-' . $deliveryId . '" style="display: none">
				<h7 id="link-wrapper-message-text-' . 'status-' . $deliveryId . '" style="text-transform: none" class="inprocess"></h7>
			</div>
			<div id="link-wrapper-button-' . 'status-' . $deliveryId . '">
				<a href="" id="' . 'status-' . $deliveryId . '" onclick="ajaxConnect(\'' . $urlLinkValidity . '\', \'' . 'status-' . $deliveryId . '\', \'Requesting...\', \' you want to ' . $textValidity . ' ' . addslashes($deliveryUser['info']['deliveryName']) . '\');return false;"><i class="fa"></i>' . $textValidity . '</a>
			</div>
		  </td>
		</tr>
');
	}

?>
	  </tbody>
	</table>
	</div>
	</div>
	<!-- Tabs: Delivery List - End -->
	
	<!-- Tabs: Airports List -->
	<div id="table-airports" class="tab-content">
	<div class="col_12">
	<table class="sortable" cellspacing="0" cellpadding="0">
	  <thead>
		<tr>
		  <th>Airport</th>
		  <th>Coverage Start</th>
		  <th>Coverage End</th>
		  <th class="hide-phone">&nbsp;</th>
		  <th class="hide-phone">&nbsp;</th>
		  <th class="hide-phone">&nbsp;</th>
		  <th class="hide-phone">&nbsp;</th>
		  <th class="hide-phone">&nbsp;</th>
		  <th class="hide-phone">&nbsp;</th>
		  <th class="hide-phone">&nbsp;</th>
		</tr>
	  </thead>
	  <tbody>
<?php

	foreach($response["airports"] as $airportIataCode => $coverageTimes) {
	
echo('
		<tr>
		  <td>' . $airportIataCode . '</td>
		  <td>' . $coverageTimes["startCoverage"] . '</td>
		  <td>' . $coverageTimes["stopCoverage"] . '</td>
		  <th class="hide-phone">&nbsp;</th>
		  <th class="hide-phone">&nbsp;</th>
		  <th class="hide-phone">&nbsp;</th>
		  <th class="hide-phone">&nbsp;</th>
		  <th class="hide-phone">&nbsp;</th>
		  <th class="hide-phone">&nbsp;</th>
		  <th class="hide-phone">&nbsp;</th>
');

	}
?>

	  </tbody>
	</table>
	</div>
	</div>
	<!-- Tabs: Airports List - End -->

</div> <!-- End Grid -->
<script type="text/javascript" src="../js/link.submit.js"></script>
</body>
</html>
