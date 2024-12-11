
var requestRunning = false

function handleAjaxResponse(link_attr_id, responseStatus, responseMessage) {
	
	$('#link-wrapper-message-text-' + link_attr_id).show();
	$('#link-wrapper-message-text-' + link_attr_id).text(responseMessage);
	
	switch(responseStatus) {
		
		// Success
		case 1:
			$('#link-wrapper-button-' + link_attr_id).hide();
			$('#link-wrapper-message-text-' + link_attr_id).attr('class', 'success');
			break;

		// Failed
		default:
			$('#link-wrapper-button-' + link_attr_id).show();
			$('#link-wrapper-message-text-' + link_attr_id).attr('class', 'error');
			break;
	}
	
	return false;
}

function ajaxConnectUponInput(json_url, link_attr_id, status_text, full_name, input_question, input_default_text, input_field_name) {
		
	var inputValue = prompt(input_question, input_default_text);

    if (inputValue != null) {

    	json_url = json_url + '&' + input_field_name + '=' + inputValue;
    	ajaxConnect(json_url, link_attr_id, status_text, full_name);
    }
    else {

    	return false;
    }
}

function ajaxFormRequest(json_url, orderId, formFieldsToSend, confirmMessage) {
		
	if(!confirm(confirmMessage)) {
		
		return false;
	}
	
	if (requestRunning) { // don't do anything if an AJAX request is pending

		return false;
	}

	for(i = 0; i < formFieldsToSend.length; i++) { 

	    json_url = json_url + '&' + formFieldsToSend[i] + '=' + document.getElementById(formFieldsToSend[i]).value;
	}

	// document.getElementById("cancelReason").value = json_url;
	// alert(json_url);
	// return false;

	requestRunning = true;

    // Send data to server through the ajax call
    // action is functionality we want to call and outputJSON is our data
        $.ajax({url: json_url,
            type: 'GET',                  
            async: 'true',
            dataType: 'json',
            beforeSend: function() {
                // This callback function will trigger before data is sent
				$('#link-wrapper-button').hide();
				$('#link-wrapper-message').show();
				$('#link-wrapper-message-text').text('Requesting...');
				$('#link-wrapper-message-text').attr('class', 'inprocess');
            },
            complete: function() {
                // This callback function will trigger on data sent/received complete
            },
            success: function (result) {

                if(result.json_resp_status == 1) {
					// Successful
					$('#link-wrapper-button').hide();
					$('#link-wrapper-message').show();
					$('#link-wrapper-extraoptions').show();
					$('#link-wrapper-message-text').text(result.json_resp_message);
					$('#link-wrapper-message-text').attr('class', 'success');
                } 
                else if(result.json_resp_status == 0) {
					// Request Pass unsuccessful
					$('#link-wrapper-button').show();
					$('#link-wrapper-extraoptions').show();
					$('#link-wrapper-message').show();
					$('#link-wrapper-message-text').text(result.json_resp_message);
					$('#link-wrapper-message-text').attr('class', 'error');
                }
                else {
					// Request Pass unsuccessful and error was not parseable
					console.log(result);
					$('#link-wrapper-button').show();
					$('#link-wrapper-extraoptions').show();
					$('#link-wrapper-message').show();
					$('#link-wrapper-message-text').text('Unable to connect, try again.'+'(Error Details: '+ result +' F)');
					$('#link-wrapper-message-text').attr('class', 'error');
                }
            },
            error: function (request,error,errorThrown) {
                // This callback function will trigger on unsuccessful action
				$('#link-wrapper-button').show();
				$('#link-wrapper-extraoptions').show();
				$('#link-wrapper-message').show();
				$('#link-wrapper-message-text').text('Unable to connect, try again...');
				$('#link-wrapper-message-text').attr('class', 'error');
            }
        });
		
		requestRunning = false;
		return false;
}

function ajaxConnect(json_url, link_attr_id, status_text, full_name) {
		
	if(!confirm('Are you sure - ' + full_name + '?')) {
		
		return false;
	}
	
	if (requestRunning) { // don't do anything if an AJAX request is pending

		return false;
	}

	requestRunning = true;

    // Send data to server through the ajax call
    // action is functionality we want to call and outputJSON is our data
        $.ajax({url: json_url,
            type: 'GET',                  
            async: 'true',
            dataType: 'json',
            beforeSend: function() {
                // This callback function will trigger before data is sent
				$('#link-wrapper-button-' + link_attr_id).hide();
				$('#link-wrapper-message-' + link_attr_id).show();
				$('#link-wrapper-message-text-' + link_attr_id).text(status_text);
				$('#link-wrapper-message-text-' + link_attr_id).attr('class', 'inprocess');
            },
            complete: function() {
                // This callback function will trigger on data sent/received complete
            },
            success: function (result) {

                if(result.json_resp_status == 1) {
					// Successful
					handleAjaxResponse(link_attr_id, 1, result.json_resp_message);
                } 
                else if(result.json_resp_status == 0) {
					// Request Pass unsuccessful
					handleAjaxResponse(link_attr_id, 0, result.json_resp_message);
                }
                else {
					// Request Pass unsuccessful and error was not parseable
					console.log(result);
					handleAjaxResponse(link_attr_id, 0, 'Reload page');
                }
            },
            error: function (request,error,errorThrown) {
                // This callback function will trigger on unsuccessful action
				handleAjaxResponse(link_attr_id, 0, 'Reload page');
            }
        });
		
		requestRunning = false;
		return false;
}

function handleAjaxResponseFor86(link_attr_id, responseStatus, responseMessage) {
	
	$('#link-wrapper-message-text-' + link_attr_id).show();
	$('#link-wrapper-message-text-' + link_attr_id).text(responseMessage);
	
	switch(responseStatus) {
		
		// Success
		case 1:
			$('#link-wrapper-button-' + link_attr_id).hide();
			$('#link-wrapper-message-text-' + link_attr_id).attr('class', 'success');
			break;

		// Failed
		default:
			$('#link-wrapper-message-text-' + link_attr_id).attr('class', 'error');
			break;
	}
	
	return false;
}

function ajax86ItemConnect(json_url, link_attr_id) {
		
	var uniqueRetailerItemId = $('#input-' + link_attr_id).val();

	if(uniqueRetailerItemId == '') {

		alert('Please enter the Unique Retailer Item Id');
		return false;
	}

	if(!confirm('Are you sure you want to 86 this item?')) {
		
		return false;
	}
	
	if (requestRunning) { // don't do anything if an AJAX request is pending

		return false;
	}

	requestRunning = true;

    // Send data to server through the ajax call
    // action is functionality we want to call and outputJSON is our data
        $.ajax({url: json_url + uniqueRetailerItemId,
            type: 'GET',                  
            async: 'true',
            dataType: 'json',
            beforeSend: function() {
                // This callback function will trigger before data is sent
				$('#link-wrapper-button-' + link_attr_id).hide();
				$('#link-wrapper-message-' + link_attr_id).show();
				$('#link-wrapper-message-text-' + link_attr_id).text("Requesting...");
				$('#link-wrapper-message-text-' + link_attr_id).attr('class', 'inprocess');
            },
            complete: function() {
                // This callback function will trigger on data sent/received complete
            },
            success: function (result) {

                if(result.json_resp_status == 1) {
					// Successful
					handleAjaxResponseFor86(link_attr_id, 1, result.json_resp_message);
                } 
                else if(result.json_resp_status == 0) {
					// Request Pass unsuccessful
					handleAjaxResponseFor86(link_attr_id, 0, result.json_resp_message);
                }
                else {
					// Request Pass unsuccessful and error was not parseable
					console.log(result);
					handleAjaxResponseFor86(link_attr_id, 0, 'Reload page');
                }
            },
            error: function (request,error,errorThrown) {
                // This callback function will trigger on unsuccessful action
				handleAjaxResponseFor86(link_attr_id, 0, 'Reload page');
            }
        });
		
		requestRunning = false;
		return false;
}
