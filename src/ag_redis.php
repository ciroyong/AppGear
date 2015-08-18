<?php

class AgRedis extends AgBaseProvider {
	static protected $instance = null;
	static protected $defaults = array();
	static protected $cipher = null;
	static protected $shortcut = array("exists", "key", "hash");
	protected $scope = null;
    protected $dependencies = array();
    protected $magics = array();
    protected $minError;

    private $redis = null;

    private function __createRedis() {
    	if(is_null($this->redis)) {
    		$this->redis = new Redis();
            $this->redis->connect("127.0.0.1", 6379);
    	}

    	return $this->redis;
    }

    final protected function _hash($key=null, $field=null, $value = null) {
    	$redis = $this->__createRedis();

    	if(!is_string($key)) {
    		return $this->minError->warning("necessary argument {key} must type of string");
    	}

    	if(is_null($field) && is_null($value)) {
    		return $redis->hgetall($key);
    	}

    	if(is_string($field)) {
    		if(is_null($value)) {
    			if($redis->hexists($key, $field)) {
    				return $redis->hget($key, $field);
    			}

    			return null;
    		}

    		if(is_string($value)) {
    			$redis->hset($key, $field, $value);
    			return true;
    		}

    		return false;
    	}
    }

    final protected function _exists($key)  {
    	$redis = $this->__createRedis();

    	return $redis->exists($key);
    }

   	final protected function _key($key = null, $value = null) {
   		$redis = $this->__createRedis();

   		if(is_null($key) && is_null($value)) {
   			return $redis->keys();
   		}

   		if(is_string($key)) {
   			if(is_null($value)) {
   				if($redis->exists($key)) {
					return $redis->get($key);
				}

				$keys = $redis->keys($value);

				if(sizeof($keys)>0) {
					return $keys;
				}

				return null;
   			}

   			$redis->set($key, $value);
   		}

   		if (is_array($key)) {
   			foreach ($value as $_key => $_value) {
   				$this->_key($_key, $_value);
   			}
   		}
   	}
}
?>