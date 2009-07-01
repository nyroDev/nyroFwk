<?php

class module_pages_controller extends module_abstract {

	protected function execFrontHome(array $prm = array()) {
	}

	protected function execError(array $prm = array()) {
		response::getInstance()->addTitleBefore('Erreur', ' :: ');
		$this->setViewVar('error', $prm[0]);
	}
	
	protected function execAdminLogin(array $prm = array()) {
		security::getInstance()->login();
	}
	
	protected function execAdminHome(array $prm = array()) {
		security::getInstance()->protect();
	}

	protected function execAdminLogout(array $prm = array()) {
		security::getInstance()->logout();
		response::getInstance()->redirect(request::uri('/'));
	}
}
