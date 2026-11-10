<?php
require_once("config.php");

// Return false on errors instead of throwing (pre-8.1 behaviour).
mysqli_report(MYSQLI_REPORT_OFF);

class DB {
	var $dbConnError = false;
	var $conn = false;

	function __construct($params = null){
		if (isset($params)){
			$this->init($params);
		}
	}

	function init($params){
		global $db_params;

		if ($this->conn != false){
			return;
		}

		$p = ($params != null) ? $params : $db_params;
		$host = $p["host"];
		$user = $p["user"];
		$password = $p["password"];
		$database = $p["database"];

		if (strcasecmp($database, "phplist_@ENV@") === 0){
			$database = "phplistdb";
		}

		try {
			$tconn = @mysqli_connect($host, $user, $password, $database);
		} catch (Exception $e){
			$this->dbConnError = true;
			return;
		}

		if ($tconn === false || $tconn === null){
			$this->dbConnError = true;
			return;
		}

		mysqli_set_charset($tconn, "utf8mb4");
		$this->conn = $tconn;
	}

	function escape($value){
		if (!$this->conn){
			return addslashes($value);
		}
		return mysqli_real_escape_string($this->conn, (string)$value);
	}

	function closeDbConnection(){
		if ($this->conn){
			mysqli_close($this->conn);
			$this->conn = false;
		}
	}

	function isDbConnectionError(){
		return $this->dbConnError;
	}

	function queryData($sql){
		$data = array();

		if (!$this->conn){
			return false;
		}

		if (isset($sql) && strlen($sql) > 0){
			$result = mysqli_query($this->conn, $sql);
			if (is_bool($result)){
				if ($result === false){
					return false;
				}
			} else {
				while ($row = mysqli_fetch_assoc($result)){
					$data[] = $row;
				}
				mysqli_free_result($result);
			}
			return $data;
		}
		return false;
	}

	function queryInt($sql){
		$retVal = $this->queryData($sql);
		if ($retVal != false){
			foreach ($retVal[0] as $value){
				return intVal($value);
			}
		}
		return -9999;
	}

	function getUserPrefs($uid){
		if (!$this->conn){
			return false;
		}

		if (isset($uid) && strlen($uid) > 0){
			$sql = sprintf("SELECT u.id, u.email, u.htmlemail, u.rssfrequency, a.attributeid, a.value, " .
						"u.blacklisted, u.confirmed " .
						"from phplist_user_user u " .
						"left join phplist_user_user_attribute a " .
						"on u.id = a.userid where u.uniqid = '%s' ",
						$this->escape($uid));
			$data = $this->queryData($sql);

			if ($data != false){
				if ($data[0]['blacklisted'] == '1'){
					return 0;	// already unsubscribed
				}

				$pref["email"] = $data[0]["email"];
				$pref["htmlemail"] = $data[0]["htmlemail"];
				$pref["frequency"] = $data[0]["rssfrequency"];
				$pref["confirmed"] = $data[0]["confirmed"];

				foreach ($data as $row){
					$wasBlankOrNull = ($row["value"] == '' || $row["value"] == 'NULL');
					$pref["servicearea"] = ($wasBlankOrNull ? "0" : $row["value"]);
				}
				return $pref;
			}
		}
		return false;
	}

	function getUidByEmail($email){
		if (!$this->conn){
			return false;
		}
		$data = $this->queryData(
			sprintf("select uniqid from phplist_user_user where email = '%s' and blacklisted = 0",
				$this->escape($email)));
		if ($data !== false){
			foreach ($data as $out){
				return $out['uniqid'];
			}
		}
		return false;
	}

	function getEmailById($uid){
		if (!$this->conn){
			return false;
		}
		$data = $this->queryData(
			sprintf("select email from phplist_user_user where uniqid = '%s' and blacklisted = 0",
				$this->escape($uid)));
		if ($data != false){
			foreach ($data as $out){
				return $out['email'];
			}
		}
		return false;
	}

	function getRouteList(){
		$routeData = array();
		if (!$this->conn){
			return $routeData;
		}
		$data = $this->queryData("select name, id from phplist_listattr_dbcroute order by listorder asc");
		if ($data != false){
			foreach ($data as $row){
				$routeData[$row["id"]] = $row["name"];
			}
		}
		return $routeData;
	}
}
?>