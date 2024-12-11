<?php

	require 'dirpath.php';
	require $dirpath . 'vendor/autoload.php';
	require $dirpath . 'core/token.php';

	// Slack code received
	if(!isset($_GET['code'])) {

		$_GET['code'] = '';
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
		<li><a href="#table-select">SETUP</a></li>
	</ul>

	<div id="table-select" class="tab-content">
	<div class="col_12">
	<table class="sortable" cellspacing="0" cellpadding="0">
		<tr>
		  <td><a href="delivery_auth.php?code=<?php echo($_GET['code']); ?>">Delivery Person Setup &gt;&gt;</a></td>
		</tr>
	</table>
	</div>
	</div>
</div> <!-- End Grid -->
<script type="text/javascript" src="../js/link.submit.js"></script>
</body>
</html>
