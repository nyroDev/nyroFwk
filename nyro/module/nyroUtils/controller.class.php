<?php

class module_nyroUtils_controller extends module_abstract {

	protected function execIcon($prm=null) {
		response::getInstance()->showFile(file::nyroExists(array(
			'name'=>'icons'.DS.implode(DS, $prm),
			'realName'=>true,
			'type'=>'other'
		)));
	}
}
