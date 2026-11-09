<?php
	// Displays success/error messages.
	// Using this page prevents accidental processing from
	// user refreshing page while still in subscribe.php

require_once('utility.php');
require_once('config.php');
global $PROCESS_CODES;

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
    <title>Subscribe to Load Limits Event Notification by Email</title>
</head>
<body>
	<?php include("header.php");?>
	<div id="subscribe_confirm">
<?php
		$html = "";
		$link = "<a href=\"".curPageURL(true)."\">".curPageURL(true)."</a>";

		if (isset($_GET['result'])){
			$flag = $_GET['result'];
			switch ($flag){
				case $PROCESS_CODES["SUCCESS_SUBSCRIBE"]:
					echo generateSubscriptionThanks();
					break;
				case $PROCESS_CODES["SUCCESS_UPDATE"]:
					echo generatePreferencesConfirmation();
					break;
				case $PROCESS_CODES["SUCCESS_UNSUBSCRIBE"]:
					echo generateUnsubscribeConfirmation();
					break;
				case $PROCESS_CODES["FAILURE_UPDATE"]:
					echo generatePreferencesFailure();
					break;
				case $PROCESS_CODES["FAILURE_UNSUBSCRIBE"]:
					echo generateUnsubscribeFailure();
					break;
				case $PROCESS_CODES["FAILURE_SUBSCRIBE"]:
					$failType = "";		// Extra optional parameter
					if (isset($_GET['failType'])) {
						$failType = $_GET['failType'];
					}
					echo generateSubscriptionFailure($failType);
					break;
				case $PROCESS_CODES['FAILURE_EMAILINUSE']:
					// Note: If we use this for the pref update screen, add an extra param
					// so that we can present the correct link with the uid and 'p' switch.
					echo generateEmailInUseFailure();
					break;
				case $PROCESS_CODES['SUCCESS_RESEND']:
					echo generateResendSuccess();
					break;
				case $PROCESS_CODES['FAILURE_RESEND']:
					echo generateResendFailure();
					break;
				case $PROCESS_CODES['FAILURE_EMAILNOTFOUND']:
					echo generateEmailNotFoundFailure();
					break;
				case $PROCESS_CODES["FAILURE_ALREADYCONFIRMED"]:
					echo generateUserAlreadyConfirmed();
					break;
				default:
					header('Location: sbscrb.php');	// Default redirect to subscribe page.
					break;
			}
		} else {
			header('Location: sbscrb.php');	// Default redirect to subscribe page.
		}
?>
	</div>
</body>
</html>

<?php
/**
 * Generate subscription thank you screen.
 */
function generateSubscriptionThanks(){
	$html = "<h1>Thank you for subscribing to Load Restrictions notifications.</h1>";
	$html .= "<ul><li>Your e-mail id has been added to our system.  You will be " .
			"e-mailed shortly with a request to confirm your subscription.</li>";
	$html .= "<br/><li>Please make sure to click the link in the e-mail within 2 days to ".
			"initiate the Load Restriction Subscription Service. If you do not click the ".
			"link in the e-mail within 2 days, you will have to complete the ".
			"registration process again.</li>";
	$html .= '<li>Return to Load Restrictions Website <a href="https://www.th.gov.bc.ca/BCHighways/loadrestrictions/loadrestrictions.htm">';
	$html .= 'https://www.th.gov.bc.ca/BCHighways/loadrestrictions/loadrestrictions.htm</a>';
	$html .= '<li><a href="javascript:window.close();">Close Window</a></li></ul>';
	return $html;
}

/**
 * Generate a failure message
 * @param $failType - Optional param to trigger some additional information for user.
 */
function generateSubscriptionFailure($failType=""){
	$url = curPageURL(true);
	$slink = anchor(resetUrl(curPageURL(true), "cmplt.php", "s"));

	$html = "<h1>Sorry, we could not add you to the Load Restrictions notification subscription.</h1><ul>";
	if ($failType === "mail"){
		$html .= "<li>We were unable to send out the confirmation email message.</li>";
	} else if ($failType === "blank"){
		$html .= "<br/><li>There was a network problem.</li>";
	}
	$html .= "<br/><li>Please try again later using this link: ".$slink."</li></ul>";
	return $html;
}

/**
 * Generate our custom screen output instead of phplist screen.
 */
function generatePreferencesConfirmation(){
	$url = curPageURL(true, true);
	$link = anchor($url);
	$pLink = anchor(resetUrl($url, "cmplt.php", "p"));
	$uLink = anchor(resetUrl($url, "cmplt.php", "u"));

	$html = "<h1>Your Load Restrictions subscription preferences have been updated.</h1>";
	$html .= "<ul><li>To make further changes to your preferences, go to: " . $pLink . "</li>";
	$html .= "<p/><li>To unsubscribe, go to: " . $uLink . "</li></ul>";
	return $html;
}

/**
 * Unexpected error during preferences update.
 */
function generatePreferencesFailure(){
	$url = curPageURL(true, true);
	$link = anchor(resetUrl($url, "cmplt.php", "p"));

	$html = "<h1>Could not update your Load Restrictions notification subscription preferences.</h1>";
	$html .= "<ul><li>Please try to update them again using the following link: ".$link."</li></ul>";
	return $html;
}

/*
 * Successfully unusubscribed
 */
function generateUnsubscribeConfirmation(){
	$url = curPageURL(true, false);
	$link = anchor(resetUrl($url, "cmplt.php", ""));

	$html = "<h1>You have successfully unsubscribed from Load Restrictions email notifications.</h1>";
	$html .= "<ul><li>You can re-subscribe to the service at any time by going to: ". $link . "</li></ul>";
	return $html;
}

/**2
 * Encountered a failure during the unsubscription process.
 */
function generateUnsubscribeFailure(){
	$url = curPageURL(true);
	$link = anchor(resetUrl($url, "cmplt.php", "u"));

	$html = "<h1>Could not remove you from Load Restrictions notification subscriptions.</h1>";
	$html .= "<ul><li>Could not find your email address in our active subscription list.</li>";
	$html .= "<br/><li>Please try to unsubscribe again using the following link: ".$link."</li></ul";
	return $html;
}


function generateEmailInUseFailure(){
	$url = curPageURL(true, true);
	$link = anchor(resetUrl($url, "cmplt.php", ""));	// Skip the switch in url.

	$html = "<h1>Email address is already subscribed.</h1>";
	$html .= "<ul><li>The email address you entered is already registered to receive Load Restriction notifications. " .
			"To update currently defined preferences for this email address, please use the link provided in the Load Restriction notification emails.</li>";
	$html .= "<br/><li>To either use a new email address to register as a new subscriber or to obtain update preferences/unsubscribe links for the entered email address, go here: ".$link."</li></ul>";
	return $html;
}

/**
 * Successfully sent out email with links.
 */
function generateResendSuccess(){
	$html = "<h1>An email has been sent to the specified address with links.</h1>";
	return $html;
}


/**
 * Problem resending an email with the preferences and unsubscription links.
 */
function generateResendFailure(){
	$url = curPageURL(true);
	$link = anchor(resetUrl($url, "cmplt.php", "s"));
	$html = "<h1>Email address provided is not currently subscribed.</h1>";
	$html .= "<ul><li>Unable to provide links to update or unsubscribe from email notifications because email provided is not currently subscribed to email notifications.</li>";
	$html .= "<li>You can re-subscribe to the service by going to: ${link}</li></ul>";
	return $html;
}

/**
 * Email address for resending email links was not found.
 */
function generateEmailNotFoundFailure(){
	$html = "<h1>Email address was not found.</h1>";
	$html .= "<ul><li>The email address you entered has not yet been registered to receive Load Restrictions notifications. " .
			"Use the 'Back' button on your browser to try another address.</li></ul>";
	return $html;
}

/**
 * Email address has already been confirmed.
 */
function generateUserAlreadyConfirmed(){
	$url = curPageURL(true, true);
	$link = anchor(resetUrl($url, "cmplt.php", ""));	// Skip the switch in url.

	$html = "<h1>Email address is already confirmed.</h1>";
	$html .= "<ul><li>This email address has already been confirmed for receiving Load Restriction notifications. " .
			"To update currently defined preferences for this email address, please use the link provided in the Load Restriction notification emails.</li>";
	$html .= "<br/><li>To either use a new email address to register as a new subscriber or to obtain update preferences/unsubscribe links for the entered email address, go here: ".$link."</li></ul>";
	return $html;
}

/**
 * Very unusual error where we have neither an email address
 * or an UID value to pass on for processing.
 * Will this ever happen???  Probably not!
 */
function errorNoUidOrEmail(){
	$html = "<h1>Could not process your Load Restrictions notification subscription request!</h1>";
	$html .= "<ul><li>Sorry, but we encountered a very unexpected error.  Please try again.</li></ul>";
	return $html;
}
?>