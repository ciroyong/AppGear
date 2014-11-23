<?php

/**
* 
*/

trait helperReflections {
	function _filter_url($url) {
		return $url;
	}

	function _get_file_content($file=null, $length=null, $mode=null) {
		if(is_null($file) || !file_exists($file)) {
			return false;
		}

		$mode = is_string($mode) && in_array($mode,
			array("r", "r+", "w", "w+", "a", "a+", "x", "x+", "c", "c+"))
			?$mode:"r";

		$filesize = filesize($file);

		if($length===null) {
			$length = $filesize;
		}

		if ($length>$filesize) {
			$length = $filesize;
		}

		if($length<=0) {
			$length = $filesize + $length;
		}

		$handle = fopen($file, $mode); 
		$content = fread($handle, $length);
		fclose($handle);
		return $content;
	}

	function _parse_uri($uri=null) {
		if (!is_null($uri)&&is_string($uri)) {
			$tokens = parse_url($uri);
			if (isset($tokens["query"])) {
				$tokens["queries"] = parse_str($tokens["query"]);
			} else {
				$tokens["queries"] = array();
			}

			return $tokens;
		}

		return array();
	}
}

class helper
{
	use helperReflections {
		_filter_url as private;
		_get_file_content as private;
		_parse_uri as private;
	}

	private function __construct() {}
	private function __clone() {}
	static private $instance  = null;
	static protected function getInstance() {
		if (is_null(self::$instance)) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	static public function __callStatic($method, $arguments) {
		$instance = self::getInstance();
		return call_user_func_array(array($instance, $method), $arguments);
	}

	public function __call($method, $arguments) {
		if (method_exists($this, "_{$method}")) {
			return call_user_func_array(array($this, "_{$method}"), $arguments);
		}
	}
}
?>