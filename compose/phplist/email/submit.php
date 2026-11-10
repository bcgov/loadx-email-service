<?php
	// Deals with jquery stepy fieldset process and final submit
	// processing with phplist and call to display results.
require_once('class.BalanceDB.php');
require_once('utility.php');
require('http_post.php');
//require('prefs.php');

global $SCREEN_MODE;
$HIDE_INPUT = false;	// Define globally.
global $unsubscribeMode;	// Defined on each screen

require('config.php');
global $SPAGE_ID;
global $PROCESS_CODES;
global $balanceDB;


/* Condition: NOT SUBMIT process */
if (!isset($_POST['submit'])){
?>
    <style type="text/css">
        td
        {
            vertical-align: top;
            white-space: nowrap;
        }
    </style>
    <script src="js/jquery.1.4.2.min.js" type="text/javascript"></script>
<?php
	if ($SCREEN_MODE != "unsubscribe")
	{
?>
    <!-- Following for subscribe and update processes. -->
    <script src="js/jquery.defButton.js" type="text/javascript"></script>
    <script src="js/jquery.validate.min.js" type="text/javascript"></script>
    <script src="js/subscribe.js" type="text/javascript"></script>
<?php
	}
} else {
	$srvrKey = "";		// Balanced server id.
	$urlSrvrkey = "";
	$email = "";
	$urlUid = "";
	$bareUid = "";
	$failureUrl = "";
	$fakeUpdate = false;	// Used in resend process.

	/* Condition: SUBMIT process */
    $postData = array();

    $process = $_POST["process_type"];


	// Get any url parameters
	$urlParamFlag = false;
	if (isset($_GET['uid'])){
		$bareUid = $_GET['uid'];
		$urlUid = "&uid=".$bareUid;
	}

	// Only one server used.
	$srvrKey = "0";
	$urlSrvrkey = "&s=0";

	if (strlen($bareUid) > 0 && strlen($srvrKey) > 0){
		$urlParamFlag = true;	// We have both parameters
	}

	if ($process != "unsubscribe"){		// Common items to all but unsubscribe
		if ($process == "preferences"){
			// 'email' is missing because it's disabled?
		    $postData["email"] = $_POST["emailconfirm"];
		    $postData["emailconfirm"] = $_POST["emailconfirm"];

		} else if ($process == "resend"){
			$fakeUpdate = true;
		    $postData["email"] = $_POST["email"];
		    $postData["emailconfirm"] = $postData["email"];

		    if(($srvrKey = $balanceDB->getServerKeyByEmail($postData['email'])) !== false){
		    	if (($bareUid = $balanceDB->findUIDByEmail($postData['email'], $srvrKey)) !== false) {
		    		$urlUid = "&uid=".$bareUid;

					// Retrieve saved values used to populate the reservered fields with it.
					if(($savedPrefs = $balanceDB->getUserPrefs($bareUid, $srvrKey)) !== false) {
						$_POST["usrpref"] = "NeworUpdated,".$savedPrefs['frequency'];	// frequency
						$_POST["htmlemail"] = $savedPrefs['htmlemail'];
						$_POST["servicearea"] = $savedPrefs['servicearea'];
					}
		    	}
		    } else {
		    	// Email not found
	    		header("Location: cmplt.php?result=".$PROCESS_CODES["FAILURE_EMAILNOTFOUND"]);
				return;
		    }

		} else {
	       $postData["email"] = $_POST["email"];
	       $postData["emailconfirm"] = $_POST["emailconfirm"];
		}

		$postData["htmlemail"] = $_POST["htmlemail"];

		$postData["rssfrequency"] = "immediately";	// Message frequency

		// Attributes in phplist database
    	$postData["attribute1"] = isset($_POST["ServiceArea"]) ? $_POST["ServiceArea"] : array();

	    $listNms = array(1=>"loadrestrictions");
	    $postData["listname"] = $listNms;
	    $postData["list"] = array(1=>"signup");

	} else {
		// Unsubscribe processing
	}



	// Check for required parameters, etc.
	switch ($process){
		case "subscribe":
	    	$srvrKey = $balanceDB->getBalancedServerKey();	// For new users.
		    // Make sure this email address is not already used.
		    if($balanceDB->isEmailAddressUsed($postData['email']) == true){
    			// Report this and have the user enter something else
	    		header("Location: cmplt.php?result=".$PROCESS_CODES["FAILURE_EMAILINUSE"]);
				return;
		    }
    		$postData["subscribe"] = "Subscribe to the Selected Newsletters";
	    	break;

		case "resend":
			$postData["update"] = "update";		// Faking an update
			$process = "preferences";
			break;

		case "preferences":
			if (!$urlParamFlag){
				$failureUrl = "result=".$PROCESS_CODES["FAILURE_UPDATE"];
   				header("Location: cmplt.php?".$failureUrl.$urlUid.$urlSrvrkey);
				return;
			} else {
				$postData["update"] = "update";
			}
			break;

		case "unsubscribe":
			if (!$urlParamFlag){
				$failureUrl = "result=".$PROCESS_CODES["FAILURE_UNSUBSCRIBE"];
   				header("Location: cmplt.php?".$failureUrl.$urlUid.$urlSrvrkey);
				return;
			} else {
	    		$postData["unsubscribe"] = "unsubscribe";
	    		$unsub_email = $balanceDB->findEmailByUID($bareUid, $srvrKey);
	    		if ($unsub_email != false){
	    			$postData["unsubscribeemail"] = $unsub_email;
	    		} else {
	    			$postData["unsubscribeemail"] = "no@email.com";
	    		}
				$postData["unsubscribereason"] = $_POST["unsubscribereason"];
			}
			break;

		default:
			break;
	}



	// Contact php_list with our data.
	$res = http_post($balanceDB->getPhplistUrl($srvrKey)."?p={$process}&id=".
											$SPAGE_ID.$urlUid.$email, $postData);

	$resultArray = getResult($process, $res);

	switch($process){
		case "subscribe":
			$retry = false;
			$loopCnt = 1;
			$serverKeyArray = array_keys($balanceDB->getBalancedServerSortedArray());	// Ordered by capacity

			do {	// Retry with another server if we failed to get communication
				if (isset($resultArray['retry']) && $resultArray['retry'] === true){
					// Try the next available server
					if ($loopCnt < count($serverKeyArray)){
						$retry = true;
						$srvrKey = $serverKeyArray[$loopCnt++];
						$res = http_post($balanceDB->getPhplistUrl($srvrKey)."?p={$process}&id=".
																$SPAGE_ID.$urlUid.$email, $postData);
						$resultArray = getResult($process, $res);
					} else {
						break;	// Break do/while loop
					}
				} else {
					if ($resultArray['code'] == $PROCESS_CODES["SUCCESS_SUBSCRIBE"]){
						$balanceDB->addToMaster($postData["email"], $srvrKey);
					}
				}
			} while($retry);
			break;

		case "resend":
		case "preferences":
			// No master table processing required.
			break;

		case "unsubscribe":
			if ($resultArray['code'] == $PROCESS_CODES["SUCCESS_UNSUBSCRIBE"]){
				$balanceDB->deleteFromMasterByUid($bareUid, $srvrKey);
			}
			break;
	}

	$HIDE_INPUT = true;		// Hides input fields

	// There may have been some optional extra url parameters passed back.
	if (isset($resultArray['extra'])){
		$extra = $resultArray['extra'];
	} else {
		$extra = "";
	}

	// Special case where we want to reset the processing codes.
	if ($process == "preferences" && $fakeUpdate == true){
		switch($resultArray["code"]){
			case $PROCESS_CODES["SUCCESS_UPDATE"]:
				$resultArray["code"] = $PROCESS_CODES["SUCCESS_RESEND"];
				break;
			case $PROCESS_CODES["FAILURE_UPDATE"]:
				$resultArray["code"] = $PROCESS_CODES["FAILURE_RESEND"];
				break;
		}
	}

	// Redirect with appropriate message information.
	header("Location: cmplt.php?result=".$resultArray["code"].$extra.$urlUid."&s=".$srvrKey);
}


/**
 * Where we can modify/replace content returned by phplist.
 * @param string $process Name of process we are in.
 * @param array $result Results from http_post function.
 * @return Error/success code value and any extra url data if available.
 */
function getResult($process, $result){
	global $PROCESS_CODES;
	$retVal = array();
	$html = null;

	if (!empty($result)){
		$html = $result['content'];
	}

	switch($process){
		case "subscribe":
			if ($html){
				if (strpos($html, "Your e-mail address has been added to our system.") != false){
					$retVal['code'] = $PROCESS_CODES["SUCCESS_SUBSCRIBE"];
				} else if(strpos($html, "your confirmation failed") !== false){
					if (strpos($html, "sending the email to request your confirmation failed") !== false){
						$retVal['extra'] = "&failType=mail";
					}
					$retVal['code'] = $PROCESS_CODES["FAILURE_SUBSCRIBE"];
				} else if (strpos($html, "Could not instantiate mail function") != false){
					$retVal['code'] = $PROCESS_CODES["FAILURE_SUBSCRIBE"];
				} else if (strpos($html, "Could not connect to SMTP host") != false){
					$retVal['code'] = $PROCESS_CODES["FAILURE_SUBSCRIBE"];
				} else if ($html){
					$retVal['code'] = $PROCESS_CODES["FAILURE_SUBSCRIBE"];
					$retVal['retry'] = true;
				}
			} else {
				$retVal['code'] = $PROCESS_CODES["FAILURE_SUBSCRIBE"];
				$retVal['extra'] = "&failType=blank";
				$retVal['retry'] = true;
			}
			break;
		case "preferences":
			// Customize output
			if ($html && strpos($html, "We have updated your information") !== false){
				$retVal['code'] = $PROCESS_CODES["SUCCESS_UPDATE"];
			} else {
				$retVal['code'] = $PROCESS_CODES["FAILURE_UPDATE"];
			}
			break;
		case "unsubscribe":
			if ($html && strpos($html, "You have been unsubscribed") !== false){
				$retVal['code'] = $PROCESS_CODES["SUCCESS_UNSUBSCRIBE"];
			} else {
				$retVal['code'] = $PROCESS_CODES["FAILURE_UNSUBSCRIBE"];
			}
			break;
		default:
			// Should never get here!
			break;
	}

	return $retVal;
}
?>