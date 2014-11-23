<?php
/**
* 
*/
class SpaInjector
{
	protected static $instance = null;
	protected static $configs = array();
	private $modules = array();
	private $dependenies = array();

	public function __construct($deps)
	{
		if(is_array($deps)) {
			foreach ($deps as $key => $value) {
				if(in_array($value, $this->dependenies)) {
					continue;
				}

				$this->dependenies[]=$value;
			}
		}

		$this->autoInject();
	}

	public function get($module=null) {
		if(is_null($module)) {
			return $this->modules;
		}
		if(is_string($module)&&array_key_exists($module, $this->modules)) {
			return $this->modules[$module];
		}

		if(is_array($module)) {
			$modules = array();
			foreach ($module as $name => $value) {
				$modules[] = $this->get($value); 
			}

			return $modules;
		}
		return null;
	}

	private function autoInject() {
		while (sizeof($this->dependenies)>0) {
			$module = array_shift($this->dependenies);
			$instance = Spa::instantiate($module);
			$this->modules[$module] = $instance;
		}
	}
}
?>