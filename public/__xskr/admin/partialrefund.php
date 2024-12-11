<?php

	require 'dirpath.php';
	require $dirpath . 'vendor/autoload.php';
	require $dirpath . 'core/token.php';

	if(!isset($_GET['orderId'])) {

		$responseText = "";
		$step = 1;
	}
	else {

		$_GET['orderId'] = intval($_GET['orderId']);

		ini_set('precision', 14);
		$epoch = microtime(true)*1000;
		$response = json_decode(
						getpage($env_BaseURL . '/order/info'
						. '/a/' . generateAPIToken($epoch)
						. '/e/' . $epoch
						. '/u/' . '0'
						. '/orderId/' . $_GET['orderId']
						), true);

		if(isset($response["json_resp_status"])
			&& intval($response["json_resp_status"]) == 0) {

			$responseText = $response["json_resp_message"];
			$step = 1;
		}
		else {

			$epoch = generateEpoch($epoch);
			$token = generateToken($epoch);
			$json_url = '../process/?action=orderPartialRefund&tokenEpoch=' . $epoch . '&token=' . $token;

			$step = 2;
		}
	}

?>
<!DOCTYPE html>
<html>
<head>
	<!-- META -->
	<title>Partial Refund Order</title>
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
			<h4>Partial Refund</h4>

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


<?php

if($step == 1) {
	
?>

	<div id="link-wrapper-message-partialrefund" style="display: inline">
		<h7 id="link-wrapper-message-text-partialrefund" style="text-transform: inline" class="inprocess"><?php echo($responseText); ?></h7>
	</div>
	<div id="link-wrapper-button-partialrefund">
		<form action="partialrefund.php" method="get">
			<input type="text" size="25" maxlength="10" name="orderId" placeholder="enter order id" onfocus="if (this.value == 'enter order id') {this.value = '';}" onblur="if (this.value == '') {this.value = 'enter order id';}" />
			<input type="submit" class="small orange button" value=" Begin refund &gt;&gt; " />
		</form>
	</div>

<!-- JMD -->
<?php

}

else {

	if(!isset($response['objectId'])) {

		$response['objectId'] = '0';
	}

	ini_set('precision', 14); 
	$epoch = generateEpoch($epoch);
	$token = generateToken($epoch);
	$urlLinkForInvoice = '../process/?action=fetchInvoice&tokenEpoch=' . $epoch . '&token=' . $token . '&orderId=' . $response['objectId'];

?>

	<!-- Tabs -->
	<ul class="tabs right">
		<li><a href="#table-active">Partial Refund Order - <?php echo($_GET['orderId']); ?></a></li>
	</ul>

	<!-- Tabs: main -->
	<div id="table-active" class="tab-content">
	<div class="col_12">
	<table class="sortable" cellspacing="0" cellpadding="0">
	  <tbody>

		<form id="partialRefundRequest">
			<div id="link-wrapper-message" style="display: none">
				<h5 id="link-wrapper-message-text" style="text-transform: none" class="inprocess"></h5>
			</div>
			<div id="link-wrapper-extraoptions" style="display: none">
				<a class="small blue button" href="../order/index.php"><i class="fa"></i> BACK TO ORDERS</a>
				<a class="small blue button" href="" onclick="location.reload();"><i class="fa"></i> RELOAD</a>
				<br /><br />
			</div>
			<div id="link-wrapper-button">
				<b>WHEN TO USE THIS FORM:</b> Only use when you must refund for an item that was not delivered (e.g. retailer was out of stock).
				<br />DO NOT use when you need to cancel the order. You must then CANCEL the order and refund the amount (partial or full);
				<br /><br />
				<b>NOTE:</b> Any refunded amount <u>will NOT</u> be reimbursed to the retailer, since this form is for retailer reasons such as as retailer out of stock or item not fulfilled.
		 		<hr>
				<b><a href="<?php echo($urlLinkForInvoice); ?>" target="_blank">View Invoice</a>
		 		<br />
				<b>Refund Order Id:</b> <?php echo($_GET['orderId']); ?>
		 		<br />
				<b>Customer:</b> <?php echo($response['customerName']); ?>
				<br />
				<b>Retailer:</b> <?php echo($response['retailerName']); ?>
		 		<br /><br />
				<b>Amount Paid:</b> <?php echo($response['totalPaid']); ?>
				<br />
				<b><u>Already Refunded Amount:</u></b> <?php echo($response['alreadyRefunded']); ?>
				<br /><br />
				<b>Refund Method:</b>
				<br />
				<select name="refundType" id="refundType">
				  <optgroup label="-- Preferred --">
				    <option value="credit">At Your Gate Credits</option>
				  </optgroup>
				  <optgroup label="-- NOT preferred (EXCEPTION cases only) --">
				    <option value="source">Payment Method (e.g. credit card)</option>
				  </optgroup>
				</select>

		 		<br /><br />
				<b>Refund amount (enter in cents; ADD TAXES, e.g. 6%):</b>
				<br />
				<input type="text" maxlength="5" name="inCents" id="inCents" size="10">

		 		<br /><br />
				<b>Brief reason for refund that will be shown to retailer (max 250 chars):</b>
				<br />
				<input type="text" maxlength="250" name="reason" id="reason" size="75">
				<br />

				<input type="hidden" value="<?php echo($_GET['orderId']); ?>" name="orderId" id="orderId">
		 		<br /><br />
				<a class="small blue button" href="../order/index.php");return false;"><i class="fa"></i> BACK TO ORDERS</a>
				<a class="small red button" href="" onclick="ajaxFormRequest('<?php echo($json_url); ?>', '<?php echo($_GET['orderId']); ?>', ['orderId', 'reason', 'inCents', 'refundType'], 'Are you sure you want to process refund for Order Id <?php echo($_GET['orderId']); ?>');return false;"><i class="fa"></i> REFUND ORDER</a>
                    <?php if($response['alreadyRefunded'] != "$0.00"){
                        ?>
                        <br/><br/>
                        <span style="color: #a52a2a;">
                        <b>Previous Refund Amount: </b> <?php echo $response['alreadyRefunded']; ?></br>
                            <b>Status: </b><?php echo (($response['orderRefundSourceAmount'] > 0 && $response['orderRefundSourceStatus']) == true?"Completed":($response['orderRefundSourceAmount'] == 0?"Completed":"Pending")); ?><br/>
                        <b>Refund Reason:</b> <?php echo $response['reasonForPartialRefund']; ?> </br>
                    </span>
                    <?php }?>
			</div>
		</form>

	  </tbody>
	</table>
	</div>
	</div>
	<!-- Tabs: main - End -->


<?php

}
	
?>
	
</div> <!-- End Grid -->
<script type="text/javascript" src="../js/link.submit.js"></script>
</body>
</html>
