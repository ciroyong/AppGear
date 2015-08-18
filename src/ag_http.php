<?php
class AgHttp extends AgBaseProvider {
	static protected $instance = null;
	static protected $defaults = array();
	static protected $cipher = null;
	static protected $shortcut = array("value", "session", "cookie", "header");
	protected $scope=null;
    protected $dependencies = array();
    protected $magics = array("_value");
    protected $minError;

	private function __initialValues() {
		session_start();
		$this->_value($_GET, $_POST);
		$this->_value(array(
			"path_info" => $this->__get_path_info(),
			"request_method" => $this->__get_request_method(),
		), $this->__get_parameters());
	}

	protected function __onConstruct() {
		parent::__onConstruct();
		$this->__initialValues();
	}

	private function __get_path_info() {
		return trim($_SERVER["PATH_INFO"], "/");
	}

	private function __get_parameters() {
		$payload = $this->_header("payload");
		$parameters = array();

		if (is_string($payload)) {
			parse_str($payload, $parameters);
		}

		return array_merge($parameters, $_GET, $_POST);
	}

	private function __get_request_method() {
		$request_method = strtolower($_SERVER["REQUEST_METHOD"]);
		$request_method_pass_through = $this->_value("_method");

		if ($request_method==="post") {
			if (in_array($request_method_pass_through, array("put", "delete"))) {
				return $request_method_pass_through;
			}
		}

		return $request_method;
	}

	private function __get_client_ip() {
		$dict = array("HTTP_X_FORWARDED_FOR", "HTTP_CLIENTIP", "HTTP_CLIENT_IP", "REMOTE_ADDR");
		foreach ($dict as $key => $value) {
			if (isset($_SERVER[$value])) {
				if ($value === "HTTP_X_FORWARDED_FOR") {
					return trim(preg_replace("/^([^,]*).*/", "$1", strip_tags($_SERVER[$value])));
				}

				return trim(strip_tags($_SERVER[$value]));
			}
		}

		return "0.0.0.0";
	}

	private function __get_connect_ip($transmit=false) {
		$dict = array("HTTP_CLIENTIP", "HTTP_X_FORWARDED_FOR", "HTTP_CLIENT_IP", "REMOTE_ADDR");

        if(!$transmit && isset($_SERVER['REMOTE_ADDR']) )
		{
        	return strip_tags($_SERVER['REMOTE_ADDR']);
        }

        foreach ($dict as $key => $value) {
        	if(isset($_SERVER[$value])) {
        		if ($value === "HTTP_X_FORWARDED_FOR") {
        			return trim(preg_replace("/^([^,]*).*/", "$1", strip_tags($_SERVER[$value])));
        		}

        		return trim(strip_tags($_SERVER[$value]));
        	}
        }

        return "0.0.0.0";
	}

	private function _is_valid_ip($value=null) {
		if (!is_null(is_string($value))) {
			return !!ip2long($value);
		}

		return false;
	}

	final protected function _cookie($name=null, $value=null, $expire=null, $path=null, $domain=null) {
		if(is_string($name)) {
			if(is_string($value)) {
				setcookie($name, $value, $expire, $path, $domain);
				return true;
			}

			if(isset($_COOKIE[$name])) {
				return $_COOKIE[$name];
			}

			return null;
		}

		if(is_array($name)) {
			foreach ($name as $key => $val) {
				$this->cookie($key, $val, $value, $expire, $path);
			}

			return true;
		}

		return null;
	}

	final protected function _session($name=null, $value=null) {
		if(is_string($name)) {
			if(is_string($value)) {
				$_SESSION[$name] = $value;
				return true;
			}

			if(isset($_SESSION[$name])) {
				return $_SESSION[$name];
			}

			return null;
		}

		if(is_array($name)) {
			foreach ($name as $key => $val) {
				$this->session($key, $val);
			}
		}

		return null;
	}

	private function __get_all_headers() {
		if (function_exists('getallheaders')){
			return getallheaders();
		} 

		$headers = '';

		foreach ($_SERVER as $name => $value) { 
			if (substr($name, 0, 5) == 'HTTP_') {
				$headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value; 
			}
		} 

		return $headers; 
	}

	private function _header($name=null, $value=null) {
		if(is_string($name)) {
			if(is_string($value)) {
				header("{$name}: {$value}");
				return true;
			}

			$headers = $this->__get_all_headers();

			if(isset($headers[$name])) {
				return $headers[$name];
			}

			return null;
		}

		if(is_array($name)) {
			foreach ($name as $key => $val) {
				$this->header($key, $val);
			}

			return true;
		}

		return null;
	}

	private function _user_agent() {
		return $this->header("User-Agent");
	}

	private function _location($location) {
		return header("Location: {$location}");
	}

	private function _content_type($value) {
		return header("Content-Type: {$value}");
	}
}
?>