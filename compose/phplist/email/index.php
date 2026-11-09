<?php
/*
 * Created on Dec 1, 2011
 * 
 * Main entry point to subscription pages
 * 
 */
require_once('utility.php');
require_once('config.php');

if (isset($_GET["p"])) {
	$params = "";
	
	// Get a copy of any command line arguments
	$argArray = explode("?", curPageURL(false)); 
	
	if (count($argArray) > 1){	
		// Strips off 'p' argument in parameters.
		$params = cleanParams($argArray[1]);
	}
	
    switch ($_GET["p"]) {
      	case "s":
    		header("Location: sbscrb.php");  		// subscribe
        	break;
      	case "p":
      		header("Location: prefs.php".$params);	// update preferences
        	break;
      	case "c":
      		header("Location: cnfrm.php".$params);	// confirm subscription
	        break;
    	case "u":
    		header("Location: unsbscrb.php".$params);	// unsubscribe
        	break;
      	default:
    		header("Location: choose.php");
        	break;
    }
} else {
    header("Location: choose.php");
}

?>
