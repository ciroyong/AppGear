<?php
/*
 *
**/
abstract class SpaBaseAction extends SpaBaseProvider{
    static protected $instance = null;
    static protected $defaults = array();
    static protected $cipher = null;
    static protected $shotcut = array();
    protected $scope;
    protected $dependencies=array();
    protected $magics = array("option", "data", "view");
    protected $minError;

    abstract public function start();

    
}
?>