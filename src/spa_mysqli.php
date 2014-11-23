<?php 
/**
* 
*/
class SpaBaseMysqliProvider extends SpaBaseProvider
{
	private static $counters = array(
		"QUERY_NUMS" => 0,
	);
	
	private static $caches = array(
		"LAST_SQL" => null,
		"QUERIES" => array(),
		"INSTANCES"=> array(),
	);

	private $link = null;

	private function __construct() {
		$this->link = self::createConnection();
	}

	public function __destruct() {
		if($this->isReady()) {
			mysqli_close($this->link);
			$this->link = null;
		}
	}

	public static function getInstance() {
		$database = self::config("database");
		$hash = hash('md4', "{$database}");
		$instances = self::$caches["INSTANCES"];
		
		if(in_array($hash, $instances)) {
			$instance = $instances[$hash];
			if($instance->isReady()) {
				return $instance;
			}
		}
		
		$instance = new self();
		self::$caches["INSTANCES"][$hash] = $instance;
		return $instance;
	}

	private static function createConnection() {
		$hosts = self::config("hosts");
		$database = self::config("database");
		$password = self::config("password");
		$timeout = self::config("timeout");
		$charset = self::config("charset");

		if (is_array($hosts)) {
			if (sizeof($hosts)<1) {
				throw new Exception("Db config error. There is no db connection info defined", 1);
			}
		} else {
			throw new Exception("Db config error. Wrong host format", 1);
		}

		$host = shuffle($hosts);
		
		list($username, $ip, $port) = extraDbInfo($host);

		$mysqli = mysqli_init();
		
		if(!mysqli_options($mysqli, MYSQLI_OPT_CONNECT_TIMEOUT, $timeout)) {
			throw new Exception("Db Config error. Wrong Database option [timeout] type: '{$timeout}'", 1);
		}

		if(@mysqli_real_connect($mysqli, $ip, $username, $password, $database, $port)) {
			if(@mysqli_set_charset($mysqli, $charset)) {
				return $mysqli;
			}

			throw new Exception("Db connect error. Error When Set Db Charset", 1);
		}

		throw new Exception("Db connect error. Cannot connect to host: [{$username}@{$ip}@{$port}]", 1);
	}


	public static function doQuery($link, $sql) {
		$start = microtime(true);
		$ret = mysqli_query($link, $sql);
		$end = microtime(true);

		self::$caches["LAST_SQL"] = $sql;
		self::$caches["QUERIES"][] = array("{$sql}"=>$end-$start);
		self::$counters["QUERY_NUMS"]++;
		
		return $ret;
	}

	public static function useDb($link, $db) {
		if(!empty($link) && $link) {
			return mysqli_select_db($link, $db);
		}

		return false;
	}

	public static function lastQuery() {
		return self::$caches["LAST_SQL"];
	}

	public static function queryNums() {
		return self::$counters["QUERY_NUMS"];
	}

	public static function queries() {
		return self::$caches["QUERIES"];
	}

	public function isReady() {
		return !empty($this->link) && $this->link;
	}

	public function isError() {
		return $this->errno() > 0;
	}

	public function query($sql) {
		if(!$this->isReady()) {
			throw new Exception("Db is not ready", 1);
		}

		return self::doQuery($this->link, $sql);
	}

	public function count($sql) {
		$res = $this->query($sql);

		if($this->isError()) {
			return 0;
		}

		$count = mysqli_fetch_array($res);
		mysqli_free_result($res);

		return $count[0][0];
	}

	public function first($sql) {
		$res = $this->query($sql);
		
		if($this->isError()) {
			return false;
		}

		$ret = mysqli_fetch_assoc($res);
		mysqli_free_result($res);

		return $ret;
	}

	public function all($sql) {
		$ret = array();
		$res = $this->query($sql);

		if($this->isError()) {
			return false;
		}

		while ($row = mysqli_fetch_assoc($res)) {
			$ret[] = $row;
		}

		mysqli_free_result($res);

		return $ret;
	}

	public function field($sql) {
		$ret = false;
		$res = $this->query($sql);

		if($this->isError()) {
			return false;
		}

		$ret = mysqli_fetch_row($res)[0];
		mysqli_free_result($res);

		return $ret[0];
	}

	public function affected() {
		if($this->isReady()) {
			return mysqli_affected_rows($this->link);
		}

		return false;
	}

	public function transaction($sqls) {
		if(!$this->isReady())
		{
			return false;
		}

		mysqli_autocommit($this->link, false);
		foreach($sqls as $sql)
		{
			$ret =  $this->query($this->link, $sql);
			if(!$ret)
			{
				mysqli_rollback($this->link);
				mysqli_autocommit($this->link, true);
				return false;
			}
		}
		mysqli_commit($this->link);
		mysqli_autocommit($this->link, true);
		return true;
	}

	public function lastInsertId() {
		return mysqli_insert_id($this->link);
	}

	public function realEscapeString($str) {
		if($this->isReady()) {
			return mysqli_real_escape_string($this->link, $str);
		}

		return false;
	}

	public function error() {
		if($this->isReady()) {
			return mysqli_error($this->link);
		}

		return "Db is not ready";
	}

	public function errorNo() {
		return mysqli_errno($this->link);
	}
}
?>