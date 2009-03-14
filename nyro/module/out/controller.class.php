<?php

class module_out_controller extends module_abstract_controller {

	protected $module;
	
	protected function afterInit() {
		$t = factory::get('tpl');
		$this->module = factory::getModule(request::get('module'));
		nReflection::callMethod($this->module, request::get('action').'Action', request::get('param'));
	}

	protected function indexAction($prm=null) {}
	
	protected function publish() {
		return $this->module->publish();
	}
}
