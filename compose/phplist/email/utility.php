<?php
/*
 * Created on Nov 28, 2011
 */

require_once('config.php');

/**
 * Resets the contents of the url with new parameter.
 * @param string $url Original url with possible parameters, etc.
 * @param string $phpFile Name of php file we are search/replacing in $url
 * @param string $pParam The 'p' switch parameter to add or blank to
 * 				         skip adding 'p' switch entirely.
 */
function resetUrl($url, $phpFile, $pParam){
	$newUrl = "";
	$switch = "";
	$extra = "";
	$parts = explode("?", $url);
	$pos = strpos($parts[0], $phpFile);

	if ($pos != false){
		$newUrl = substr($parts[0], 0, $pos);
	}
	if (isset($pParam) && strlen($pParam) > 0){
		$switch = "?p=".$pParam;
	}
	if(count($parts) > 1){
		$first = true;
		foreach ($parts as $part){
			if ($first){
				$first = false;
				continue;
			}
			if ($switch == ""){
				$extra = "?";
			} else {
				$extra .= "&";
			}
			$extra .= $part;
		}
	}
	return $newUrl.$switch.$extra;
}


/**
 * Creates anchor tag with provided link.
 */
function anchor($link){
	return "<a href=\"".$link."\">".$link."</a>";
}


/**
 * Get current URL
 * @param $stripParams If true, removes ? and anything after that.
 * @param $keepUid If true, maintains uid parameter (if found).  Only applicable if $stripParams is true.
 */
function curPageURL($stripParams=false, $keepUid=false) {
	$pageURL = "";
	$curURL = rtrim(PUBLIC_BASE_URL, '/') . $_SERVER["REQUEST_URI"];

	// Strip any parameters
	if ($stripParams){
		$parts = explode("?", $curURL);
		$pageURL = $parts[0];

		if ($keepUid){
			$params = explode("?", $curURL);
			if (count($params) > 1){	// [0] is address and [1] are any parameters
				$params = explode("&", $params[1]);
				foreach($params as $param){	// Look at each parameter
					$pos = strpos($param, "uid=");
					if ($pos !== false && $pos === 0){
						$pageURL .= "?".$param;
					} else {
						$pos = strpos($param, "s=");
						if ($pos !== false && $pos === 0){
							$pageURL .= "?".$param;
						}
					}
				}
			}
		}
	} else {
		$pageURL = $curURL;
	}
	return $pageURL;
}


/**
 * Removes the 'p=' parameter
 */
function cleanParams($params){
	$outParam = "";
	$paramArray = explode("&", $params);
	foreach($paramArray as $param){
		$pos = strpos($param, "p=");
		if ($pos !== false && $pos === 0){
			// skip this one
		} else {
			if (strlen($outParam) == 0){
				$outParam = "?";
			} else {
				$outParam .= "&";
			}
			$outParam .= $param;
		}
	}
	return $outParam;
}


/**
 * Determine basic test for mobile user agent in header.
 * Note: This works, but is very unstable since it does not recognize new mobile devices!!!
 * @return boolean true if mobile match, else false.
 */
function isMobileDevice(){
	global $mobile_device_mode_override;
	if (isset($mobile_device_mode_override)){
		return $mobile_device_mode_override;
	}

	// A list of mobile devices
	$useragents = array (
						'iPad',
						'iPhone',
						'Playbook',
						'Android',
						'Blazer',
						'Palm',
						'Handspring',
						'Nokia',
						'Kyocera',
						'Samsung' ,
						'Motorola' ,
						'Smartphone',
						'Windows CE' ,
						'Blackberry' ,
						'WAP' ,
						'SonyEricsson',
						'PlayStation Portable',
						'LG',
						'MMP',
						'OPWV',
						'Symbian',
						'EPOC'
	);
//	// Debugging mode (set in config.php)
//	if ($GLOBALS['mobile_device_mode_override']){
//		return true;
//	}
	foreach ($useragents as $key => $value ) {
		if( substr_count(strtolower($_SERVER['HTTP_USER_AGENT']), strtolower($value)) > 0 ) {
			return true;
 		}
	}
	return false;
}

/**
 * Logging function
 * @param $msg
 * @param $clear defaults to false.  Set true to clear contents before writing.
 */
function logit($msg, $clear=false){
	if ($clear){
		file_put_contents(__DIR__.'/LoadRestriction.log', "", LOCK_EX);
	}
	file_put_contents(__DIR__.'/LoadRestriction.log', $msg.PHP_EOL, FILE_APPEND|LOCK_EX);
}

?>
