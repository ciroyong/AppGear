<?php
/**
* 
*/
class SpaIntent extends SpaBaseProvider
{
    protected static $instance=null;
    protected static $configs=array();
    protected $dependencies = array("SpaHttp");
    
    protected function __construct()
    {
        parent::__construct();
        $this->initialize();
    }

    private function initialize() {
    	$http
    	$this->attr()
    }
}
?>