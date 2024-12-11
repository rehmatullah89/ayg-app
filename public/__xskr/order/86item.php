<?php

	require 'dirpath.php';
	require $dirpath . 'vendor/autoload.php';
	require $dirpath . 'core/token.php';

	ini_set('precision', 14);
	$epoch = microtime(true)*1000;
	$response = json_decode(
					getpage($env_BaseURL . '/retailer/list86'
					. '/a/' . generateAPIToken($epoch)
					. '/e/' . $epoch
					. '/u/' . '0'
					), true);

	$epoch = generateEpoch($epoch);
	$token = generateToken($epoch);
	$urlTo86TheItem = '../process/?action=86item&tokenEpoch=' . $epoch . '&token=' . $token . '&uniqueRetailerItemId=';

?>
<!DOCTYPE html>
<html>
<head>
	<!-- META -->
	<title>86 Items</title>
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
			<h4>86 Items (for today)</h4>

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

	<div id="link-wrapper-message-86item" style="display: none">
		<h7 id="link-wrapper-message-text-86item" style="text-transform: none" class="inprocess"></h7>
	</div>
	<div id="link-wrapper-button-86item">
		<form action="#">
			<input type="text" size="25" maxlength="100" name="itemId" id="input-86item" placeholder="enter item unique id" onfocus="if (this.value == 'enter item unique id') {this.value = '';}" onblur="if (this.value == '') {this.value = 'enter item unique id';}" />
			<a class="small orange button" href="#" onclick="ajax86ItemConnect('<?php echo($urlTo86TheItem); ?>', '86item');return false;"><i class="fa"></i> 86 item &gt;&gt;</a>
		</form>
	</div>

	<!-- Tabs -->
	<ul class="tabs right">
		<li><a href="#table-pos">86ed Items (<?php echo(count($response)); ?>)</a></li>
	</ul>

	<!-- Tabs: Item List -->
	<div id="table-pos" class="tab-content">
	<div class="col_12">
	<table class="sortable" cellspacing="0" cellpadding="0">
	  <thead>
		<tr>
		  <th>Retailer</th>
		  <th class="hide-phone">Location</th>
		  <th>Item</th>
		  <th class="hide-phone">Item Id</th>
		  <th>&nbsp;</th>
		</tr>
	  </thead>
	  <tbody>
<?php

	if(count($response) < 1) {
		
echo('
		<tr>
		  <td colspan="5" style="text-align: center; color: #ddd"><h4>No 86ed Items!</h4></td>
		</tr>
');
	}

	foreach($response as $item) {

		ini_set('precision', 14); 
		$epoch = generateEpoch($epoch);
		$token = generateToken($epoch);
		$urlToDel86Item = '../process/?action=86itemRemove&tokenEpoch=' . $epoch . '&token=' . $token . '&uniqueRetailerItemId=' . $item['uniqueRetailerItemId'];

echo('
		<tr>
		  <td>' . $item['retailerName'] . '</td>
		  <td class="hide-phone">' . $item['location'] . '</td>
		  <td>' . $item['itemName'] . '</td>
		  <td class="hide-phone">' . $item['uniqueRetailerItemId'] . '</td>
');

echo('
		  <td class="hide-phone">
			<div id="link-wrapper-message-' . $item['uniqueRetailerItemId'] . '" style="display: none">
				<h7 id="link-wrapper-message-text-' . $item['uniqueRetailerItemId'] . '" style="text-transform: none" class="inprocess"></h7>
			</div>
			<div id="link-wrapper-button-' . $item['uniqueRetailerItemId'] . '">
				<a href="" id="' . $item['uniqueRetailerItemId'] . '" onclick="ajaxConnect(\'' . $urlToDel86Item . '\', \'' . $item['uniqueRetailerItemId'] . '\', \'Requesting...\', \' you want to put the item back in service?\');return false;"><i class="fa"></i>Remove 86</a>
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
	<!-- Tabs: 86 Item List - End -->
	
</div> <!-- End Grid -->
<script type="text/javascript" src="../js/link.submit.js"></script>
</body>
</html>
