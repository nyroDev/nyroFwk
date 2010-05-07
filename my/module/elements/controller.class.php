<?php

class module_elements_controller extends module_abstract {

	protected function renderAdminMenu(array $prm = null) {
		$links = array();
		if (security::getInstance()->isLogged()) {
			$db = db::getInstance();
			$tables = $db->getTables();
			foreach($tables as $t) {
				if (!strpos($t, '_') && !strpos($t, db::getCfg('i18n')))
					$links[$t] = request::uriDef(array('module'=>$t, 'action'=>'', 'param'=>''));
			}
		}
		$this->setViewVar('linksTable', $links);
	}
}
