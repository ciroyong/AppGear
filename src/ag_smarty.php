<?php
require_once "Smarty-3.1.21/libs/Smarty.class.php";
class AgSmarty extends AgBaseProvider
{
    static protected $instance = null;
    static protected $defaults = array();
    static protected $cipher = null;
    static protected $shortcut = array("assign", "display", "fetch");
    protected $scope=null;
    protected $dependencies = array();
    protected $magics = array("_cache", "_option");
    protected $minError;
    private $smarty = null;

	private function getSmarty() {
		if(is_null($this->smarty)) {
			$this->smarty = new Smarty();
			$this->smarty->setTemplateDir(AgConfig::smarty("template_dir"));
	        $this->smarty->setCompileDir(AgConfig::smarty("compile_dir"));
	        $this->smarty->setConfigDir(AgConfig::smarty("config_dir"));
	        $this->smarty->setCacheDir(AgConfig::smarty("cache_dir"));
		}

		return $this->smarty;
	}

    final protected function _display($tpl) {
    	$smarty = $this->getSmarty();
    	$smarty->display($tpl);
    }

    final protected function _assign($name, $value) {
    	$smarty = $this->getSmarty();
    	$smarty->assign($name, $value);
    }

    final protected function _fetch($tpl) {
    	$smarty = $this->getSmarty();
    	$smarty->fetch($tpl);
    }
}
?>