<?php

/**
* 
*/
class AgTemplateLexer extends AgBaseProvider {
	static protected $instance = null;
    static protected $defaults = array();
    static protected $cipher = null;
    static protected $shotcut = array();
    protected $scope=null;
    protected $dependencies = array();
    protected $magics = array();
    protected $minError;
}
?>