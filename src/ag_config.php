<?php
/**
* 
*/
class AgConfig extends AgBaseProvider
{
    static protected $instance = null;
    static protected $defaults = array();
    static protected $cipher = null;
    static protected $shotcut = array("get", "load", "cache", "option");
    protected $scope=null;
    protected $dependencies = array();
    protected $magics = array("_cache", "_option");
    protected $minError;
    
    final protected function _get($key) {
        list($component, $namespace) = array_pad(explode(":", $key), 2, null);
        $chunk = explode(".", $namespace);
        $ret = $this->_cache($component);

        while (sizeof($chunk)>0) {
            $name = array_shift($chunk);
            $ret = $ret[$name];
        }


        return $ret;
    }

    final protected function _load($path, $cache=null) {
        $content = helper::get_file_content($path);
        $config = json_decode($content, true);

        if(is_null($cache)) {
            return $config;
        }

        return $this->_cache($cache, $config);
    }
}
?>