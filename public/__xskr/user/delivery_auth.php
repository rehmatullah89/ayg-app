<?php

	require 'dirpath.php';
	require $dirpath . 'vendor/autoload.php';
	require $dirpath . 'core/token.php';

	// Slack code received
	if(isset($_GET['code'])
		&& !empty($_GET['code'])) {

		$slackCode = 1;
	}
	else {

		$slackCode = 0;
	}

?>
<!DOCTYPE html>
<html>
<head>
	<!-- META -->
	<title>Delivery Person set up</title>
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
			<h4>Set up new Delivery Person</h4>
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
			<a href='delivery_auth.php'><img src="../images/logo.png" width="70" /></a>
		  </th>
		</tr>
	  </thead>
    </table>

	<!-- Tabs -->
	<ul class="tabs right">
		<li><a href="#table-online">New Delivery Person</a></li>
	</ul>

	<div id="table-online" class="tab-content">
	<div class="col_12">
	<table class="sortable" cellspacing="0" cellpadding="0">
	  <thead>
		<tr>
		  <td><b>Step 1</b> - Login to Slack</td>
		</tr>
		<tr>
<?php

	if($slackCode == 1) {

?>
		  <td><b>Step 2</b> - Create channel pairing token, <b>complete</b>.</td>
<?php

	}
	else {
?>

		  <td><b>Step 2</b> - Create channel pairing token, <a href="https://slack.com/oauth/authorize?scope=incoming-webhook&client_id=<?php echo($GLOBALS['env_SlackClientId']); ?>">start</a></td>
<?php

	}

?>
		</tr>
		<tr>
<?php

	if($slackCode == 1) {

		ini_set('precision', 14); 
		$epoch = generateEpoch($epoch);
		$token = generateToken($epoch);
		$urlLink = "../process/?action=getDeliverySlackUrl&tokenEpoch=" . $epoch . '&token=' . $token . '&code=' . $_GET['code'];
?>
	  <td>
		<b>Step 3</b> - Generate Slack URL, 
			<br /><br />
			<div id="link-wrapper-message-<?php $_GET['code'] ?>" style="display: none">
				<h7 id="link-wrapper-message-text-<?php $_GET['code'] ?>" style="text-transform: none" class="inprocess"></h7>
			</div>

			<div id="link-wrapper-button-<?php $_GET['code'] ?>">
				<a class="small blue button" href="" id="<?php $_GET['code'] ?>" onclick="ajaxConnect('<?php echo($urlLink); ?>', '<?php $_GET['code'] ?>', 'Requesting...', 'you want to generate the Slack URL');return false;"><i class="fa fa-check"></i> start</a>
			</div>
	  </td>

<?php
	}
	else {
?>
	  <td><b>Step 3</b> - Generate Slack URL, <i>awaiting Step 2 completion</i></td>

<?php
	}
?>
		</tr>
	  </thead>
	</table>
	</div>
	</div>

</div> <!-- End Grid -->
<script type="text/javascript" src="../js/link.submit.js"></script>
</body>
</html>
