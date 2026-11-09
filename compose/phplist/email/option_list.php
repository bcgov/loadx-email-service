<?php
	// Reused multiple times, so moved here for simple include statements.
	// List of all routes in dropdowns, not including 'All'.
	require 'routes.inc';
	{
		global $routeArray;
		foreach ($routeArray as $key => $value){
			print ('<option value="'.$key.'">'.$value.'</option>');
		}
	}
?>
