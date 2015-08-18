<?php
/*
 * Error backoff object
**/
final class MinError {
	protected $levels = array("warning", "notice", "error");
	public function __call($method, $arguments) {
		return $this->__shortcut($method, $arguments);
	}

	protected function __shortcut($method, $arguments) {
		if (in_array($method, $this->levels)) {
			$message = call_user_func_array("sprintf", $arguments);
			return call_user_func(array($this, "_{$method}"), $message);
		}

		return $this->_error("undefined method, %s::%s ", "MinError", "{$method}");
	}

	final private function _warning($message) {
		return trigger_error($message, E_USER_WARNING);
	}

	final private function _notice($message) {
		return trigger_error($message, E_USER_NOTICE);
	}

	final private function _error($message) {
		return trigger_error($message, E_USER_ERROR);
	}
}

/*
 * @class Ag
**/
class Ag {
	static protected $instance = null;
	static protected $defaults = array();
	static protected $cipher = null;
	static protected $shortcut = array("resolve", "launch", "magic", "autoload");
    protected $preserved = array("config", "injector");
    protected $scope = null;
	protected $dependencies = array();
	protected $magics = array();
	protected $minError;

	/*
	 * constructor
	**/
	protected function __construct($cipher, $defaults) {
		$this->scope = array("cipher"=>$cipher, "defaults"=>$defaults);
		$this->minError = new MinError();
		$this->__onConstruct();
	}

	static public function getInstance() {
		if (is_null(static::$cipher)) {
			static::$cipher = hash("md4", mt_rand(0, time()));
		}

		if (is_null(static::$instance)) {
			static::$instance = new static(static::$cipher, static::$defaults);
		}

		return static::$instance;
	}

	/*
	 * 
	**/
	protected function __clone() {}
	public function __set($name, $value) {}
	public function __get($name) {}

	/*
	 * 
	**/
	static public function __callStatic($method, $arguments) {
		return static::__onStaticCall($method, $arguments);
	}

	/*
	 * 
	**/
	public function __call($method, $arguments) {
		return $this->__onInstanceCall($method, $arguments);
	}

	protected function __onConstruct() {
		$this->__config();
	}

	static protected function __onStaticCall($method, $arguments) {
		return forward_static_call(array("static", "_shortcut"), $method, $arguments);
	}

	protected function __onInstanceCall($method, $arguments) {
		if (preg_match('/^[^_]+_' . $this->scope['cipher'] . '$/', $method)) {
			return $this->__shortcut($method, $arguments);
		}

		$whitelist = array_unique(array_merge($this->magics, $this->preserved));
		
		if (in_array($method, $whitelist)) {
			return $this->__magic($method, $arguments);
		}

		return $this->minError->warning("undefined method, %s::%s", get_called_class(), $method);
	}


	static protected function _shortcut($method, $arguments) {
		$_static = get_called_class();
		$whitelist = $_static::$shortcut;

		$instance = static::getInstance();
		$cipher = static::$cipher;
		if(in_array($method, $whitelist)) {
			return call_user_func_array(array($instance, "{$method}_{$cipher}"), $arguments);
		}


		return $instance->minError->warning("undefined method, %s::%s", get_called_class(), $method);
	}

	protected function __shortcut($method, $arguments) {
		$method = preg_replace('/^([^_]+)_' . $this->scope['cipher'] . '$/', "_$1", $method);
		return call_user_func_array(array($this, $method), $arguments);
	}

	private function _magic(&$chunk=null, $name=null, $value=null) {
		if(is_null($chunk)) {
			return array();
		}

		if(is_null($name) && is_null($value)) {
			return array_merge(array(), $chunk);
		}

		if (is_string($name)) {
			if(is_null($value)) {
				if(isset($chunk[$name])) {
					return $chunk[$name];
				}
				return null;
			}

			$chunk[$name]=$value;
			return true;
		}

		if(is_array($name)) {
			foreach ($name as $key => $val) {
				$chunk[$key] = $val;
			}

			if(is_array($value)) {
				foreach ($value as $key => $val2) {
					$chunk[$key] = $val2;
				}
			}
		}
	}

	protected function __magic($method, $arguments) {
		$cipher = $this->scope["cipher"];
		list($name, $value) = array_pad($arguments, 2, null);		
		if(!isset($this->scope["{$cipher}~{$method}"]))	{
			$this->scope["{$cipher}~{$method}"] = array();
		}

		return $this->_magic($this->scope["{$cipher}~{$method}"], $name, $value);
	}

	/*
	 * 
	**/
	final protected function __config() {
		$this->config($this->scope["defaults"]);
	}

	/*
	 * 
	**/
    final private function _launch() {
    	$path_info = AgHttp::value("path_info");
    	$action = $this->__resovle($this->__dispatch($path_info));
        return $action::start();
    }

	/*
	 * 
	**/
    final private function __dispatch($path_info) {
        if(is_string($path_info)) {
            $path_info =helper::filter_url($path_info);
            if($path_info === "") {
            	return AgConfig::defaults("defaultAction");
            }

            $rules = AgConfig::route();
			foreach($rules as $name => $value) {
				if(preg_match($name, $path_info, $matches)) {
					return preg_replace($name, $value, $path_info);
				}
			}
			
    		return AgConfig::defaults("errorAction"). "?page=404";
        }

        return AgConfig::defaults("errorAction") . "?page=403";
    }

    final private function __resovle($rule) {
		$parse = parse_url($rule);
    	$action = $parse["path"];
    	if(isset($parse["query"])) {
    		$options = parse_str($parse["query"]);
    		if (is_array($options)) {
		    	$action::option($options);
    		}
    	}
    	return $action;
    }

	/*
	 * 
	**/
	final private function _autoload($name) {
		$filename = AgConfig::path($name);
		if(file_exists($filename)) {
			require_once $filename;
			return true;
		}	

		// $this->minError->warning("Failed load class: %s", $name);
		return false;
	}
}

/*
 * 
**/
class_alias("Ag", "AgBaseProvider");

/*
 * 
**/
spl_autoload_register(function ($className) {
	return Ag::autoload("$className");
});
?>