<?php

/*
 *
**/
abstract class SpaBaseController extends SpaBaseProvider {
	static protected $instance = null;
    static protected $defaults = array();
    static protected $cipher = null;
    static protected $shotcut = array();
    protected $scope;
    protected $dependencies=array();
    protected $magics = array("option", "data");
    protected $minError;
	abstract public function execute();	
}
?>