<?php 

/**
* 
*/
class SpaRouter extends SpaBaseProvider
{
	protected static $instance = null;
	protected static $configs = array(
		"baseDir" => SPA_DIR,
		"default_action" => "action://SpaMain",
		"not_found_action" => "action://SpaError/?page=404",
	);

	protected $dependencies = array("SpaConfig");

	protected function __construct()
	{
		parent::__construct();
		$this->loadConfig();
	}

	private function loadConfig() {
		$conf = $this->injector->get("SpaConfig");
		$configs = $conf->get("config://routes");
		$this->config($configs);
	}

	public function resolve($path_info=null) {
		
	}
}
?>