/*
 * This is added here to avoid any 'console' not defined errors in IE when
 * FirePHP is active and the developers tools dialog is not open.
 */
if (!window.console){
	window.console = {};
	window.console.log = function () {};
}

// True if step visible and available for validation.
var isStep2 = function() {
	return !$("#step2").is(':hidden');
};

/**
 * Javascript for subscribe/update screens that use
 * the stepy fieldset tag for display of multiple
 * screens.
 */
    $(function() {

    	/* NEXT BUTTON */
    	$("[id^=subscribeForm-next-]").bind('click', function() {

        	/** VALIDATOR **/
            $("#subscribeForm").validate({
              errorPlacement: function(error, element) {
                  $('#subscribeForm div.validation-error').append(error);
              },
              rules: {
                  email: { 'email': true, 'required': true }
                  , emailconfirm: { equalTo: "#email" }
                  , "ServiceArea[]": { required: isStep2, minlength: 1 }
              },
              messages: {
                  email: { email: 'Invalid e-mail!'
                           , required: 'Email is required'
                  }
                  , emailconfirm: {"equalTo": 'Email addresses do not match' }
                  , "ServiceArea[]": {required: "At least one service area is required"}
              }
           });
    		
			var curId = $(this).attr('id');
			var n = parseInt(curId.match("[0-9]+(?:\.[0-9]*)?"));
    		
			if (n === 1){	// To step 2
    			if ($("#subscribeForm").validate().form()){
    				$("#step1").hide();
    				$("#step2").show();
    				$("#prefs0").focus();
    	            $("#step2").defaultButton("#subscribeForm-next-2");
    			}
    		} else if (n === 2){	// To step 3
    			if ($("#subscribeForm").validate().form()){
    				$("#step2").hide();
    				$("#step3").show();
    	            $("#step3").defaultButton("#submit");
    				setConfirmationValues();
    			}
    		} else {
    			// ERROR
    		}
    	});
    	
    	/* BACK BUTTON */
    	$("[id^=subscribeForm-back-]").bind('click', function() {
    		var curId = $(this).attr('id');
    		var n = parseInt(curId.match("[0-9]+(?:\.[0-9]*)?"));

    		if (n === 2){	// To step 1
    			if ($("#subscribeForm").validate().form()){
    				$("#step2").hide();
    				$("#step1").show();
    				$("#email").focus();
    	            $("#step1").defaultButton("#subscribeForm-next-1");
    			}
    		} else if (n === 3){	// To step 2
    			if ($("#subscribeForm").validate().form()){
    				$("#step3").hide();
    				$("#step2").show();
    				$("#prefs0").focus();
    	            $("#step2").defaultButton("#subscribeForm-next-2");
    			}
    		} else {
    			// ERROR
    		}    		
    	});
        
    	
    	/* SUBMIT BUTTON */
    	$("#subscribeForm-submit").bind('click', function() {
    		// Submit button clicked
    	});

    	
    	// CHOICE.PHP buttons
       	$("#new_subscriber").bind('click', function() {
    		window.location = "sbscrb.php";
    	});
    	
       	$("#email_links").bind('click', function() {
    		window.location = "resend.php";
    	});       	
       	
       	$("#unsubcribe_sms").bind('click', function() {
    		window.location = "unsbscrb.php";
    	});       	
    	// End of CHOICE.PHP buttons
 				
	    
	    /* Popup on mouse-over faq icons */
		$("[id^=faq_wrapper]").hover(
			function(){
				var div = $(this).find('div.jq-popup:hidden');
				div.css("top", $(this).position().top + 10);	// Position relative to image
				div.css("left", $(this).position().left + 5);	// Position relative to image
				div.fadeIn(350);
			},
			function(){
				var div = $(this).find('div.jq-popup:visible');
				div.fadeOut(350);}
		);
    });

    
    /** JQUERY ONLOAD EVENT **/
    $(document).ready(function(){
    	
    	/** LOAD PREFERENCES **/
    	if ($("#process_type").val() === "preferences"){
    		// Show screen and setup default button.
    		$("#step0").hide();
        	$("#subscribe_header1").show();
    		$("#step1").show();
			$("#htmlFormat1").focus();
            $("#step1").defaultButton("#subscribeForm-next-1");
    		
    		// Read php loaded values
    		var frequency = $("#prefs_frequency").val();
    		var areas = $("#prefs_areas").val().split(",");

    		// Need to set these to avoid validation errors.
    		$("#email").val($("#prefs_email").val());
    		$("#emailconfirm").val($("#prefs_email").val());
    		
    		// Make email address fields read-only
    		$("#email").attr('disabled', true);
    		$("#emailconfirmlbl").hide();
    		$("#emailconfirm").hide();
    		    		
    	    // Set Area checkboxes or Route comboboxes
       	    $('[id^=ServiceArea]').each(function(i){
   				$(this).attr("checked", (jQuery.inArray($(this).val(), areas) !== -1));
       	    });        	
    	} else if ($("#process_type").val() === "subscribe"){
    		// Normal process starts with disclaimer page.
    		$("#dsclmr_accept_btn").focus();
            $("#step0").defaultButton("#dsclmr_accept_btn");
            
            $("#dsclmr_accept_btn").bind('click', function() {
            	$("#step0").hide();		// Hide disclaimer page
            	$("#subscribe_header1").show();
            	$("#step1").show();
            	$("#email").focus();
                $("#step1").defaultButton("#subscribeForm-next-1");
            });
    	}
    });
    
    
    
    /**
     * Toggle checkboxes between 'All' and specific items.
     * @param n identifier
     * @return
     */
    function toggleCheckBox(el) {
    	var elId = jQuery(el).attr("id");
    	if (elId){
			var num = elId.match("[0-9]+(?:\.[0-9]*)?");
			var id = elId.split(num)[0];
			if (num === 0){
				// 'All' checkbox checked so uncheck all others.
	        	$('input[name="' + id + '[]"][id!="' + id + '0"]').each(
	        		function(index) {
	        			if ($(this).is(':checked') === true){
	        				$(this).attr("checked", false);
	        			}
	        		});
	        } else {
	        	// Uncheck the 'All' checkbox
	        	$('#' + id + '0').attr("checked", false);
	        }
    	}
    }

    
   
            
    /**
     * Values displayed for confirmation page before submit.
     */
	function setConfirmationValues(){
		// Email address
		$("#cfrm_email").html($("#email").val());

		// Email format
		if ($("#htmlFormat1").is(':checked')){
			$("#cfrm_format").html("HTML");
		} else {
			$("#cfrm_format").html("Plain Text");				
		}

		// Subscription preference
		if ($("#prefs0").is(':checked')){
			$("#cfrm_pref").html("Immediately");	
		} else if ($("#prefs1").is(':checked')){
			$("#cfrm_pref").html("Hourly");	
		} else if ($("#prefs2").is(':checked')){
			$("#cfrm_pref").html("Daily");	
		}


		var value = "";
		var srvcAreaArray = new Array ("South Vancouver Island", "Central Vancouver Island", "North Vancouver Island", "Howe Sound", 
									  "Sunshine Coast", "Lower Mainland", "Fraser Valley", "South Okanagan", "Kootenay Boundary", 
									  "Central Kootenay", "East Kootenay", "Selkirk", "Okanagan-Shuswap", "Nicola", "Thompson", 
									  "South Cariboo", "Central Cariboo", "North Cariboo", "Fort George", "Robson", "South Peace", 
									  "North Peace", "Nechako", "Lakes", "Bulkley Nass", "Skeena", "North Coast", "Stikine");
		for (var x = 0; x <= (srvcAreaArray.length-1); x++){
			if ($("#ServiceArea"+x).is(':checked')) {
				// alert("Array[" + x + "] = " +  srvcAreaArray[x]);
				if (value.length > 0){value += ",<br/>";}
				value += (x+1) + ": " + srvcAreaArray[x];
			}
		}					

		//check strlen of value to see if any ServiceAreas were checked ...
		if (value.length === 0 ) { 
			value = '<span style="color:red;">At least one service area is required.  Please select the \"BACK\" button and check one or more service areas.</span>';
		}
		$("#result_area_route").html("Service Area(s):");
		$("#cfrm_area_route").html(value);
		value = "";
	}	
	
	
	// Added here for use by older browsers versions and IE
	if (!Array.prototype.indexOf)
	{
	  Array.prototype.indexOf = function(elt /*, from*/)
	  {
	    var len = this.length;

	    var from = Number(arguments[1]) || 0;
	    from = (from < 0) ? Math.ceil(from) : Math.floor(from);
	    if (from < 0){
	    	from += len;
	    }
	    for (; from < len; from++)
	    {
	      if (from in this && this[from] === elt){
	    	  return from;
	      }
	    }
	    return -1;
	  };
	}