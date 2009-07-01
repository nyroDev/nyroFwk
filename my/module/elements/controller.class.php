<?php

class module_elements_controller extends module_abstract {

	protected function execAdminTinyMceImage(array $prm = null) {
		$links = array();

		/*
		$links[] = array('Nom de l'image', request::uri(array(
			'lang'=>false,
			'controller'=>false,
			'module'=>'nyroUtils',
			'action'=>'uploadedFiles',
			'param'=>str_replace('/', ',', $pf->fichier_file),
			'out'=>null
		)));
		*/
		
		response::getInstance()->sendText($this->tinyMceList('Image', $links));
	}
	
	protected function execAdminTinyMceLink(array $prm = null) {
		$links = array();

		/*
		$links[] = array('Nom du lien', request::uri(array(
			'controller'=>false,
			'lang'=>false,
			'module'=>'p',
			'action'=>$p->id,
			'param'=>$p->url,
			'out'=>null
		)));
		 */

		response::getInstance()->sendText($this->tinyMceList('Link', $links));
	}
	
	protected function tinyMceList($name, array $list) {
		$tmp = array();
		foreach($list as $l)
			$tmp[] = '["'.$l[0].'", "'.$l[1].'"]';
		return 'var tinyMCE'.$name.'List = new Array('.implode(', ', $tmp).');';
	}
	
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
