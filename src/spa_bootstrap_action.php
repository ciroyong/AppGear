<?php
/**
* 
*/
class SpaBootstrapAction extends SpaBaseAction
{
    protected static $instance = null;
    protected static $configs = array();
    protected $dependencies = array("SpaRouter");
	protected function __construct()
	{
		parent::__construct();
	}

	public function execute() {
        $router = $this->injector->get("SpaRouter");
        $route = $router->resolve(helper::get_path_info());
        $module = Spa::module($route);
        $module->execute();
	}
}
?>