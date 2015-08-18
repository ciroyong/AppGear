<?php
/**
* 
*/
class AgConfig extends AgBaseProvider
{
    static protected $instance = null;
    static protected $defaults = array();
    static protected $cipher = null;
    static protected $shortcut = array("defaults", "path", "route", "api", "db", "metadata", "smarty", "option");
    protected $scope=null;
    protected $dependencies = array();
    protected $magics = array("_option");
    protected $minError;
    protected $redis = null;
    private $cache_token = "{cache_token}";

    final protected function _defaults($name=null) {
        return $this->__cache("defaults", $name);
    }

    final protected function _metadata($name=null) {
        return $this->__cache("metadata", $name);
    }
    
    final protected function _path($name=null) {
       return $this->__cache("path", $name);
    }

    final protected function _route($name=null) {
        return $this->__cache("route", $name);
    }

    final protected function _api($name=null) {
        return $this->__cache("api", $name);
    }

    final protected function _db($name) {
    }

    final protected function _smarty($name=null) {
        return $this->__cache("smarty", $name);
    }

    final private function __cache($name, $field) {
        $token = $this->_option("token");
        $key = $this->_option("token") . "." . $name;
        $retArr = array();

        // printf("<h1>AgConfig __cache</h1>");
        // printf("<ol>");
        // printf("<li>key is: %s, field is: %s</li>", $name, $field);

        if(AgRedis::exists($key)) {
            return AgRedis::hash($key, $field);
        }
        // printf("<li>key is: %s, field is: %s</li>", $name, $field);

        $path = $this->_option($name);
        // printf("<li>path is: %s</li>", $path);
        // printf("<li>config file exists: <b>%s</b></li>", file_exists($path));
        $json = helper::get_file_content($path);
        // printf("<li>json is: %s</li>", $json);
        $object = json_decode($json);
        // echo "<li>";
        // echo "<pre>";
        // print_r($object);
        // echo "</pre>";
        // echo "</li>";
        // printf("</ol>");

        foreach($object as $_field => $_value) {
            $retArr[$_field] = $_value;
            AgRedis::hash($key, $_field, $_value);
        }

        if(is_null($field)) {
            return $retArr;
        }

        if(array_key_exists($field, $retArr)) {
            return $retArr[$field];
        }

        return null;
    }

    final private function __load($name, $_key) {
        $path = $this->_option($name);
        $json = helper::get_file_content($path);
        $object = json_decode($json);
        
        foreach ($object as $key => $value) {
            
        }
    }
}
?>