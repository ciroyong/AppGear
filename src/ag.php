<?php
if (!defined("AG_DIR")) {
	define("AG_DIR", __DIR__ . DIRECTORY_SEPARATOR);
}

/*
 * Error backoff object
**/
final class MinError {
	protected $levels = array("warning", "notice", "error");
	public function __call($method, $arguments) {
		return $this->__shotcut($method, $arguments);
	}

	protected function __shotcut($method, $arguments) {
		if (in_array($method, $this->levels)) {
			$message = call_user_func_array("sprintf", $arguments);
			return call_user_func(array($this, "_{$method_exists}"), $message);
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
	static protected $shotcut = array("resolve", "launch", "magic", "autoload");
    protected $preserved = array("config", "injector");
    protected $scope=null;
	protected $dependencies = array();
	protected $magics = array();
	protected $minError;

	/*
	 * constructor
	**/
	protected function __construct($cipher, $defaults) {
		$this->scope = array("cipher"=>$cipher, "minError"=>new MinError, "defaults"=>$defaults);
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
		$this->__autoInject();
	}

	static protected function __onStaticCall($method, $arguments) {
		return forward_static_call(array("static", "_shotcut"), $method, $arguments);
	}

	protected function __onInstanceCall($method, $arguments) {
		if (preg_match('/^[^_]+_' . $this->scope['cipher'] . '$/', $method)) {
			return $this->__shotcut($method, $arguments);
		}

		$whitelist = array_unique(array_merge($this->magics, $this->preserved));
		
		if (in_array($method, $whitelist)) {
			return $this->__magic($method, $arguments);
		}

		return $this->minError->warning("undefined method, %s::%s", get_called_class(), $method);
	}


	static protected function _shotcut($method, $arguments) {
		$whitelist = static::$shotcut;
		if(in_array($method, $whitelist)) {
			$instance = static::getInstance();
			$cipher = static::$cipher;
			return call_user_func_array(array($instance, "{$method}_{$cipher}"), $arguments);
		}

		return $this->minError->warning("undefined method, %s::%s", get_called_class(), $method);
	}

	protected function __shotcut($method, $arguments) {
		$method = preg_replace('/^([^_]+)_' . $this->scope['cipher'] . '$/', "_$1", $method);
		return call_user_func_array(array($this, $method), $arguments);
	}

	protected function __magic($method, $arguments) {
		$cipher = $this->scope["cipher"];
		list($name, $value) = array_pad($arguments, 2, null);		
		if(!isset($this->scope["{$cipher}~{$method}"]))	{
			$this->scope["{$cipher}~{$method}"] = array();
		}

		return $this->_magic($this->scope["{$cipher}~{$method}"], $name, $value);
	}

	protected function _counter($counter, $addup=null) {
		if (!is_string($counter) || strlen($counter) < 1) {
			return $this->minError->warning("Wrong counter name format: %s", $counter);
		}

		if (is_null($addup)) {
			return $this->_counter($counter, 0);
		}

		$cipher = $this->scope["cipher"];

		if (!isset($this->scope["{$cipher}~{$counter}"])) {
			return $this->scope["{$cipher}~{$counter}"] = $addup;
		}

		if (is_numeric($step)) {
			return $this->scope["{$cipher}~{$counter}"] += $step;
		}
	}

	/*
	 * 
	**/
	final protected function __config() {
		$this->config($this->scope["defaults"]);
	}

    final protected function __autoInject() {
		if(!is_null($this->dependencies) && is_array($this->dependencies)) {
			foreach ($this->dependencies as $key => $value) {
				$this->injector($value, $this->_module($value));
			}
		}
	}

    /*
     * Resolve Ag Uri
     * @param: $uri
     * @returnValue: array tokens
    **/
    final private function _resolve($uri) {
        $tokens = helper::parse_uri($uri);
        if(is_null($tokens)) {
            return $this->minError->warning("error route info");
        }


        return $this->__dispatch($tokens);
    }

	/*
	 * 
	**/
	final private function _route($path) {
        if(is_string($path)) {
            $path =helper::filter_url($path);

            if($path === "") {
            	return AgConfig::get("Ag:defult_action");;
            }

            $rules = AgConfig::get("Ag:rules");

			foreach($rules as $name => $value) {
				if(preg_match($name, $path, $matches)) {
					return preg_replace($name, $value, $path);
				}
			}
			
    		return AgConfig::get("Ag:not_found_action");
        }

        return AgConfig::get("Ag:error_action") . "?page=403";
	}    
	/*
	 * 
	**/
	final private function _module($name) {
		if(is_string($name)) {
			if (class_exists($name)) {
				$instance = forward_static_call(array($name, "getInstance"));
				return $instance;
			}
		}

		return null;
	}

	/*
	 * 
	**/
    final private function __dispatch($tokens) {
        $scheme = isset($tokens["scheme"])?$tokens["scheme"]:null;

        if($scheme === "config") {
        	AgConfig::option($tokens["queries"]);
        	AgConfig::load(trim($tokens["path"], "/"), $tokens["host"]);
        	return true;
        }

        if($scheme === "action") {
        	$action = $this->_module($tokens["host"]);
        	$action->option($tokens["queries"]);
        	return $action->start();
        }
     }

	/*
	 * 
	**/
    final private function _launch() {
        return $this->_resolve($this->_route(AgHttp::value("path_info")));
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

	/*
	 * 
	**/
	final private function _autoload($name, $path=AG_DIR) {
		if(class_exists($name))
            return true;
		
		$filename = strtolower(
		    preg_replace('/(?=[[:upper:]])/', "_",
		    lcfirst($name), -1));

		if (!!strpos($filename, "_")) {
			$pathname = preg_replace('/^([^_]*(?=_)).*/', "$1", $filename);
			if($pathname != "ag") {
				$paths = AgConfig::get("Ag:paths");
				$baseDir = AgConfig::get("Ag:paths"); 
				if (isset($paths[$pathname])) {
					$path = $baseDir . $paths[$pathname];
				}
			}
		}

		$filename = "{$path}{$filename}.php";

		if(file_exists($filename)) {
			require_once $filename;
			return true;
		}

		$this->minError->warning("Failed load class: %s", $name);
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