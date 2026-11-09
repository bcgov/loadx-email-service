<?php
/*
 * Created on March 1, 2012
 *
 * Resend email with links to update or unsubscribe.
 *
 */
	require_once('utility.php');
	$SCREEN_MODE = "resend"; 	// Define globally.
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

    <title>Resend Load Limits Event Subcription EMail</title>
</head>
<body>
	<?php include("header.php"); ?>
	<div id="subscribe">
		<form id="subscribeForm" method="post">
			<input id="process_type" name="process_type" type="hidden" value="<?php echo $SCREEN_MODE; ?>" />
			<?php
				include("input.php");
			?>
		</form>
	</div>
</body>
</html>