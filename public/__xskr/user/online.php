<?php

	require 'dirpath.php';
	require $dirpath . 'vendor/autoload.php';
	require $dirpath . 'core/token.php';

	if(!isset($_GET['l'])
		|| intval($_GET['l']) <= 0
		|| intval($_GET['l']) > 96) {

		$_GET['l'] = 1;
	}

	$_GET['l'] = intval($_GET['l']) * 60;
	$withinInHrs = $_GET['l'] / 60;

	ini_set('precision', 14);
	$epoch = microtime(true)*1000;
	$response = json_decode(
					getpage($env_BaseURL . '/user/online'
					. '/a/' . generateAPIToken($epoch)
					. '/e/' . $epoch
					. '/u/' . '0'
					. '/withinMins/' . $_GET['l']
					), true);


	//var_dump($response);die();
?>
<!DOCTYPE html>
<html>
<head>
	<!-- META -->
	<title>Users Online</title>
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
			<h4>Online Users (in last <?php echo($withinInHrs); ?> hr)</h4>
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
		<li><a href="#table-online">Online (<?php echo(count($response)); ?>)</a></li>
	</ul>

	<!-- Tabs: Online users -->
	<div id="table-online" class="tab-content">
	<div class="col_12">
	<table class="sortable" cellspacing="0" cellpadding="0">
	  <thead>
		<tr>
		  <th>Name</th>
		  <th class="hide-phone">On Wifi?</th>
		  <th class="hide-phone">Device</th>
		  <th class="hide-phone">App</th>
		  <th class="hide-phone">App Ver</th>
		  <th class="hide-phone">Location</th>
		  <th class="hide-phone">Phone</th>
		  <th class="hide-phone">Email</th>
		  <th>Last session start</th>
		</tr>
	  </thead>
	  <tbody>
<?php

	if(count($response) < 1) {
		
echo('
		<tr>
		  <td colspan="9" style="text-align: center; color: #ddd"><h4>No one is online</h4></td>
		</tr>
');
	}

	$response = array_sort($response, 'checkinTimestamp', SORT_DESC);
	foreach($response as $email => $user) {
		
echo('
		<tr>
		  <td>' . $user['firstName'] . ' ' . $user['lastName'] . '</td>
		  <td class="hide-phone">' . (($user['isOnWifi'] == true) ? 'Y' : 'N') . '</td>
		  <td class="hide-phone">' . $user['deviceModel'] . '</td>
		  <td class="hide-phone">' . $user['deviceType'] . '</td>
		  <td class="hide-phone">' . $user['appVersion'] . '</td>
		  <td class="hide-phone">' . $user['locationNearAirportIataCode'] . ' (' . $user['locationState'] . '-' . $user['locationCountry'] . ')</td>
		  <th class="hide-phone">' . $user['phone'] . '</th>
		  <th class="hide-phone">' . $user['email'] . '</th>
		  <td class="hide-phone"><i style="font-size: 1px; color:#fff">"' . $user['checkinTimestamp'] . '-' . '</i>' . $user['checkinTimestampFormatted'] . '</td>
		</tr>
');

	}

?>
	  </tbody>
	</table>
	</div>
	</div>
	<!-- Tabs: Online users - End -->

</div> <!-- End Grid -->
<script type="text/javascript" src="../js/link.submit.js"></script>
</body>
</html>
