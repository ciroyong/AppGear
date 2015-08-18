<?php
/*
 *
**/
abstract class AgBaseAction extends AgBaseProvider{
    static protected $instance = null;
    static protected $defaults = array();
    static protected $cipher = null;
    static protected $shortcut = array("start");
    protected $scope;
    protected $dependencies = array();
    protected $magics = array("option", "data", "view");
    protected $minError;

    abstract protected function _initialize();
    abstract protected function _start();

    protected function __onConstruct() {
    	parent::__onConstruct();
        $this->config(AgConfig::metadata());
    	$this->_initialize();
    }
}
?>