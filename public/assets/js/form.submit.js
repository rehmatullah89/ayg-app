
var requestRunning = false
var contactPageName = 'contact'
var signupPageName = 'signup'

addClickHandler(formPagesName);

function addClickHandler(formPagesName){

	$(document).on('click', '#' + formPagesName + '_submit', function() { // catch the form's submit event
		activePage = formPagesName;
		ajaxConnect(formPagesName);
		return false; // cancel original event to prevent form submitting
	});
}

function handleAjaxResponse(pageName, responseStatus, responseMessage) {
	
	formName = 'form_' + pageName;
	
	// Contact or Signup
	if(pageName == contactPageName || pageName == signupPageName) {
		
		$('#form-wrapper-message-' + formName).show();
		$('#messagetext-' + formName).text(responseMessage);
		
		switch(responseStatus) {
			
			// Success
			case 1:
				$('#messagetext-' + formName).attr('class', 'text-success');
				$('#form-wrapper-' + formName).hide();
				break;
			// Failed
			default:
				$('#messagetext-' + formName).attr('class', 'text-fail');
				$('#form-wrapper-' + formName).show();
				break;
		}
	}
	
	return false;
}

function ajaxConnect(pageName) {

		if (requestRunning) { // don't do anything if an AJAX request is pending
			return false;
		}

		var formName = 'form_' + pageName;
		var form_data = $('#' + formName).serialize();
		var form_link = $('#' + formName).attr("action");
		requestRunning = true;
        // Send data to server through the ajax call
        // action is functionality we want to call and outputJSON is our data
            $.ajax({url: form_link,
                data: form_data,
                type: 'POST',                  
                async: 'false',
                dataType: 'json',
                beforeSend: function() {
                    // This callback function will trigger before data is sent
					// $('body').addClass('ui-loading');
					$('#form-wrapper-' + formName).hide();
					$('#form-wrapper-message-' + formName).show();
					$('#messagetext-' + formName).text("Sending your request...");
					$('#messagetext-' + formName).attr('class', 'text-success');
                },
                complete: function() {
                    // This callback function will trigger on data sent/received complete
					// $('body').removeClass('ui-loading');
                },
                success: function (result) {

					//alert(result.json_resp_status + result.json_resp_message);
					// $('body').removeClass('ui-loading');
                    if(result.json_resp_status == 1) {
						// Successful
						// updateMessageBox('#message_' + pageName, 'icon-ok', 'message success', result.json_resp_message);
						handleAjaxResponse(pageName, 1, result.json_resp_message);
                    } 
					else {
						// Request Pass unsuccessful
						//alert("page naem = " + pageName);
						// updateMessageBox('#message_' + pageName, 'icon-exclamation-sign', 'message error', result.json_resp_message);
						handleAjaxResponse(pageName, 0, result.json_resp_message);
                    }
                },
                error: function (request,error,errorThrown) {
                    // This callback function will trigger on unsuccessful action
                    // updateMessageBox('#message_' + pageName, 'icon-exclamation-sign', 'message error', 'Network error has occurred! Please try again.');
					handleAjaxResponse(pageName, 0, result.json_resp_message);
                }
            });
			
			requestRunning = false;
			return false;
}
