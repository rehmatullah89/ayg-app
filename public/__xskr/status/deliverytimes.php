<?php

	require 'dirpath.php';
	require $dirpath . 'vendor/autoload.php';
	require $dirpath . 'core/token.php';

	ini_set('precision', 14);
	$epoch = microtime(true)*1000;

	// Airport List
	$epoch = generateEpoch($epoch);
	$token = generateToken($epoch);
	// JMD
	$urlMetadataAirports = '../process/?action=mt_airports&tokenEpoch=' . $epoch . '&token=' . $token;

	// Retailers List
	$epoch = generateEpoch($epoch);
	$token = generateToken($epoch);
	$urlMetadataRetailers = '../process/?action=mt_retailers&tokenEpoch=' . $epoch . '&token=' . $token . '&airportIataCode=';

	// Terminal Concourse List
	$epoch = generateEpoch($epoch);
	$token = generateToken($epoch);
	$urlMetadataTerminalConcourses = '../process/?action=mt_terminalconcourses&tokenEpoch=' . $epoch . '&token=' . $token . '&airportIataCode=';

	$tokenEpoch = generateEpoch($epoch);
	$token = generateToken($epoch);

?>
<!DOCTYPE html>
<html>
<head>
	<!-- META -->
	<title>Delivery - Times Override</title>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
	<meta name="description" content="" />
	
	<!-- CSS -->
	<link rel="stylesheet" type="text/css" href="../css/kickstart.css" media="all" />
	<link rel="stylesheet" type="text/css" href="../css/style.css" media="all" /> 
	<link rel="stylesheet" type="text/css" href="../library/selectize/css/selectize.default.css" media="all" /> 
	
    <link rel="icon" type="image/png" href="/images/favicon.png">
	
	<!-- Javascript -->
	<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>
	<script type="text/javascript" src="../js/kickstart.js"></script>
	<script type="text/javascript" src="../library/selectize/js/standalone/selectize.min.js"></script>
</head>
<body>
<div class="grid">
	<table class="noborder" cellspacing="0" cellpadding="0">
	  <thead>
		<tr>
		  <th>
			<h4>Delivery Times Override</h4>
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
		<li><a href="#table-add">Add Override</a></li>
		<!-- <li><a href="#table-existing">Existing Overrides</a></li> -->
	</ul>

	<!-- Tabs: Add -->
	<div id="table-add" class="tab-content">
	<div id="form-submission-label" style="display: none">
		<h5 class="loader">Requesting...</h5>
	</div>
	<span id="form-submission-container" style="display: inline">
	<div class="col_6">
				<form action="deliverytimesprocess.php" method="post" id="form-adjust">
					<div>
						<h6 class="loader" id="select-airports-loader" style="display: inline">loading...</h6>
					</div>
					<div>
						<h5 class="inactive" id="select-airports-label">Select Airport</h5>
					</div>
					<div class="sandbox">
						<label for="select-airports" style="margin-top:20px"></label>
						<select id="select-airports" name="select-airports" placeholder="Pick Airport with Delivery">
						</select>
					</div>
					<br />
					<div>
						<h6 class="loader" id="select-retailers-loader" style="display: none">loading...</h6>
					</div>
					<div>
						<!-- JMD -->
						<h5 class="inactive" id="select-retailers-label">Select one or more retailers</h5>
					</div>
					<div class="sandbox">
						<label for="select-retailers" style="margin-top:20px"></label>
						<select id="select-retailers" name="select-retailers[]" placeholder="Pick one or more Retailers"></select>
					</div>
					<br />
					<div>
						<h6 class="loader" id="select-terminalconcourses-loader" style="display: none">loading...</h6>
					</div>
					<div>
						<h5 class="inactive" id="select-terminalconcourses-label">Select one or more Delivery Locations (Terminals and Concourses)</h5>
					</div>
					<div class="sandbox">
						<label for="select-terminalconcourses" style="margin-top:20px"></label>
						<select id="select-terminalconcourses" name="select-terminalconcourses[]" placeholder="Pick one or more Terminal/Concourses"></select>
					</div>
					<br />
					<div>
						<h5 class="active">Adjustment Time (in minutes)</h5>
					</div>
					<div class="sandbox">
						<select id="select-adjustment-direction" name="select-adjustment-direction">
							<option value="i">Increase by</option>
							<option value="d">Decrease by</option>
						</select>

						<select id="select-adjustment-minutes" name="select-adjustment-minutes">
							<option value="0">(reset)</option>
							<option value="1" selected>1 minute</option>
							<option value="2">2 minutes</option>
							<option value="3">3 minutes</option>
							<option value="4">4 minutes</option>
							<option value="5">5 minutes</option>
							<option value="6">6 minutes</option>
							<option value="7">7 minutes</option>
							<option value="8">8 minutes</option>
							<option value="9">9 minutes</option>
							<option value="10">10 minutes</option>
							<option value="11">11 minutes</option>
							<option value="12">12 minutes</option>
							<option value="14">14 minutes</option>
							<option value="15">15 minutes</option>
							<option value="16">16 minutes</option>
							<option value="17">17 minutes</option>
							<option value="18">18 minutes</option>
							<option value="19">19 minutes</option>
							<option value="20">20 minutes</option>
							<option value="21">21 minutes</option>
							<option value="22">22 minutes</option>
							<option value="23">23 minutes</option>
							<option value="24">24 minutes</option>
							<option value="25">25 minutes</option>
							<option value="26">26 minutes</option>
							<option value="27">27 minutes</option>
							<option value="28">28 minutes</option>
							<option value="29">29 minutes</option>
							<option value="30">30 minutes</option>
						</select>
					</div>
					<p>&nbsp;</p>
					<div class="sandbox">
						<input type="hidden" name="step" value="1" />
						<input type="hidden" name="token" value="<?php echo($token); ?>" />
						<input type="hidden" name="tokenEpoch" value="<?php echo($tokenEpoch); ?>" />
						<input type="submit" value="Adjust" class="blue" />
						<br /><br />
						<h7><b>Note:</b> Adjustments expire in 2 hours. A Slack notification will be sent before expiry.</h7>
					</div>
				</form>
	</div>
	</div>
	</span>
	<!-- Tabs: Add - End -->
	
	<!-- Tabs: Existing List 
	<div id="table-existing" class="tab-content">
	<div class="col_12">
	<table class="sortable" cellspacing="0" cellpadding="0">
	  <thead>
		<tr>
		  <th>Airport</th>
		  <th>From Retailer</th>
		  <th>To Terminal & Concourse</th>
		  <th>Override (in mins)</th>
		  <th>Expires</th>
		</tr>
	  </thead>
	  <tbody>

	  </tbody>
	</table>
	</div>
	</div>
	 Tabs: Existing List - End -->

</div> <!-- End Grid -->

<script>


var xhr, xhr2;
var select_airports, $select_airports;
var select_retailers, $select_retailers;
var select_terminalconcourses, $select_terminalconcourses;
var url_fetch_metadata_airports = '<?php echo($urlMetadataAirports); ?>';
var url_fetch_metadata_retailers = '<?php echo($urlMetadataRetailers); ?>';
var url_fetch_metadata_terminalconcourses = '<?php echo($urlMetadataTerminalConcourses); ?>';

$select_airports = $('#select-airports').selectize({
    valueField: 'id',
    labelField: 'id',
    optgroupField: 'id',
    maxItems: 1,
    searchField: ['name', 'id'],
	render: {
	        option: function(item, escape) {
	            return '<div style="margin: 5px 5px 5px 5px">' +
	                    	'&nbsp;<span><b>' + (item.id) + '</b></span><br />' +
	                    	'&nbsp;<span>' + (item.name) + '</span>' +
	            		'</div>';
	        }
	},
    
    onInitialize: function() {

		$('#select-airports-loader').show();

        this.disable();
        this.clearOptions();
        this.load(function(callback) {
            xhr && xhr.abort();
            xhr = $.ajax({
                url: url_fetch_metadata_airports,
	            dataType: 'json',
                success: function(results) {

					$('#select-airports-loader').hide();
                    if(results.json_resp_status == 1) {
						
						alert('Reload page and try again.');
                        callback();
                    }

					$('#select-airports-label').attr('class', 'active');
                    select_airports.enable();
                    callback(results.json_resp_message);
                },
                error: function() {

					$('#select-airports-loader').hide();
					alert('Reload page and try again.');
                    callback();
                }
            })
        });
    },
    onChange: function(value) {
        if (!value.length) return;

		$('#select-retailers-loader').show();
		$('#select-retailers-label').attr('class', 'inactive');
		$('#select-terminalconcourses-label').attr('class', 'inactive');

        select_retailers.disable();
        select_retailers.clearOptions();
        select_retailers.load(function(callback) {
            xhr && xhr.abort();
            xhr = $.ajax({
                url: url_fetch_metadata_retailers + value,
	            dataType: 'json',
                success: function(results) {

					$('#select-retailers-loader').hide();
                    if(results.json_resp_status == 1) {

						alert('Reload page and try again.');
                        callback();
                    }

					$('#select-retailers-label').attr('class', 'active');
                    select_retailers.enable();

                    // Load groups
					for (i = 0; i < results.json_resp_message["optgroups"].length; i++) { 

						var option = { groupid: results.json_resp_message["optgroups"][i]["groupid"], groupname: results.json_resp_message["optgroups"][i]["groupname"]};

						select_retailers.addOptionGroup(results.json_resp_message["optgroups"][i]["groupid"], option);
					}

                    callback(results.json_resp_message["options"]);
                },
                error: function() {

					$('#select-retailers-loader').hide();
					alert('Reload page and try again.');
                <!-- JMD -->
                    callback();
                }
            })
        });

		$('#select-terminalconcourses-loader').show();

        select_terminalconcourses.disable();
        select_terminalconcourses.clearOptions();
        select_terminalconcourses.load(function(callback) {
            xhr2 && xhr2.abort();
            xhr2 = $.ajax({
                url: url_fetch_metadata_terminalconcourses + value,
	            dataType: 'json',
                success: function(results) {

                <!-- JMD -->
					$('#select-terminalconcourses-loader').hide();
                    if(results.json_resp_status == 1) {

						alert('Reload page and try again.');
                        callback();
                    }

					$('#select-terminalconcourses-label').attr('class', 'active');
                    select_terminalconcourses.enable();
                    callback(results.json_resp_message);
                },
                error: function() {

					$('#select-terminalconcourses-loader').hide();
					alert('Reload page and try again.');
                    callback();
                }
            })
        });
    }
});

$select_terminalconcourses = $('#select-terminalconcourses').selectize({
    valueField: 'id',
    labelField: 'name',
    searchField: ['name', 'id'],
	maxItems: null
});

$select_retailers = $('#select-retailers').selectize({
    valueField: 'id',
    labelField: 'name',
    optgroupField: 'groupid',
    optgroupLabelField: 'groupid',
    optgroupValueField: 'groupid',
    render: {
        optgroup_header: function(item, escape) {
            return '<div class="optgroup-header" style="background-color: #c3c6db">' + escape(item.groupname) + '</div>';
        }
    },
    searchField: ['name', 'id'],
	maxItems: null
});

select_terminalconcourses  = $select_terminalconcourses[0].selectize;
select_retailers  = $select_retailers[0].selectize;
select_airports = $select_airports[0].selectize;

select_terminalconcourses.disable();	
select_retailers.disable();	

$("#form-adjust").submit(function() {

	if(!confirm('Are you sure you want to adjust delivery times?')) {

		return false;		
	}

	$('#form-submission-label').show();
	$('#form-submission-container').hide();

	return true;
});


</script>

</body>
</html>
