<?php
/*
 * Created on Nov 17, 2011
 *
 * Update subscription preferences.
 *
 */
	require_once('utility.php');
	require_once('class.BalanceDB.php');
	$SCREEN_MODE = "preferences"; 	// Define globally.

	$dbError = false;
	$urlError = false;
	$unsubscribed = false;

	$uid = "";
	if (isset($_GET['uid']) && strlen($_GET['uid']) > 0){
		$uid = $_GET['uid'];
	} else {
		// ERROR!!!  No uid provided
		$urlError = true;
	}

	$srvrKey = "0";

	$savedPrefs = false;
	if (!$urlError){
		global $balanceDB;
		// Retrieve saved values used to populate the fields with.
		$savedPrefs = $balanceDB->getUserPrefs($uid, $srvrKey);
		if ($savedPrefs === false){
			$dbError = true;		// Found in master table but not in database?
		} else if ($savedPrefs === 0){
			$unsubscribed = true;	// No longer subscribed.
		}
	}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <link href="./css/jquery.stepy.css" rel="stylesheet" type="text/css" />
<?php
if (isMobileDevice()){
	print('<link rel="stylesheet" type="text/css" href="./css/mobile.css" />');
} else {
	print('<link rel="stylesheet" type="text/css" href="./css/desktop.css" />');
}
?>
    <title>Update Load Limits EMail Notifications</title>
</head>
<body>
	<?php include("header.php"); ?>
	<div id="subscribe">
		<form id="subscribeForm" method="post">
			<input id="process_type" name="process_type" type="hidden" value="<?php echo $SCREEN_MODE; ?>" />
			<input id="oremail" name="oremail" type="hidden" value="" />

			<?php
				// If no uid, redirect to subscribe
				if (isset($_GET['uid']) && $_GET['uid'] != ""){
					// Check for data from db or display error message.
					if ($unsubscribed === true){
						echo "<h1>Could not load user information.</h1><br/>";
						$url = anchor(resetUrl(curPageURL(true), "prefs.php", "s"));
						echo "<span>This user has unsubscribed.<br/>To re-subscribe, click here: ".$url."</span>";
					} else if ($savedPrefs != false){
?>
						<input id="prefs_email" name="prefs_email" type="hidden" value="<?php echo $savedPrefs['email']; ?>" />
						<input id="prefs_frequency" name="prefs_frequency" type="hidden" value="<?php echo $savedPrefs['frequency']; ?>" />
						<input id="prefs_areas" name="prefs_areas" type="hidden" value="<?php echo $savedPrefs['servicearea']; ?>" />
<?php
						include("input.php");
					} else {
						if ($dbError){
							echo "<h1>Could not load user information.</h1><br/>";
							echo "<span>We cannot load this users information at this time.<br/>Please try again in a few minutes.</span>";
						} else {
							echo "<h1>Could not locate user information.</h1><br/>";
							echo "<span>Either the user could not be found in our database or the url is incomplete.<br/>";
							echo "Please copy the entire link from your email and paste it into the browser address bar and try again.</span>";
						}
					}
				} else {
					header("Location: sbscrb.php");
				}
			?>
		</form>
	</div>
</body>
</html>