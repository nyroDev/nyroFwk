<?php

class module_nyroUtils_controller extends module_abstract {

	protected function execIcon($prm=null) {
		response::getInstance()->neverExpire();
		response::getInstance()->showFile(file::nyroExists(array(
			'name'=>'icons'.DS.implode(DS, $prm),
			'realName'=>true,
			'type'=>'other'
		)));
	}
	
	protected function execUploadedFiles($prm=null) {
		response::getInstance()->showFile(FILESROOT.implode(DS, $prm));
	}
	
	protected function execTinyMce($prm=null) {
		$tmp = str_replace('js/tiny_mce/', '', request::get('request'));
		$file = file::nyroExists(array(
			'name'=>'lib'.DS.'tinyMce'.DS.$tmp,
			'realName'=>true,
			'type'=>'other'
		));
		if (strpos($file, '.php') !== false) {
			array_walk($_GET, create_function('&$v', '$v = urldecode($v);'));
			$path = str_replace($tmp, '', $file);
			ini_set('include_path', $path);
			define('TINYMCEPATH', substr($path, 0, -1));
			include($file);
			exit;
		} else {
			response::getInstance()->neverExpire();
			response::getInstance()->showFile($file);
		}
	}
}
