<?php

	require 'dirpath.php';
	require $dirpath . 'vendor/autoload.php';
	require $dirpath . 'core/token.php';

?>
<!DOCTYPE html>
<html>
<head>
	<!-- META -->
	<title>Ops Dashboard</title>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
	<meta name="description" content="" />
	
	<!-- CSS -->
	<link rel="stylesheet" type="text/css" href="css/kickstart.css" media="all" />
	<link rel="stylesheet" type="text/css" href="css/style.css" media="all" /> 
	
    <link rel="icon" type="image/png" href="/images/favicon.png">
	
	<!-- Javascript -->
	<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>
	<script type="text/javascript" src="js/kickstart.js"></script>
</head>
<body>
<div class="grid">
	<table class="noborder" cellspacing="0" cellpadding="0">
	  <thead>
		<tr>
		  <th>
			<h4>Ops Controls</h4>
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
		  </th>
		  <th style="text-align: right">
			<a href='' onclick='self.reload();'><img src="images/logo.png" width="70" /></a>
		  </th>
		</tr>
	  </thead>
    </table>

	<!-- Tabs -->
	<div id="table-online" class="tab-content">
	<div class="col_12">
	<table class="sortable" cellspacing="0" cellpadding="0">
	  <tbody>
		<tr>
		  <td><a href='user/online.php'><b>Current &amp; Recent Online Users</b></a></td>
		</tr>
		<tr>
		  <td><a href='user/online.php?l=24'>Last 24 hrs</a> | <a href='user/online.php?l=12'>Last 12 hrs</a> | <a href='user/online.php?l=6'>Last 6 hrs</a> | <a href='user/online.php?l=3'>Last 3 hrs</a> | <a href='user/online.php?l=1'>Last hr</a></td>
		</tr>
		<tr>
		  <td>See current and recently online users. Append ?l=X to the URL to list users within X hours. By default it shows last one hour.</td>
		</tr>
	  </tbody>

	  <tbody>
		<tr>
		  <td><a href='status/pos.php'><b>POS Uptime</b></a></td>
		</tr>
		<tr>
		  <td>See POS status. Close retailers for the day (pre-scheduled time).</td>
		</tr>
		<tr>
		  <td><a href='status/delivery.php'><b>Delivery Users Online</b></a></td>
		</tr>
		<tr>
		  <td>See Delivery users online status and last ping time.</td>
		</tr>
		<tr>
		  <td><a href='status/deliverytimes.php'><b>Override Delivery Estimate Times</b></a></td>
		</tr>
		<tr>
		  <td>Increase or Decrease delivery times for a short period.</td>
		</tr>
	  </tbody>

	  <tbody>
		<tr>
		  <td><a href='order/index.php'><b>Active &amp; Recent orders</b></a></td>
		</tr>
		<tr>
		  <td><a href='order/index.php?l=24'>Last 24 hrs</a> | <a href='order/index.php?l=12'>Last 12 hrs</a> | <a href='order/index.php?l=6'>Last 6 hrs</a> | <a href='order/index.php?l=3'>Last 3 hrs</a> | <a href='order/index.php?l=1'>Last hr</a></td>
		</tr>
		<tr>
		  <td>View currently active orders and recently completed ordes. Cancel or manually push orders. View Order invoices. Append ?l=X to the URL to list orders within X hours. By default it shows last 36 hours.</td>
		</tr>
		<tr>
		  <td><a href='admin/partialrefund.php'><b>Issue full or partial refund</b></a></td>
		</tr>
		<tr>
		  <td>Only to be used for orders that won't be canceled, and because retailer didn't fully provide all items, missed SLA, provided incorrect or bad quality items. Please read full notes before using it.</td>
		</tr>
		<tr>
		  <td><a href='order/86item.php'><b>86 an item</b></a></td>
		</tr>
		<tr>
		  <td>Remove an item from availability for the day.</td>
		</tr>
	  </tbody>

	  <tbody>
		<tr>
		  <td><a href='user/auth.php'><b>Set up new Delivery user</b></a></td>
		</tr>
		<tr>
		  <td>Slack integration to set up new user accounts and channel bindings.</td>
		</tr>
	  </tbody>

	  <tbody>
		<tr>
		  <td><a href='user/mobilock.php'><b>Set up new POS</b></a></td>
		</tr>
		<tr>
		  <td>Moiblock details for the POS.</td>
		</tr>
	  </tbody>

        <tbody>
        <tr>
            <td><a href='data/update.php'><b>Data Update</b></a></td>
        </tr>
        <tr>
            <td>When data is correctly uploaded to S3, it is a place to force data update.</td>
        </tr>
        </tbody>

        <tbody>
        <tr>
            <td><a href='retailers/update.php'><b>Retailers Update</b></a></td>
        </tr>
        <tr>
            <td>When data is correctly uploaded to S3, it is a place to force retailers (operation hours) update.</td>
        </tr>
        </tbody>

        <tbody>
        <tr>
            <td><a href='coupons/update.php'><b>Coupons Update</b></a></td>
        </tr>
        <tr>
            <td>When data is correctly uploaded to S3, it is a place to force coupons update.</td>
        </tr>
        </tbody>


    </table>
	</div>
	</div>

</div> <!-- End Grid -->
<script type="text/javascript" src="js/link.submit.js"></script>
</body>
</html>
