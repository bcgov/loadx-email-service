<?php

/* Used to communicate between subscribe/update and cnfrm.php */
$PROCESS_CODES = array(
				 "SUCCESS_SUBSCRIBE" => 1,
				 "FAILURE_SUBSCRIBE" => -1,
				 "FAILURE_ALREADYCONFIRMED" => -11,
				 "SUCCESS_UPDATE" => 2,
				 "FAILURE_UPDATE" => -2,
				 "SUCCESS_UNSUBSCRIBE" => 3,
				 "FAILURE_UNSUBSCRIBE" => -3,
				 "FAILURE_EMAILINUSE" => -4,
				 "SUCCESS_RESEND" => 5,
				 "FAILURE_RESEND" => -5,
				 "FAILURE_EMAILNOTFOUND" => -55
				);


// Subscription page id in php_list
$SPAGE_ID = 1;

// When set to 'true', this parameter will block the display
// of the subscription input controls.
// This parameter is checked in sbscrb.php which will then
// load the message content from static/nosbscrb.html
$NO_NEW_SUBSCRIBERS = false;

// Location of data files.
#$loadFilePath = "https://tst-www.th.gov.bc.ca/roadreports/";
#$loadFilePath = "https://tst-loadx.th.gov.bc.ca/feeds/loadrestrictions/";
// Location of data files configured via environment variable
$loadFilePath = getenv('LOAD_FILE_PATH') ?: "http://localhost:8080/feeds/"; # the localhost one simply gets the files from the local container, which is useful for testing

/* Public base URL of this app as users see it (behind the OpenShift route).
 * Override per environment with the PUBLIC_BASE_URL environment variable. */
define('PUBLIC_BASE_URL', getenv('PUBLIC_BASE_URL') ?: '');

// Balance server information// Balance server information using environment variables
$phplist_instance_info = array (
    0 => array(
        "phplist_url"   => "http://localhost:8080/lists/",
        "forwaring_url" => "",
        "host"          => getenv('PHPLIST_DATABASE_HOST') ?: "",
        "user"          => getenv('PHPLIST_DATABASE_USER') ?: "",
        "password"      => getenv('PHPLIST_DATABASE_PASSWORD') ?: "",
        "database"      => getenv('PHPLIST_DATABASE_NAME') ?: "",
        "capacity"      => "1000"
    )
    //@ADDITIONAL_PHPLIST_INSTANCES@
);

?>
