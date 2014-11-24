<?php 
/**
* 
*/
final class AgMysqli extends AgBaseProvider {
	protected $instances = array();
	static protected $defaults = array();
	static protected $cipher = null;
	static protected $shotcut = array();
    protected $scope=null;
	protected $dependencies = array();
	protected $magics = array("cache", "queries");
	protected $minError;

	private function __construct($cipher, $config=array(), $defaults=array()) {
		$this->scope = array("cipher"=>$cipher, "minError"=>new MinError);
		$this->config($this->scope["defaults"]);
		$this->link = $this->__createConnection($config);
	}

	public function __destruct() {

	}

	public function __toString() {

	}

	protected function getInstance() {
		if (is_null(self::$cipher)) {
			self::$cipher = hash("md4", mt_rand(0, time()));
		}

		$config = AgConfig::get("Ag:mysql");
		
		if(!isset($config["database"])) {
			return trigger_error("cant instaniate db module", E_USER_WARNING);
		}

		$database = $config["database"];

		if (!isset(self::$instances[$database])) {
			self::$instances[$database] = new self(self::$cipher, $config, self::$defaults);
		}

		return self::$instance[$database];
	}

	final private function __createConnection($config) {
		$hosts = $config["hosts"];
		$database = $config["database"];
		$password = $config["password"];
		$timeout = $config["timeout"];
		$charset = $config["charset"];

		if (is_array($hosts)) {
			if (sizeof($hosts)<1) {
				return trigger_error("Db config error. There is no db connection info defined", E_USER_WARNING);
			}
		} else {
			return trigger_error("Db config error. Wrong host format", E_USER_WARNING);
		}

		$host = shuffle($hosts);
		
		list($username, $ip, $port) = $this->__extraDbInfo($host);

		$mysqli = mysqli_init();
		
		if(!mysqli_options($mysqli, MYSQLI_OPT_CONNECT_TIMEOUT, $timeout)) {
			return trigger_error("Db Config error. Wrong Database option [timeout] type: '{$timeout}'", E_USER_WARNING);
		}

		if(@mysqli_real_connect($mysqli, $ip, $username, $password, $database, $port)) {
			if(@mysqli_set_charset($mysqli, $charset)) {
				return $mysqli;
			}

			return trigger_error("Db connect error. Error When Set Db Charset", E_USER_WARNING);
		}

		return trigger_error("Db connect error. Cannot connect to host: [{$username}@{$ip}@{$port}]", E_USER_WARNING);
	}

    private function __extraDbInfo($src) {

    }

	final private function __doQuery($link, $sql) {
		$start = microtime(true);
		$ret = mysqli_query($link, $sql);
		$end = microtime(true);

		$this->cache("last_sql", $sql);
		$this->queries(hash("md4", $sql), $end-$start);
		$this->_counter("QUERY_NUMS");
		$this->_counter("QUERY_NUMS", 1);
		
		return $ret;
	}

	final private function _useDb($link, $db) {
		if(!empty($link) && $link) {
			return mysqli_select_db($link, $db);
		}

		return false;
	}

	final private function _lastQuery() {
		return $this->cache("last_sql");
	}

	final private function _queryNums() {
		return $this->_counter("QUERY_NUMS");
	}

	final private function _queries() {
		return $this->queries();
	}

	final private function _isReady() {
		return !empty($this->link) && $this->link;
	}

	final private function _isError() {
		return $this->_errorCode() > 0;
	}

	final private function _query($sql) {
		if(!$this->_isReady()) {
			return trigger_error("Db is not ready", E_USER_WARNING);
		}

		return $this->__doQuery($this->link, $sql);
	}

	final private function _count($sql) {
		$res = $this->_query($sql);

		if($this->isError()) {
			return 0;
		}

		$count = mysqli_fetch_array($res);
		mysqli_free_result($res);

		return $count[0][0];
	}

	final private function _first($sql) {
		$res = $this->_query($sql);
		
		if($this->isError()) {
			return false;
		}

		$ret = mysqli_fetch_assoc($res);
		mysqli_free_result($res);

		return $ret;
	}

	final private function _all($sql) {
		$ret = array();
		$res = $this->_query($sql);

		if($this->isError()) {
			return false;
		}

		while ($row = mysqli_fetch_assoc($res)) {
			$ret[] = $row;
		}

		mysqli_free_result($res);

		return $ret;
	}

	final private function _field($sql) {
		$ret = false;
		$res = $this->_query($sql);

		if($this->isError()) {
			return false;
		}

		$ret = mysqli_fetch_row($res)[0];
		mysqli_free_result($res);

		return $ret[0];
	}

	final private function _affected() {
		if($this->_isReady()) {
			return mysqli_affected_rows($this->link);
		}

		return false;
	}

	final private function _transaction($sqls) {
		if(!$this->_isReady())
		{
			return false;
		}

		mysqli_autocommit($this->link, false);
		foreach($sqls as $sql)
		{
			$ret =  $this->_query($this->link, $sql);
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

	final private function _lastInsertId() {
		return mysqli_insert_id($this->link);
	}

	final private function _realEscapeString($str) {
		if($this->_isReady()) {
			return mysqli_real_escape_string($this->link, $str);
		}

		return false;
	}

	final private function _error() {
		if($this->_isReady()) {
			return mysqli_error($this->link);
		}

		return "Db is not ready";
	}

	final private function _errorCode() {
		return mysqli_errno($this->link);
	}
}
?>