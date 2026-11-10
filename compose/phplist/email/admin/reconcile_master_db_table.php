<?php
/*
 * This process is command line executed.
 * 
 * The purpose of this file is to populate the master lookup table
 * used to balance the subscription load among instances of phplist.
 * 
 * This process will re-generate this master table from the various
 * instances of phplist by reading the active subscribers and output
 * a json format file.
 */
require_once __DIR__.'/../config.php';


// get config info on phplist instances
global $phplist_instance_info;
global $master_table_info;

$conn = null;
$insertValues = "";

// Master db table information
$master_conn = null;
$master_table = $master_table_info["table"];

print "This process reconciles the Master Table with users from all configured".PHP_EOL.
	  "server instances of php_list".PHP_EOL.PHP_EOL;

print "Starting process...".PHP_EOL.PHP_EOL;


// Loop through connections
foreach($phplist_instance_info as $srvrkey=>$db_params){
	print "Processing server ".$srvrkey."...".PHP_EOL;
	
	if ($conn != null){
		// Close connection if open.
		mysql_close($conn);
	}	
	
	try {
		// Connect to database
		$conn = mysql_connect($db_params['host'], 
						      $db_params['user'], 
						      $db_params['password']);
		if (mysql_errno()) {
			print PHP_EOL."MySQL error ".mysql_errno().": ".mysql_error().
				PHP_EOL."Connecting to server ".$srvrkey.PHP_EOL;
			die;
		}		
	} catch (Exception $e){
		print "Exception trying to connect with database: " .
			$db_params["host"]."  db: ".$db_params['database']
			."  error: " . mysql_error().PHP_EOL;
		die;
	}
	
	try {
		// Select database.
		if (!mysql_select_db($db_params['database'], $conn)) {
			print "Could not select database [".$db_params['database']."]! ".mysql_error().PHP_EOL;
			die;						
		}
	} catch (Exception $e){
		print "Could not select database: ".$db_params['database']."  error: ".$e->errorMessage().PHP_EOL;
		die;
	}

	
	/*************************
	 * Query users table
	 *************************/ 
	$sql = 'select email from phplist_user_user where email not like "%?" and blacklisted="0" and disabled="0"';
	$result = mysql_query($sql, $conn);
	if (mysql_errno($conn)) {
		print "MySQL error ".mysql_errno($conn).": ".mysql_error($conn).
				PHP_EOL."When executing:".PHP_EOL.$sql.PHP_EOL;
		die;
	} 		
	if($result){
		$cnt = 0;
		while ($row = mysql_fetch_assoc($result)) {
			if (strlen($insertValues) > 0){
				$insertValues .= ",";
			}
			$cnt++;		
			$insertValues .= "('".strtolower($row['email'])."',".$srvrkey.")";
		}		
		
		print "Number of subscribers found: ".$cnt.PHP_EOL.PHP_EOL;
	} else {
		print "No data in this server to process".PHP_EOL.PHP_EOL;
	}
}
if ($conn != null){
	mysql_close($conn);
}
print "All servers read.".PHP_EOL.PHP_EOL;

	
/**
 * Connect to master table in database.
 */
try {
	// Connect to database
	$master_params = $master_table_info;
	print "Connecting to master table... ";
	$master_conn = mysql_connect($master_params['host'],
			$master_params['user'],
			$master_params['password']);
	if (mysql_errno()) {
		print PHP_EOL."MySQL error ".mysql_errno().": ".mysql_error().
			PHP_EOL."When starting transaction".PHP_EOL;
		die;
	}		
	print "Done".PHP_EOL.PHP_EOL;
} catch (Exception $e){
	print PHP_EOL."Exception trying to connect with database master table: " .
			$master_params["host"]."  db: ".$master_params['database']
			."  error: " . mysql_error().PHP_EOL;
	die;
}

try {
	// Select database.
	if (!mysql_select_db($master_params['database'], $master_conn)) {
		print "Could not select database [".$master_params['database']."]  ".mysql_error($master_conn).PHP_EOL;
		mysql_close($master_conn);
		die;
	}
} catch (Exception $e){
	print "Could not select master table database: ".$master_params['database'].
	"  error: ".$e->errorMessage().PHP_EOL;
	mysql_close($master_conn);
	die;
}

try {
	print "Lock master table... ";
	// Will be unlocked when connection closes.
	mysql_query("lock table ".$master_table." write", $master_conn);
	if (mysql_errno($master_conn)) {
		print PHP_EOL."MySQL error ".mysql_errno($master_conn).": ".mysql_error($master_conn).
			PHP_EOL."When locking master table".PHP_EOL;
		die;
	}		
	print "Done".PHP_EOL.PHP_EOL;
} catch (Exception $e){
	print PHP_EOL."Could not lock the master table: ".$master_table.
			"  error: ".$e->errorMessage().PHP_EOL;
	mysql_close($master_conn);
	die;
}


try {
	print "Start transaction... ";
	// Rollback if connection closed before 'commit'.
	mysql_query("start transaction", $master_conn);
	if (mysql_errno($master_conn)) {
		print PHP_EOL."MySQL error ".mysql_errno($master_conn).": ".mysql_error($master_conn).
			PHP_EOL."When starting trasnaction".PHP_EOL;
		die;
	}		
	print "Done".PHP_EOL.PHP_EOL;
} catch (Exception $e){
	print PHP_EOL."Could not start a transaction.  error: ".$e->errorMessage().PHP_EOL;
	mysql_close($master_conn);
	die;
}

try {
	// 	Delete contents of table.
	print "Empty master table data... ";
	mysql_query("delete from ".$master_table, $master_conn);
	if (mysql_errno($master_conn)) {
		print PHP_EOL."MySQL error ".mysql_errno($master_conn).": ".mysql_error($master_conn).
			PHP_EOL."When deleting master table contents".PHP_EOL;
		die;
	}		
		print "Done".PHP_EOL.PHP_EOL;
} catch (Exception $e){
	print PHP_EOL."Could not delete contents of master table.  error: ".$e->errorMessage().PHP_EOL;
	mysql_close($master_conn);
	die;
}


// Write out to the database.
if (strlen($insertValues) > 0){
	print "Write data to master table... ";
	$insert_sql = sprintf("INSERT INTO %s (email, srvrKey) VALUES %s",
			$master_table, $insertValues);
	mysql_query($insert_sql, $master_conn);
	if (mysql_errno($master_conn)) {
		print PHP_EOL."MySQL error ".mysql_errno($master_conn).": ".mysql_error($master_conn).
			PHP_EOL."When executing:".PHP_EOL.$insert_sql.PHP_EOL;
		die;
	}		
	print "Done".PHP_EOL.PHP_EOL;
} else {
	print "Nothing to insert into master table!".PHP_EOL.PHP_EOL;
}	


try {
	print "Commit transaction... ";
	mysql_query("commit", $master_conn);
	if (mysql_errno($master_conn)) {
		print PHP_EOL."MySQL error ".mysql_errno($master_conn).": ".mysql_error($master_conn).
			PHP_EOL."When committing transaction".PHP_EOL;
		die;
	}		
	print "Done".PHP_EOL.PHP_EOL;
} catch (Exception $e){
	print PHP_EOL."Could not commit transaction.  error: ".$e->errorMessage().PHP_EOL;
	mysql_close($master_conn);
	die;
}



// Close connections
if ($master_conn != null){
	mysql_close($master_conn);
}

print "Processing finished".PHP_EOL;
?>