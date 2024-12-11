<?php

	require 'dirpath.php';
	require $dirpath . 'vendor/autoload.php';
	require $dirpath . 'core/token.php';

	if(!isset($_GET['l'])
		|| intval($_GET['l']) <= 0
		|| intval($_GET['l']) > 96) {

		$_GET['l'] = 36;
	}

	$_GET['l'] = intval($_GET['l']);

	ini_set('precision', 14);
	$epoch = microtime(true)*1000;
	$response = json_decode(
					getpage($env_BaseURL . '/order/list'
					. '/a/' . generateAPIToken($epoch)
					. '/e/' . $epoch
					. '/u/' . '0'
					. '/l/' . $_GET['l']
					), true);
?>
<!DOCTYPE html>
<html>
<head>
	<!-- META -->
	<title>Orders</title>
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
			<h4>Orders</h4>
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
		<li><a href="#table-active">Active (<?php echo(count($response['active'])); ?>)</a></li>
		<li><a href="#table-completed">Completed - <?php echo($_GET['l']); ?> hrs (<?php echo(count($response['completed'])); ?>)</a></li>
	</ul>

	<!-- Tabs: active order -->
	<div id="table-active" class="tab-content">
	<div class="col_12">
	<table class="sortable" cellspacing="0" cellpadding="0">
	  <thead>
		<tr>
		  <th>Status</th>
		  <th>Id</th>
		  <th class="hide-phone">Delay Notes</th>
		  <th>Type</th>
          <th>Airport</th>
		  <th colspan="2">Retailer</th>
		  <th class="hide-phone">ETA</th>
		  <th class="hide-phone">Submitted</th>
		  <th class="hide-phone">Delivery P</th>
		  <th class="hide-phone">Deliver at</th>
		  <th class="hide-phone">Customer</th>
		  <th class="hide-phone">Phone</th>
		  <th class="hide-phone">Flight</th>
		  <th class="hide-phone">&nbsp;</th>
		</tr>
	  </thead>
	  <tbody>
<?php

	if(count($response['active']) < 1) {
		
echo('
		<tr>
		  <td colspan="16" style="text-align: center; color: #ddd"><h4>No active orders</h4></td>
		</tr>
');
	}

	$response['active'] = array_sort($response['active'], 'submitTimestamp', SORT_DESC);
	foreach($response['active'] as $order) {

		ini_set('precision', 14); 
		$epoch = generateEpoch($epoch);
		$token = generateToken($epoch);
		$urlLinkForInvoice = '../process/?action=fetchInvoice&tokenEpoch=' . $epoch . '&token=' . $token . '&orderId=' . $order['objectId'];
	
		ini_set('precision', 14); 
		$epoch = generateEpoch($epoch);
		$token = generateToken($epoch);
		$urlLinkPush = '../process/?action=orderPush&tokenEpoch=' . $epoch . '&token=' . $token . '&orderId=' . $order['objectId'];
	
		printOrderRow($order, '', $urlLinkForInvoice, $urlLinkPush, '', '', 'a');
	}

?>
	  </tbody>
	</table>
	</div>
	</div>
	<!-- Tabs: active orders - End -->
	
	<!-- Tabs: completed orders -->
	<div id="table-completed" class="tab-content">
	<div class="col_12">
	<table class="sortable" cellspacing="0" cellpadding="0">
	  <thead>
		<tr>
		  <th>Status</th>
		  <th>Id</th>
		  <th class="hide-phone">SLA</th>
		  <th>Type</th>
          <th>Airport</th>
		  <th colspan="2">Retailer</th>
		  <th class="hide-phone">ETA</th>
		  <th class="hide-phone">Submitted</th>
		  <th class="hide-phone">Delivery P</th>
		  <th class="hide-phone">Deliver at</th>
		  <th class="hide-phone">Customer</th>
		  <th class="hide-phone">Phone</th>
		  <th class="hide-phone">Flight</th>
		  <th class="hide-phone">App Rating</th>
		</tr>
	  </thead>
	  <tbody>
<?php

	if(count($response['completed']) < 1) {
		
echo('
		<tr>
		  <td colspan="15" style="text-align: center; color: #ddd"><h4>No completed orders in last ' . $_GET['l'] . ' hours.</h4></td>
		</tr>
');
	}

	$response['completed'] = array_sort($response['completed'], 'submitTimestamp', SORT_DESC);
	foreach($response['completed'] as $order) {

		ini_set('precision', 14); 
		$epoch = generateEpoch($epoch);
		$token = generateToken($epoch);
		$urlLinkPush = '../process/?action=orderPush&tokenEpoch=' . $epoch . '&token=' . $token . '&orderId=' . $order['objectId'];

		ini_set('precision', 14); 
		$epoch = generateEpoch($epoch);
		$token = generateToken($epoch);
		$urlLinkForInvoice = '../process/?action=fetchInvoice&tokenEpoch=' . $epoch . '&token=' . $token . '&orderId=' . $order['objectId'];
		$urlLinkRatingRequest = '../process/?action=appRatingRequestSend&tokenEpoch=' . $epoch . '&token=' . $token . '&orderId=' . $order['objectId'];
		$urlLinkRatingSkip = '../process/?action=appRatingRequestSkip&tokenEpoch=' . $epoch . '&token=' . $token . '&orderId=' . $order['objectId'];

		$urlLink = '';
	
		printOrderRow($order, $urlLink, $urlLinkForInvoice, $urlLinkPush, $urlLinkRatingRequest, $urlLinkRatingSkip, 'c');
	}

?>
	  </tbody>
	</table>
	</div>
	</div>
	<!-- Tabs: completed orders - End -->


</div> <!-- End Grid -->
<script type="text/javascript" src="../js/link.submit.js"></script>
</body>
</html>

<?php

function printOrderRow($order, $urlLink, $urlLinkForInvoice, $urlLinkPush, $urlLinkRatingRequest, $urlLinkRatingSkip, $side) {
	
	echo('
		<tr>
			<td>
	');

	$buttonColorDeliveryStatus = 'orange';

	// Scheduled Order
	if($order['status'] == 8) {

		$buttonColorStatus = 'pink';
	}
	else if($order['statusCategory'] == 100) {

		$buttonColorStatus = 'blue';
	}
	else if($order['statusCategory'] == 200) {

		$buttonColorStatus = 'orange';
	}
	else if($order['statusCategory'] == 400) {

		$buttonColorStatus = 'green';
		$buttonColorDeliveryStatus = 'green';
	}
	else if($order['statusCategory'] == 400) {

		$buttonColorStatus = 'green';
	}
	else if($order['statusCategory'] == 600) {

		$buttonColorStatus = 'red';
	}
	else {

		$buttonColorStatus = 'pink';
	}

echo('
		<a class="small ' . $buttonColorStatus . ' button" href="#" onclick="return false"><i class="fa"></i>' . $order['status'] . '</a>
');

	if(!empty($order['statusDelivery'])) {

echo('
		<br />
		<a class="small ' . $buttonColorDeliveryStatus . ' button" href="#" onclick="return false"><i class="fa"></i>' . $order['statusDelivery'] . '</a>
');

	}

	if(!empty($order['flightNum'])) {

		$flightInfo = $order['flightNum'] . ' to ' . $order['flightArrivalIataCode'] . ' - ' . $order['flightDepartureTimeFormatted'] . ' from <u>' . $order['flightDepartureGate'] . '</u>';
	}
	else {

		$flightInfo = 'none';
	}

echo('
			</td>
		  <td><a href="' . $urlLinkForInvoice . '" target="_blank">' . $order['orderSequenceId'] . '</a></td>
');

if(strcasecmp($side, 'a')==0) {
echo('
		  <td class="hide-phone"><i style="font-size: 1px; color:#fff">' . $order['delayInMins'] . '-' . '</i><i style="color: red">' . $order['delayedByFormatted'] . '</i></td>
');
}
else {

		if(!isset($order['promisedVsActualDeliveryTimeDiffInMins'])) {

echo('
		  <td class="hide-phone"><i style="font-size: 1px; color:#fff">0-' . '</i></td>
');
}
		else if($order['promisedVsActualDeliveryTimeDiffInMins'] == 0) {

echo('
		  <td class="hide-phone"><i style="font-size: 1px; color:#fff">0-</i><i style="color: green">On time</i></td>
');
		}
		else if($order['promisedVsActualDeliveryTimeDiffInMins'] > 0) {

echo('
		  <td class="hide-phone"><i style="font-size: 1px; color:#fff">' . $order['promisedVsActualDeliveryTimeDiffInMins'] . '-' . '</i><i style="color: green">Early ' . $order['promisedVsActualDeliveryTimeDiffInMins'] . ' mins</i></td>
');
		}
		else {

echo('
		  <td class="hide-phone"><i style="font-size: 1px; color:#fff">' . $order['promisedVsActualDeliveryTimeDiffInMins'] . '-' . '</i><i style="color: red">Late ' . -1*$order['promisedVsActualDeliveryTimeDiffInMins'] . ' mins</i></td>
');
		}

}

if($order['etaTimestampRangeShown'] == true) {
	
	$etaTimeFormatted = $order['etaTimestampRangeFormatted'];
}
else {
	
	$etaTimeFormatted = $order['etaTimestampFormatted'];
}

echo('
		  <td>' . ($order['fullfillmentType'] == 'p' ? 'P' : 'D') . '</td>
		  <td class="hide-phone"><i style="font-size: 10px;">' . $order['airportIataCode'] . '</i></td>
		  <td colspan="2">' . $order['retailerName'] . ' (' . $order['retailerLocation'] . ')</td>
		  <td class="hide-phone"><i style="font-size: 1px; color:#fff">"' . $order['etaTimestamp'] . '-' . '</i><i style="font-size: 10px;">' . $etaTimeFormatted . '</i></td>
		  <td class="hide-phone"><i style="font-size: 1px; color:#fff">"' . $order['submitTimestamp'] . '-' . '</i><i style="font-size: 10px;">' . $order['submitTimestampFormatted'] . '</i></td>
		  <td class="hide-phone"><i style="font-size: 10px;">' . $order['deliveryName'] . '</i></td>
		  <td class="hide-phone"><i style="font-size: 10px;">' . $order['deliveryLocation'] . '</i></td>
		  <td class="hide-phone"><i style="font-size: 10px;">' . $order['customerName'] . '</i></td>
		  <td class="hide-phone"><i style="font-size: 10px;">' . $order['customerPhone'] . '</i></td>
		  <td class="hide-phone"><i style="font-size: 10px;">' . $flightInfo . '</i></td>
   		  <td style="text-align: right">
 ');

 if($order["isCancellable"] == true) {

 echo('
			<div id="link-wrapper-button-cancel-' . $order['objectId'] . '">
				<a class="small red button" href="cancel.php?orderId=' . $order['orderSequenceId'] . '&retailerName=' . urlencode($order['retailerName']) . '&customerName=' . urlencode($order['customerName']) . '&amountPaid=' . urlencode($order['amountPaid']) .'" id="cancel-' . $order['objectId'] . '"><i class="fa"></i> CANCEL</a>
			</div>
			<br />
 ');

}

if(strcasecmp($side, 'a')==0) {
	
 echo('
			<div id="link-wrapper-button-cancel-' . $order['objectId'] . '">
				<a class="small orange button" href="../admin/cancel.php?orderId=' . $order['orderSequenceId'] . '&retailerName=' . urlencode($order['retailerName']) . '&customerName=' . urlencode($order['customerName']) . '&amountPaid=' . urlencode($order['amountPaid']) .'" id="canceladmin-' . $order['objectId'] . '"><i class="fa"></i> CANCEL w/ ADMIN</a>
			</div>
			<br />
 ');

 echo('
			<div id="link-wrapper-button-cancel-' . $order['objectId'] . '">
				<a class="small orange button" href="../admin/complete.php?orderId=' . $order['orderSequenceId'] .'" id="complete-' . $order['objectId'] . '"><i class="fa"></i> COMPLETE w/ ADMIN</a>
			</div>
			<br />
 ');
}

if($order["isPushable"] == true) {

 echo('
			<div id="link-wrapper-message-' . $order['objectId'] . '" style="display: none">
				<h7 id="link-wrapper-message-text-' . $order['objectId'] . '" style="text-transform: none" class="inprocess"></h7>
			</div>
			<div id="link-wrapper-button-' . $order['objectId'] . '">
				<a class="small blue button" href="" id="' . $order['objectId'] . '" onclick="ajaxConnect(\'' . $urlLinkPush . '\', \'' . $order['objectId'] . '\', \'Requesting...\', \'push Order# ' . $order['orderSequenceId'] . '\');return false;"><i class="fa"></i> PUSH</a>
			</div>
 ');
}

if($order["ratingRequestAllowed"] == true) {

 echo('
			<div id="link-wrapper-message-' . $order['objectId'] . '" style="display: none">
				<h7 id="link-wrapper-message-text-' . $order['objectId'] . '" style="text-transform: none" class="inprocess"></h7>
			</div>
			<div id="link-wrapper-button-' . $order['objectId'] . '">
				<a class="small blue button" href="" id="' . $order['objectId'] . '" onclick="ajaxConnect(\'' . $urlLinkRatingRequest . '\', \'' . $order['objectId'] . '\', \'Requesting...\', \'request rating for Order# ' . $order['orderSequenceId'] . '\');return false;"><i class="fa"></i> REQUEST</a>
				<a class="small orange button" href="" id="' . $order['objectId'] . '" onclick="ajaxConnect(\'' . $urlLinkRatingSkip . '\', \'' . $order['objectId'] . '\', \'Skipping...\', \'skip rating request for Order# ' . $order['orderSequenceId'] . '\');return false;"><i class="fa"></i> SKIP</a>
			</div>
 ');
}
else if($order["ratingRequestAllowed"] == false
	&& !empty($order['ratingRequestNotAllowedReason'])) {

 echo('
			<div id="link-wrapper-button-' . $order['objectId'] . '">' . $order['ratingRequestNotAllowedReason'] . '</div>
 ');
}

echo('
		  </td>
		</tr>
');

}
