<?php
/**
 * @author Cédric Nirousset <cedric@nyrodev.com>
 * @version 0.2
 * @package nyroFwk
 */
/**
 * Compress controller
 */
class module_compress_controller extends module_abstract {

	protected function execCssExt($prm=null) {
		$file = $prm[0];
		if (request::get('text'))
			$file.= DS.request::get('text');
		response::getInstance()->showFile(file::nyroExists(array(
			'name'=>'module'.DS.nyro::getCfg()->compressModule.DS.'css'.DS.$file,
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

		$key = $type.'--'.md5(implode('---', $prm)).'--'.md5(implode('---', $conf));
		$supportsGzip = false;
		if ($conf['compress']) {
			$encodings = isset($_SERVER['HTTP_ACCEPT_ENCODING']) ? explode(',', strtolower(preg_replace("/\s+/", "", $_SERVER['HTTP_ACCEPT_ENCODING']))) : array();
			if ($conf['gzip_compress'] && (in_array('gzip', $encodings) || in_array('x-gzip', $encodings) || isset($_SERVER['---------------'])) && function_exists('gzencode') && !ini_get('zlib.output_compression')) {
				$enc = in_array('x-gzip', $encodings) ? 'x-gzip' : 'gzip';
				$supportsGzip = true;
				$key = 'gzip-'.$key;
			}
		}
		$content = null;
		$cache = cache::getInstance($this->cfg->cache);
		if (!$conf['disk_cache'] || !$cache->get($content, array('id'=>$key))) {
			foreach($prm as $file) {
				$f = file::nyroExists(array(
								'name'=>'module_'.nyro::getCfg()->compressModule.'_'.$type.'_'.$file,
								'type'=>'tpl',
								'tplExt'=>$type
							));
				if ($f) {
					if ($conf['php'])
						$content.= file::fetch($f);
					else
						$content.= file::read($f);
				}
			}
			
			if ($conf['compress']) {
				if ($type == 'js') {
					$content = JSMin::minify($content);
				} else if ($type == 'css') {
					$content = CssMin::minify($content, $conf['filters'], $conf['plugins']);
				}
				if ($supportsGzip)
					$content = gzencode($content, 9, FORCE_GZIP);
			}
			$cache->save();
		}

		$resp = response::getInstance();
		
		/* @var $resp response_http */
		if ($conf['compress']) {
			$resp->setCompress(false);
			$resp->addHeader('Vary', 'Accept-Encoding'); // Handle proxies
			if ($conf['etags'] || preg_match('/MSIE/i', $_SERVER['HTTP_USER_AGENT'])) {
				// We need to use etags on IE since it will otherwise always load the contents
				$resp->addHeader('ETag', md5($content));
			}
			$parseTime = $this->_parseTime($conf['expires_offset']);
			$resp->addHeader('Expires', gmdate('D, d M Y H:i:s', time() + $parseTime).' GMT');
			$resp->addHeader('Cache-Control', 'public, max-age='.$parseTime);
			
			if ($type == 'js') {
				// Output explorer workaround or compressed file
				if (!isset($_GET['gz']) && $supportsGzip && $conf['patch_ie'] && strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== false) {
					// Build request URL
					$url = $_SERVER['REQUEST_URI'];

					if (isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'])
						$url.= '?'.$_SERVER['QUERY_STRING'].'&gz=1';
					else
						$url.= '?gz=1';

					// This script will ensure that the gzipped script gets loaded on IE versions with the Gzip request chunk bug
					echo 'var gz;try {gz = new XMLHttpRequest();} catch(gz) { try {gz = new ActiveXObject("Microsoft.XMLHTTP");}';
					echo 'catch (gz) {gz = new ActiveXObject("Msxml2.XMLHTTP");}}';
					echo 'gz.open("GET", "'.$url.'", false);gz.send(null);eval(gz.responseText);';
					die();
				}
			}
			
			if ($supportsGzip)
				$resp->addHeader('Content-Encoding', $enc);	
		}
		
		$resp->sendText($content);
	}
	
	protected function _parseTime($time) {
		$multipel = 1;

		// Hours
		if (strpos($time, "h") != false)
		$multipel = 60 * 60;

		// Days
		if (strpos($time, "d") != false)
		$multipel = 24 * 60 * 60;

		// Months
		if (strpos($time, "m") != false)
		$multipel = 24 * 60 * 60 * 30;

		// Trim string
		return intval($time) * $multipel;
	}

}
