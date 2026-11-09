<?php
require_once 'config.php';
require_once 'class.DB.php';
require_once 'utility.php';

/** Single-instance helper (load balancing removed). Method names kept for callers. */
class BalanceDB {
	private $info;
	private $db = null;

	function __construct(){
		global $phplist_instance_info;
		$this->info = $phplist_instance_info[0];
	}

	private function db(){
		if ($this->db === null){
			$d = new DB($this->info);
			$this->db = $d->isDbConnectionError() ? false : $d;
		}
		return $this->db ?: null;
	}

	function getBalancedServerKey(){ return 0; }
	function getBalancedServerSortedArray(){ return array(0 => $this->info["capacity"]); }
	function getServerKeyByEmail($email){ return 0; }

	function addToMaster($email, $srvrKey){}
	function deleteFromMasterByUid($uniqid, $srvrKey){}

	function getPhplistUrl($srvrKey = 0){ return $this->info['phplist_url']; }

	function isEmailAddressUsed($email){
		$db = $this->db();
		if (!$db) return false;
		$e = $db->escape($email);
		$rows = $db->queryData(sprintf(
			'select email from phplist_user_user where (email = "%s" or email = "%s?") and blacklisted = 0', $e, $e));
		return !empty($rows);
	}

	function findEmailByUID($uniqid, $srvrKey = 0){
		$db = $this->db();
		return $db ? $db->getEmailById($uniqid) : false;
	}

	function findUIDByEmail($email, $srvrKey = 0){
		$db = $this->db();
		return $db ? $db->getUidByEmail($email) : false;
	}

	function getUserPrefs($uid, $srvrKey = 0){
		$db = $this->db();
		return $db ? $db->getUserPrefs($uid) : false;
	}
}

$GLOBALS['balanceDB'] = new BalanceDB();
?>