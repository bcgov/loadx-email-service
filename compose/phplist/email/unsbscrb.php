<?php
	require_once('utility.php');

	$SCREEN_MODE = "unsubscribe";		// Define globally.

	// Check for url parameters
	$urlError = false;
	if (isset($_GET['uid']) && strlen($_GET['uid']) > 0){
		;
	} else {
		$urlError = true;
	}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta name="viewport" content="user-scalable=no, width=device-width" />
    <link href="./css/jquery.stepy.css" rel="stylesheet" type="text/css" />
<?php
	if (isMobileDevice()){
		print('<link rel="stylesheet" type="text/css" href="./css/mobile.css" />');
	} else {
		print('<link rel="stylesheet" type="text/css" href="./css/desktop.css" />');
	}
?>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title>Unsubscribe to Load Limits Notifications by Email</title>
</head>
<body>
	<?php include("header.php");?>
	<div id="unsubscribe">
		<form id="unsubscribeForm" method="post">
			<input id="process_type" name="process_type" type="hidden" value="<?php echo $SCREEN_MODE; ?>" />
<?php
			if ($urlError){
				echo "<h1>Could not unsubscribe you.</h1><br/>";
				echo "<p>The url is incomplete.</p>";
				echo "<p>Please copy the entire link from your email and paste it into the browser address bar and try again.</p>";
			} else {
				include("input.php");
			}
?>
		</form>
	</div>
</body>
</html>