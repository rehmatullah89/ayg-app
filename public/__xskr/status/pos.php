<?php

	require 'dirpath.php';
	require $dirpath . 'vendor/autoload.php';
	require $dirpath . 'core/token.php';

	ini_set('precision', 14);
	$epoch = microtime(true)*1000;
	$response = json_decode(
					getpage($env_BaseURL . '/status/pos'
					. '/a/' . generateAPIToken($epoch)
					. '/e/' . $epoch
					. '/u/' . '0'
					), true);

	?>
<!DOCTYPE html>
<html>
<head>
	<!-- META -->
	<title>POS Status</title>
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
			<h4>Tablet/POS Status</h4>

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
		<li><a href="#table-pos">Tablets (<?php echo(count($response)); ?>)</a></li>
	</ul>

	<!-- Tabs: Tablet List -->
	<div id="table-pos" class="tab-content">
	<div class="col_12">
	<table class="sortable" cellspacing="0" cellpadding="0">
	  <thead>
		<tr>
		  <th>Retailer</th>
		  <th class="hide-phone">Airport</th>
		  <th class="hide-phone">Location</th>
		  <th>Status</th>
		  <th class="hide-phone">Available?</th>
		  <th class="hide-phone">Last Seen</th>
		  <th class="hide-phone">App ver</th>
		  <th class="hide-phone">Battery</th>
		  <th class="hide-phone">Charging?</th>
		  <th class="hide-phone">@Airport?</th>
		  <th class="hide-phone">Locked?</th>
		  <th class="hide-phone">&nbsp;</th> <!-- Alerts: Mobilock license alert -->
		  <th class="hide-phone">&nbsp;</th> <!-- Unused -->
		  <th class="hide-phone" colspan="2">Close POS</th> <!-- Close Early --> <!-- Close Early +1 days -->
		</tr>
	  </thead>
	  <tbody>
<?php

	if(count($response) < 1) {
		
echo('
		<tr>
		  <td colspan="15" style="text-align: center; color: #ddd"><h4>No Live Tablets found!</h4></td>
		</tr>
');
	}

	foreach($response as $uniqueId => $retailer) {

		ini_set('precision', 14); 
		$epoch = generateEpoch($epoch);
		$token = generateToken($epoch);
		$urlToPingSlackLink = '../process/?action=posTestMsg&tokenEpoch=' . $epoch . '&token=' . $token . '&uniqueId=' . $uniqueId;

		$epoch = generateEpoch($epoch);
		$token = generateToken($epoch);

		$urlToEarlyClosePOSLink = '../process/?action=closeEarlyPOS&tokenEpoch=' . $epoch . '&token=' . $token . '&uniqueId=' . $uniqueId . '&closeUntilDate=0';

		$epoch = generateEpoch($epoch);
		$token = generateToken($epoch);
		$urlToReOpenPOSLink = '../process/?action=reopenPOS&tokenEpoch=' . $epoch . '&token=' . $token . '&uniqueId=' . $uniqueId;
		
echo('
		<tr>
		  <td>' . $retailer['info']['retailerName'] . '</td>
		  <td class="hide-phone">' . $retailer['info']['airportIataCode'] . '</td>
		  <td class="hide-phone">' . $retailer['info']['location'] . '</td>
');

if($retailer['ping']['isPingBeingChecked'] == false)
echo('
   		  <td style="text-align: left">
			<a class="small orange button" href="#" onclick="return false" title="POS not being checked for Online status - Update RetailerPOSConfig"><i class="fa"></i> HOLD</a>
		  </td>
');
else if($retailer['ping']['isTabletOnline'] == true)
echo('
   		  <td style="text-align: left">
			<a class="small green button" href="#" onclick="return false"><i class="fa"></i> ONLINE</a>
		  </td>
');
else
echo('
   		  <td style="text-align: left">
			<a class="small red button" href="#" onclick="return false"><i class="fa"></i> OFFLINE</a>
		  </td>
');

if($retailer['ping']['isClosed'] == false)
echo('
   		  <td style="text-align: left" class="hide-phone">
			<a class="small green button" href="#" onclick="return false"><i class="fa"></i>OPEN</a>
		  </td>
');
else if($retailer['ping']['isClosedEarly'] == true) {
	
	if(!empty($retailer['ping']['isClosedEarlyUntil']))
echo('
   		  <td style="text-align: left" class="hide-phone">
			<a class="small orange button" href="#" onclick="return false"><i class="fa"></i>CLOSED until ' . $retailer['ping']['isClosedEarlyUntil'] . '</a>
		  </td>
');

	else
echo('
   		  <td style="text-align: left" class="hide-phone">
			<a class="small orange button" href="#" onclick="return false"><i class="fa"></i>CLOSED EARLY</a>
		  </td>
');
}
else if($retailer['ping']['isBeingClosedEarly'] == true)
echo('
   		  <td style="text-align: left" class="hide-phone">
			<a class="small yellow button" href="#" onclick="return false"><i class="fa"></i>CLOSING</a>
		  </td>
');
else
echo('
   		  <td style="text-align: left" class="hide-phone">
			<a class="small red button" href="#" onclick="return false"><i class="fa"></i>CLOSED</a>
		  </td>
');

echo('
		  <td class="hide-phone"><i style="font-size: 1px; color:#fff">"' . $retailer['ping']['lastSeenTimestamp'] . '-</i>' . $retailer['ping']['lastSeenTimestampFormatted'] . '</td>
		  <td class="hide-phone">' . $retailer['info']['appVersion'] . '</td>
');

if(intval($retailer['info']['lastSeenByMobilock']) == 0 
	|| (time() - intval($retailer['info']['lastSeenByMobilock'])) > 0.5*60*60)
echo('
   		  <td style="text-align: left" class="hide-phone">
			<a class="small yellow button" href="#" onclick="return false">-</a>
		  </td>
');
else if($retailer['info']['batteryLevelPct'] > 50)
echo('
   		  <td style="text-align: left" class="hide-phone">
			<a class="small green button" href="#" onclick="return false"><i style="font-size: 1px; color:#fff">"' . sprintf('%03d', $retailer['info']['batteryLevelPct']) . '-</i>' . $retailer['info']['batteryLevelPct'] . '%</a>
		  </td>
');
else if($retailer['info']['batteryLevelPct'] > 25)
echo('
   		  <td style="text-align: left" class="hide-phone">
			<a class="small yellow button" href="#" onclick="return false"><i style="font-size: 1px; color:#fff">"' . sprintf('%03d', $retailer['info']['batteryLevelPct']) . '-</i>' . $retailer['info']['batteryLevelPct'] . '%</a>
		  </td>
');
else
echo('
   		  <td style="text-align: left" class="hide-phone">
			<a class="small red button" href="#" onclick="return false"><i style="font-size: 1px; color:#fff">"' . sprintf('%03d', $retailer['info']['batteryLevelPct']) . '-</i>' . $retailer['info']['batteryLevelPct'] . '%</a>
		  </td>
');

if(intval($retailer['info']['lastSeenByMobilock']) == 0 
	|| (time() - intval($retailer['info']['lastSeenByMobilock'])) > 0.5*60*60)
echo('
   		  <td style="text-align: left" class="hide-phone">
			<a class="small yellow button" href="#" onclick="return false"><i class="fa"></i>-</a>
		  </td>
');

else if($retailer['info']['batteryCharging'] == true)
echo('
   		  <td style="text-align: left" class="hide-phone">
			<a class="small green button" href="#" onclick="return false"><i class="fa"></i>YES</a>
		  </td>
');

else
echo('
   		  <td style="text-align: left" class="hide-phone">
			<a class="small red button" href="#" onclick="return false"><i class="fa"></i>NO</a>
		  </td>
');

if(intval($retailer['info']['lastSeenByMobilock']) == 0 
	|| (time() - intval($retailer['info']['lastSeenByMobilock'])) > 0.5*60*60)
echo('
   		  <td style="text-align: left" class="hide-phone">
			<a class="small yellow button" href="#" onclick="return false"><i class="fa"></i>-</a>
		  </td>
');

else if($retailer['info']['isTabletAtAirport'] == "Y")
echo('
   		  <td style="text-align: left" class="hide-phone">
			<a class="small green button" href="#" onclick="return false"><i class="fa"></i>YES</a>
		  </td>
');

else if($retailer['info']['isTabletAtAirport'] == "U")
echo('
   		  <td style="text-align: left" class="hide-phone">
			<a class="small orange button" href="#" onclick="return false"><i class="fa"></i>UNKNOWN</a>
		  </td>
');

else
echo('
   		  <td style="text-align: left" class="hide-phone">
			<a class="small red button" href="#" onclick="return false"><i class="fa"></i>NO</a>
		  </td>
');

if(intval($retailer['info']['lastSeenByMobilock']) == 0 
	|| (time() - intval($retailer['info']['lastSeenByMobilock'])) > 0.5*60*60)
echo('
   		  <td style="text-align: left" class="hide-phone">
			<a class="small yellow button" href="#" onclick="return false"><i class="fa"></i>-</a>
		  </td>
');

else if($retailer['info']['isLockedInMobilock'] == true)
echo('
   		  <td style="text-align: left" class="hide-phone">
			<a class="small green button" href="#" onclick="return false"><i class="fa"></i>YES</a>
		  </td>
');

else
echo('
   		  <td style="text-align: left" class="hide-phone">
			<a class="small red button" href="#" onclick="return false"><i class="fa"></i>NO</a>
		  </td>
');

   		  echo('<td style="text-align: left" class="hide-phone">&nbsp;</td>');

if(!empty($retailer['info']['mobilockLicenseAlert']))
echo('
   		  <td style="text-align: left" class="hide-phone">
			' . $retailer['info']['mobilockLicenseAlert'] . '
		  </td>
');


else if(intval($retailer['info']['lastSeenByMobilock']) == 0)
echo('
   		  <td style="text-align: left" class="hide-phone">
			<i style="color: red">Mobilock not set up</i>
		  </td>
');

else if((time() - intval($retailer['info']['lastSeenByMobilock'])) > 0.5*60*60)
echo('
   		  <td style="text-align: left" class="hide-phone">
			<i style="color: red">Mobilock inactive</i>
		  </td>
');

else
echo('
   		  <td style="text-align: left" class="hide-phone">
			&nbsp;
		  </td>
');

/*
if(strcasecmp($retailer['info']['posType'], 'app') == 0) {
	
	if($retailer['ping']['isLoggedIn'] == true)
echo('
   		  <td style="text-align: left" class="hide-phone">
			<a class="small green button" href="#" onclick="return false"><i class="fa"></i>YES</a>
		  </td>
');

	else
echo('
   		  <td style="text-align: left" class="hide-phone">
			<a class="small red button" href="#" onclick="return false"><i class="fa"></i>NO</a>
		  </td>
');
}

else
echo('
   		  <td style="text-align: left" class="hide-phone">
			<a class="small orange button" href="#" onclick="return false"><i class="fa"></i>N/A</a>
		  </td>
');
*/

/*
if(strcasecmp($retailer['info']['posType'], 'slack') == 0)
echo('
		  <td class="hide-phone">
			<div id="link-wrapper-message-' . $uniqueId . '" style="display: none">
				<h7 id="link-wrapper-message-text-' . $uniqueId . '" style="text-transform: none" class="inprocess"></h7>
			</div>
			<div id="link-wrapper-button-' . $uniqueId . '">
				<a href="" id="' . $uniqueId . '" onclick="ajaxConnect(\'' . $urlToPingSlackLink . '\', \'' . $uniqueId . '\', \'Sending...\', \' you want to send text message to ' . addslashes($retailer['info']['retailerName']) .' (' . addslashes($retailer['info']['location']) . ')' . '\');return false;"><i class="fa"></i>Send Test Msg</a>
			</div>
		  </td>
		  <td class="hide-phone">
			<div>&nbsp;</div>
		  </td>
');
*/

if(strcasecmp($retailer['info']['posType'], 'app') == 0) {
	
	if($retailer['ping']['isClosedEarly'] == true
		|| $retailer['ping']['isBeingClosedEarly'] == true) {
echo('
		  <td class="hide-phone">
			<div id="link-wrapper-message-' . $uniqueId . '" style="display: none">
				<h7 id="link-wrapper-message-text-' . $uniqueId . '" style="text-transform: none" class="inprocess"></h7>
			</div>
			<div id="link-wrapper-button-' . $uniqueId . '">
				<a href="" id="' . $uniqueId . '" onclick="ajaxConnect(\'' . $urlToReOpenPOSLink . '\', \'' . $uniqueId . '\', \'Requesting...\', \' you want to reopen the Terminal for ' . addslashes($retailer['info']['retailerName']) .' (' . addslashes($retailer['info']['location']) . ')' . '\');return false;"><i class="fa"></i>ReOpen</a>
			</div>
		  </td>
		  <td class="hide-phone">
			<div>&nbsp;</div>
		  </td>	
');
	}

	else {
echo('
		  <td class="hide-phone">
			<div id="link-wrapper-message-' . $uniqueId . '" style="display: none">
				<h7 id="link-wrapper-message-text-' . $uniqueId . '" style="text-transform: none" class="inprocess"></h7>
			</div>
			<div id="link-wrapper-button-' . $uniqueId . '">
				<a href="" id="' . $uniqueId . '" onclick="ajaxConnect(\'' . $urlToEarlyClosePOSLink . '\', \'' . $uniqueId . '\', \'Requesting...\', \' you want to close the Terminal for ' . addslashes($retailer['info']['retailerName']) .' (' . addslashes($retailer['info']['location']) . ') for the day' . '\');return false;"><i class="fa"></i>Close for day</a>
			</div>
 		  </td>
		  <td class="hide-phone">
			<div id="link-wrapper-message-' . 'close-' . $uniqueId . '" style="display: none">
				<h7 id="link-wrapper-message-text-' . 'close-' . $uniqueId . '" style="text-transform: none" class="inprocess"></h7>
			</div>
			<div id="link-wrapper-button-' . 'close-' . $uniqueId . '">
				<a href="" id="' . 'close-' . $uniqueId . '" onclick="ajaxConnectUponInput(\'' . $urlToEarlyClosePOSLink . '\', \'' . 'close-' . $uniqueId . '\', \'Requesting...\', \' you want to close the Terminal for ' . addslashes($retailer['info']['retailerName']) .' (' . addslashes($retailer['info']['location']) . ')' . '\', \'Close POS until the midnight of the entered date. Enter date as MMDDYYYY \(e.g. Feb 4 2018 as 02042018\) format\', \' \', \'closeUntilDate\');return false;" title="Close Retailer for more than a day, up to 14 days"><i class="fa"></i>&gt;1 day</a>
			</div>
		  </td>
');
	}
}
else {
echo('
		  <td class="hide-phone">
			<div>&nbsp;</div>
		  </td>
		  <td class="hide-phone">
			<div>&nbsp;</div>
		  </td>
');
}


echo('
		</tr>
');
	}

?>
	  </tbody>
	</table>
	</div>
	</div>
	<!-- Tabs: Tablet List - End -->
	

</div> <!-- End Grid -->
<script type="text/javascript" src="../js/link.submit.js"></script>
</body>
</html>
