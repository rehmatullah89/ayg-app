<?php

	require 'dirpath.php';
	require $dirpath . 'vendor/autoload.php';
	require $dirpath . 'core/token.php';

	if(!isset($_GET['orderId'])) {

		die("Reload previous page");
	}



	$_GET['orderId'] = intval($_GET['orderId']);
	$_GET['retailerName'] = sanitize(urldecode($_GET['retailerName']));
	$_GET['customerName'] = sanitize(urldecode($_GET['customerName']));

	ini_set('precision', 14); 
	$epoch = microtime(true)*1000;
	$token = generateToken($epoch);
	$json_url = '../process/?action=orderCancelAdmin&tokenEpoch=' . $epoch . '&token=' . $token . '&orderId=' . $_GET['orderId'];
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
</head>
<body>
<div class="grid">
	<table class="noborder" cellspacing="0" cellpadding="0">
	  <thead>
		<tr>
		  <th>
			<h4>Orders Admin Override Cancellation Request</h4>
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
		<li><a href="#table-active">Cancel Order - <?php echo($_GET['orderId']); ?></a></li>
	</ul>

	<!-- Tabs: main -->
	<div id="table-active" class="tab-content">
	<div class="col_12">
	<table class="sortable" cellspacing="0" cellpadding="0">
	  <tbody>

		<form id="cancelRequest">
			<div id="link-wrapper-message" style="display: none">
				<h5 id="link-wrapper-message-text" style="text-transform: none" class="inprocess"></h5>
			</div>
			<div id="link-wrapper-extraoptions" style="display: none">
				<a class="small blue button" href="../order/index.php"><i class="fa"></i> BACK TO ORDERS</a>
				<a class="small blue button" href="" onclick="location.reload();"><i class="fa"></i> RELOAD</a>
				<br /><br />
			</div>
			<div id="link-wrapper-button">
				<b>Cancel Order Id:</b> <?php echo($_GET['orderId']); ?>
		 		<br />
				<b>Amount Paid:</b> <?php echo($_GET['amountPaid']); ?>
				<br />
				<b>Retailer:</b> <?php echo($_GET['retailerName']); ?>
		 		<br />
				<b>Customer:</b> <?php echo($_GET['customerName']); ?>
				<br /><br />
				<b>Refund Method:</b>
				<br />
				<select name="refundType" id="refundType">
				  <optgroup label="-- Preferred --">
				    <option value="fullcredit">Full refund - At Your Gate Credits</option>
				    <option value="partialcredit">Partial refund - At Your Gate Credits (enter below)</option>
				  </optgroup>
				  <optgroup label="-- NOT preferred (EXCEPTION cases only) --">
				    <option value="source">Full refund - Payment Method</option>
				  </optgroup>
				</select>

		 		<br /><br />
				<b><u>(Optional):</u> Partial refund amount (enter in cents):</b>
				<br />
				<input type="text" maxlength="5" name="partialRefundAmount" id="partialRefundAmount" size="10">

		 		<br /><br />
				<b>Cancellation Reason Code:</b>
				<br />
				<select name="cancelReasonCode" id="cancelReasonCode">
				  <optgroup label="-- At Your Gate error (refund customer and retailer if they had accepted) --">
				    <option value="21">21 - No Delivery Person Available</option>
				    <option value="22">22 - Retailer Tablet is Down</option>
				    <option value="23">23 - Order stuck led to delays</option>
				    <option value="24">24 - Delivered late</option>
				    <option value="90">90 - Other reason (specify below)</option>
				  </optgroup>
				  <optgroup label="-- Retailer or Customer error (we refund customer but not retailer) --">
				    <option value="101">101 - Retailer - Out of item</option>
				    <option value="102">102 - Retailer - Kitchen Closed</option>
				    <option value="103">103 - Retailer - Closed Early</option>
				    <option value="104">104 - Retailer - Item no longer carried</option>
				    <option value="105">105 - Retailer - Accepted the order late</option>
				    <option value="110">110 - Retailer - Other reason (specify below)</option>
				    <option value="150">150 - Customer - Wanted pickup but selected delivery</option>
				    <option value="151">151 - Customer - Wanted delivery but selected pickup</option>
				    <option value="152">152 - Customer - Ordered wrong Item</option>
				    <option value="153">153 - Customer - Requested cancel - Delivery or Pickup will be too late</option>
				    <option value="154">154 - Customer - Other reason (specify below)</option>
				    <option value="190">190 - Other (specify below)</option>
				  </optgroup>
				</select>

		 		<br /><br />
				<b>Refund the retailer?</b> (This choice doesn't affect if this is due to Retailer error)
				<br />
				<select name="refundRetailer" id="refundRetailer">
				    <option value="1">YES</option>
				    <option value="0" selected>NO</option>
				  </optgroup>
				</select>

		 		<br /><br />
				<b>Brief specifics of Cancellation (max 250 chars):</b>
				<br />
				<input type="text" maxlength="250" name="cancelReason" id="cancelReason" size="75">
				<br />

				<input type="hidden" value="<?php echo($_GET['orderId']); ?>" name="orderId">
		 		<br /><br />
				<a class="small blue button" href="../order/index.php");return false;"><i class="fa"></i> BACK TO ORDERS</a>
				<a class="small red button" href="" onclick="ajaxFormRequest('<?php echo($json_url); ?>', '<?php echo($_GET['orderId']); ?>', ['cancelReason', 'cancelReasonCode', 'refundType', 'refundRetailer', 'partialRefundAmount'], 'Are you sure you want to cancel Order Id <?php echo($_GET['orderId']); ?>');return false;"><i class="fa"></i> CANCEL ORDER</a>

			</div>
		</form>

	  </tbody>
	</table>
	</div>
	</div>
	<!-- Tabs: main - End -->

</div> <!-- End Grid -->
<script type="text/javascript" src="../js/link.submit.js"></script>
</body>
</html>
