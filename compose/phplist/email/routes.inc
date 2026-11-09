<?php
/*
 * Created on Dec 8, 2011
 *
 * Array of route names and it's number
 * 
 */
$routeArray = array();
try {
   	$db = new DB();
   	if ($db->isDbConnectionError() != true){
   		$routeArray = $db->getRouteList();
   		foreach($routeArray as $id => $name){
   			$routeArray[$id] = $name;
   		}
   	}
	$db->closeDbConnection();
} catch (Exception $e){
  	// Error
}
?>	
