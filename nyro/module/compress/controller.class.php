<?php

class module_compress_controller extends module_abstract {

	protected function execCssExt($prm=null) {
		response::getInstance()->neverExpire();
		response::getInstance()->showFile(file::nyroExists(array(
			'name'=>'module'.DS.nyro::getCfg()->compressModule.DS.'css'.DS.$prm[0].DS.request::get('text'),
			'realName'=>true,
			'type'=>'other'
		)));
	}
	
	protected function execJs($prm=null) {
		$this->compress('js', $prm);
	}

	protected function execCss($prm=null) {
		$this->compress('css', $prm);
	}

	/**
	 * Compress the file requested, using MoxieCompressor library
	 *
	 * @param string $type File type (css or js)
	 * @param array $prm Files to compress
	 */
	protected function compress($type, $prm) {
		if ($type == 'js') {
			$conf = array_merge_recursive($this->cfg->all, $this->cfg->js);
		} else if ($type == 'css') {
			$conf = array_merge_recursive($this->cfg->all, $this->cfg->css);
		}

		if (!$conf['compress']) {
			$tmp = null;
			foreach($prm as $file) {
				$f = file::nyroExists(array(
								'name'=>'module_'.nyro::getCfg()->compressModule.'_'.$type.'_'.$file,
								'type'=>'tpl',
								'tplExt'=>$type
							));
				if ($f) {
					if ($conf['php'])
						$tmp.= file::fetch($f);
					else
						$tmp.= file::read($f);
				}

			}
			response::getInstance()->sendText($tmp);
		}

		lib::load('MoxieCompressor');

		if ($type == 'js') {
			$compressor = new Moxiecode_JSCompressor($conf);
		} else if ($type == 'css') {
			$compressor = new Moxiecode_CSSCompressor($conf);
		}

		foreach($prm as $file) {
			$f = file::nyroExists(array(
							'name'=>'module_'.nyro::getCfg()->compressModule.'_'.$type.'_'.$file,
							'type'=>'tpl',
							'tplExt'=>$type
						));
			if ($f) {
				if ($conf['php'])
					$compressor->addContent(file::fetch($f));
				else
					$compressor->addFile($f);
			}
		}

		echo $compressor->compress();
		exit(0);
	}

}
