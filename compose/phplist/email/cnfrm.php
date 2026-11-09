<?php
	// Confirmation of subscription from email link.
	require_once('utility.php');
	require('config.php');
	require_once('class.BalanceDB.php');
	global $balanceDB;
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <link href="./css/jquery.stepy.css" rel="stylesheet" type="text/css" />
<?php
if (isMobileDevice()){
	print('<link rel="stylesheet" type="text/css" href="./css/mobile.css" />');
} else {
	print('<link rel="stylesheet" type="text/css" href="./css/desktop.css" />');
}
?>
	<title>Load Limits Subscription Service</title>
</head>
<body>

<?php
	require_once ("http_post.php");
	include("header.php");

	echo '<div id="subscribe_confirm">';

	$uid = "";
	if (isset($_GET['uid']) && strlen($_GET['uid']) > 0){
		$uid = $_GET['uid'];
	} else {
		// ERROR!!!  No uid provided
		echo "<h1>Could not confirm your Load Limits e-mail subscription.</h1>";
		echo "<br/>";
		echo "<ul><li>No identification number provided in the URL.  Please try to copy/paste the provided URL into the browser address bar manually and try again.</li></ul>";
	}

	$srvrKey = "0";

	if (strlen($uid) > 0 && strlen($srvrKey) > 0){
		// Check if this user is already confirmed
		$prefs = $balanceDB->getUserPrefs($uid, 0);
		if ($prefs["confirmed"] == 1){
			$urlUid = "&uid=".$uid;
			header("Location: cmplt.php?result=".$PROCESS_CODES["FAILURE_ALREADYCONFIRMED"].$urlUid."&s=0");
		} else {

			try {
				$param = array();
			    $res = http_post($balanceDB->getPhplistUrl($srvrKey)."?p=confirm&uid=".$uid, $param);
			    if (isset($res) && $res["content"] != ""
			    		&& strpos($res["content"], "Thank you for confirming your subscription") !== false){
					$list = midString($res["content"], '<li class="list">', '<div');
					echo "<h1>Thank you for confirming your Load Restriction notification subscription.</h1>";
					echo "<br/>";
					echo "<ul><li>You will start receiving Load Restriction notification emails for future updates based on your indicated preferences</li>";
					echo '<li>Current load restrictions can also be found on our website at: '.
						'<a href="https://www.th.gov.bc.ca/bchighways/loadrestrictions/loadrestrictions.htm">'.
						'https://www.th.gov.bc.ca/bchighways/loadrestrictions/loadrestrictions.htm</a></li></ul>';

					// Deal with potential email address change confirmation case
	//				try {
	//					if (isEmailChangedConfirmation($uid)){
	//						unsubscribeOldEmail($uid);
	//					}
	//				} catch (Exception $e){
	//					echo "<p> <span class='error_msg'>Encountered an error unsubscribing your old address.</span>";
	//				}

			    } else {
			    	// ERROR
			    	$errMsg = midString($res['content'], '<h1>', '</h1>');
			    	$error = midString($res['content'], '</h1>', '<P>');
			    	if ($errMsg == ""){
			    		$errMsg = "You must complete the Registration Process again.";
			    		$error = "It has been more than 2 days since your initial registration for the Load Restriction notification service. You will have to complete the registration process again.";
			    	}
					echo '<br/><h1>'.$errMsg.'</h1>';
					echo '<br/>';
					echo '<ul><li><span class="confirm_error">'.$error.'</span></li>';
					echo '<li>To register again, please go here: '.anchor(resetUrl(curPageURL(true), "cnfrm.php", "s")).'</li><ul>';
			    }
			} catch (Exception $e) {
				echo "<h1>An internal error has been encountered</h1>";
				echo "<br/>";
				echo "<ul><li>Sorry for the inconvenience!  Please try confirming your subscription again in a few minutes using the provided link in your email.</li></ul>";
			}
		}
	} else {
		// We need to tell the user that they have to include the uid in the url
	}

	echo "</div>";


	/**
	 * Extract string from between two other strings
	 */
	function midString($input, $start, $end){
		if ($input == "" || $start == "" || $end == ""){
			return "";
		}
		$pos1 = stripos($input, $start) + strlen($start);
		$pos2 = stripos($input, $end, $pos1);
		$num = $pos2 - $pos1;
		return substr($input, $pos1, $num);
	}
?>

</body>