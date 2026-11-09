<?php
	require('config.php');
	require('utility.php');

	$SCREEN_MODE = "choose";		// Define globally.
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta name="viewport" content="user-scalable=no, width=device-width" />
    <script src="js/jquery.1.4.2.min.js" type="text/javascript"></script>
    <script src="js/subscribe.js" type="text/javascript"></script>
<?php
if (isMobileDevice()){
	print('<link rel="stylesheet" type="text/css" href="./css/mobile.css" />');
} else {
	print('<link rel="stylesheet" type="text/css" href="./css/desktop.css" />');
}
?>

	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title>Load Restrictions Notifications by Email</title>
</head>
<body>
	<?php include("header.php");?>
	<div id="chooseDiv">
		<form id="chooseForm" method="post">
			<input id="process_type" name="process_type" type="hidden" value="<?php echo $SCREEN_MODE; ?>" />
			<a id="new_subscriber" href="#" class="choice">Click here to subscribe to Email Notifications</a>
			<a id="email_links" href="#" class="choice">Click here to send me links to update or unsubscribe from Email Notifications</a>
		</form>
	</div>
</body>
</html>