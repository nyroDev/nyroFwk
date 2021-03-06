<?php
/**
 * @author Cédric Nirousset <cedric@nyrodev.com>
 * @version 0.2
 * @package nyroFwk
 */
/**
 * nyroUtils Controller to handle default action
 */
class module_nyroUtils_controller extends module_abstract {

	protected function execIcon($prm=null) {
		response::getInstance()->neverExpire();
		if (array_search('..', $prm) !== false)
			response::getInstance()->error(null, 403);
		response::getInstance()->showFile(file::nyroExists(array(
			'name'=>'icons'.DS.implode(DS, $prm),
			'realName'=>true,
			'type'=>'other'
		)));
	}

	protected function execUploadedFiles($prm=null) {
		if (array_search('..', $prm) !== false)
			response::getInstance()->error(null, 403);
		$text = request::get('text');

		$file = FILESROOT.urldecode(implode(DS, $prm));
		if ($text)
			$file.= DS.$text;

		$out = request::get('out');
		if ($out != 'html')
			$file.= '.'.$out;

		response::getInstance()->showFile($file);
	}

	protected function execTinyMce($prm=null) {
		$search = 'js/tiny_mce/';
		$request = request::get('request');
		$pos = strpos($request, $search);
		if ($pos === false)
			exit;
		$tmp = substr($request, $pos+strlen($search));
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
			define('TINYMCECACHEPATH', substr(TMPROOT, 0, -1));
			if (ob_get_length())
				ob_clean();
			include($file);
			exit;
		} else
			response::getInstance()->showFile($file);
	}

}
