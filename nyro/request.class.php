<?php
/**
 * @author Cédric Nirousset <cedric@nyrodev.com>
 * @version 0.2
 * @package nyroFwk
 */
/**
 * Store the client request
 * Singleton
 */
final class request {

	/**
	 * Request configuration
	 *
	 * @var config
	 */
	private static $cfg;

	/**
	 * The requested request in array. In order:
	 *  - bool secure
	 *  - string protocol
	 *  - string controller
	 *  - string domain
	 *  - string path
	 *  - bool pathWithController
	 *  - string request
	 *  - string lang
	 *  - string module
	 *  - string action
	 *  - string param
	 *  - array paramA
	 *  - string text
	 *  - string out
	 *
	 * @var array
	 */
	private static $requestedUriInfo;

	/**
	 * The actual request in array. In order:
	 *  - bool secure
	 *  - string protocol
	 *  - string controller
	 *  - string domain
	 *  - string path
	 *  - bool pathWithController
	 *  - string request
	 *  - string lang
	 *  - string module
	 *  - string action
	 *  - string param
	 *  - array paramA
	 *  - string text
	 *  - string out
	 *
	 * @var array
	 */
	private static $uriInfo;

	/**
	 * Module requested
	 *
	 * @var module_abstract
	 */
	private static $module;

	/**
	 * Indicate if the module was scaffolded or not
	 *
	 * @var bool
	 */
	private static $scaffold;

	/**
	 * Mobile status caching
	 *
	 * @var string
	 */
	private static $mobileStatus = false;

	/**
	 * No instanciation for this class
	 */
	private function __construct() {}

	/**
	 * Initialize the request object
	 * Call by the init function from nyro class
	 */
	public static function init() {
		if (!empty(self::$requestArray))
			return;

		self::$cfg = new config(factory::loadCfg(__CLASS__));

		$alias = array();
		$outs = '(\.('.implode('|', array_keys(self::$cfg->outCfg)).'))';
		foreach(self::$cfg->alias as $k=>$v) {
			if ($k == '/') {
				$alias['(/.{2})?/?'] = '\1'.$v;
				$alias['(/.{2})?'.$outs] = '\1'.$v.'\2';
			} else {
				$k = '(/.{2})?'.$k;
				$tmp = explode('\\', $v);
				$t = '\\1'.$tmp[0];
				unset($tmp[0]);
				$i = 2;
				foreach($tmp as $tt) {
					$t.='\\'.(intval(substr($tt, 0, 1))+1).substr($tt, 1);
					$i++;
				}
				if (!preg_match($outs, $k))
					$alias[$k.'(.*)'] = $t.'\\'.$i;
				$alias[$k] = $t;
			}
		}
		$alias = array_reverse($alias, true);
		self::$cfg->alias = $alias;

		// Put the default lang at the top of available langs
		$avlLangsTmp = self::$cfg->avlLang;
		$avlLangs = array();
		foreach($avlLangsTmp as $k=>$v) {
			if ($k == self::$cfg->lang) {
				$avlLangs[$k] = $v;
				unset($avlLangsTmp[$k]);
			}
		}
		self::$cfg->avlLang = array_merge($avlLangs, $avlLangsTmp);

		$secure = (array_key_exists('HTTPS', $_SERVER) && $_SERVER['HTTPS'] == 'on');

		$protocol = $secure? 'https' : 'http';

		$controller = basename($_SERVER['SCRIPT_FILENAME']);

		$serverName = array_key_exists('SERVER_NAME', $_SERVER) ? $_SERVER['SERVER_NAME'] : self::$cfg->defaultServerName;
		$stdPort = $secure ? '443' : '80';
		$port = (array_key_exists('SERVER_PORT', $_SERVER) && $_SERVER['SERVER_PORT'] != $stdPort && $_SERVER['SERVER_PORT'])? ':'.$_SERVER['SERVER_PORT'] : '';
		$domain = $protocol.'://'.$serverName.$port;

		$scriptName = $_SERVER['SCRIPT_NAME'];
		$requestUri = array_key_exists('REQUEST_URI', $_SERVER) ? $_SERVER['REQUEST_URI'] : ('/'.$scriptName.(array_key_exists(1, $_SERVER['argv']) ? $_SERVER['argv'][1] : ''));

		$redir = null;
		$forceServerName = null;
		if (self::$cfg->forceServerName
		    && strpos($serverName, 'localhost') === false
		    && strtolower(self::$cfg->forceServerName) != strtolower($serverName)) {
			$forceServerName = self::$cfg->forceServerName;
			$redir = $protocol.'://'.$forceServerName.$port.$requestUri;
		}
		if (self::$cfg->forceNoOut
		    && self::$cfg->noOut
		    && ($pos = strpos($requestUri, self::$cfg->noOut))
		    && $pos + strlen(self::$cfg->noOut) == strlen($requestUri)
		    ) {
			if ($redir)
			    $redir = substr($redir, 0, -1*(strlen(self::$cfg->noOut)+1));
			else
			    $redir = $domain.substr($requestUri, 0, $pos-1);
		}
		if (self::$cfg->noController && strpos($requestUri, self::$cfg->noController) !== false) {
			if (!$redir)
			    $redir = $domain.$requestUri;
			$redir = str_replace(self::$cfg->noController.'/', '', $redir);
			if ($pos = strpos($redir, self::$cfg->noController))
				$redir = substr($redir, 0, $pos);
		}

		$path = '/';
		$requestUriTmp = explode('/', substr($requestUri, 1));
		$scriptNameTmp = explode('/', substr($scriptName, 1));
		$min = min(count($requestUriTmp), count($scriptNameTmp));
		$i = 0;
		while ($i < $min && $requestUriTmp[$i] == $scriptNameTmp[$i]) {
			$path.= $requestUriTmp[$i].'/';
			$i++;
		}

		$pathWithController = (strpos($requestUri, $controller) !== false);

		if ($pathWithController) {
			if (strpos($path, $controller) !== false)
				$path = substr($path, 0, -strlen($controller)-1);
			$len = strlen($path.$controller);
			$request = (isset($requestUri[$len]) && $requestUri[$len] == '?') ? '' : substr($requestUri, $len+1);
		} else
			$request = substr($requestUri, strlen($path));

		if (self::$cfg->forceNoLang) {
			$forceNoLang = self::$cfg->forceNoLang === true ? self::$cfg->lang : self::$cfg->forceNoLang;
			if ($requestUri != $path && strpos($requestUri, '/'.$forceNoLang.'/') !== false && $request) {
				$redir = str_replace('/'.$forceNoLang.'/', '/', $redir ? $redir : $domain.$requestUri);
			}
		} else if (self::$cfg->forceLang) {
			$forceLang = self::$cfg->forceLang === true ? self::$cfg->lang : self::$cfg->forceLang;
			if ($requestUri != $path && strpos($requestUri, '/'.$forceLang.'/') === false
					&& $request) {
				$continue = true;
				$i = 0;
				$cpt = count(self::$cfg->noForceLang);
				while($continue && $i < $cpt) {
					if (strpos($request, self::$cfg->noForceLang[$i]) === 0)
						$continue = false;
					$i++;
				}
				if ($continue) {
					// lang not found, force it
					$redirWork = $redir ? $redir : $domain.$requestUri;
					$search = ($forceServerName ? $forceServerName : $domain).$path.($pathWithController? $controller.'/' : null);
					$pos = strpos($redirWork, $search) + strlen($search);
					$end = $pos < strlen($redirWork) ? strpos($redirWork, '/', $pos+1) : false;
					$end = $end ? $end-$pos : strlen($redirWork);
					$curLang = substr($redirWork, $pos, $end);
					if (!self::isLang($curLang))
						$redir = substr($redirWork, 0, $pos).$forceLang.'/'.substr($redirWork, $pos);
				}
			}
		}

		if ($redir) {
		    header('HTTP/1.0 301 Moved Permanently');
		    header('Location: '.$redir);
		    exit;
		}

		self::extractGet($request, true);

		self::$requestedUriInfo = self::analyseRequest($request);

		self::$uriInfo = array_merge(array(
				'secure'=>$secure,
				'protocol'=>$protocol,
				'controller'=>$controller,
				'serverName'=>$serverName,
				'domain'=>$domain,
				'path'=>$path,
				'pathWithController'=>$pathWithController,
				'request'=>$request,
				'lang'=>self::$cfg->lang,
				'module'=>self::$cfg->module,
				'moduleScaffold'=>null,
				'action'=>self::$cfg->action,
				'param'=>self::$cfg->param,
				'text'=>self::$cfg->text,
				'out'=>self::$cfg->out
			), self::$requestedUriInfo);

		if (self::$cfg->forceSecure)
			request::forceSecure();

		self::fixFiles();
	}

	/**
	 * Fix the arraye $_FILES with multiple uploads
	 */
	private static function fixFiles() {
		if (!empty($_FILES)) {
			$tmp = array();
			foreach($_FILES as $k=>$p) {
				if (is_array($p['name'])) {
					if (is_array(current($p['name']))) {
						// multiple files with multiple element
						$tmp[$k] = array();
						foreach($p['name'] as $i=>$v) {
							foreach($v as $kk=>$vv) {
								$tmp[$k][$i][$kk] = array(
									'name'=>$vv,
									'type'=>$_FILES[$k]['type'][$i][$kk],
									'tmp_name'=>$_FILES[$k]['tmp_name'][$i][$kk],
									'error'=>$_FILES[$k]['error'][$i][$kk],
									'size'=>$_FILES[$k]['size'][$i][$kk],
								);
							}
						}
					} else {
						// multiple files
						$tmp[$k] = array();
						foreach($p['name'] as $i=>$v) {
							$tmp[$k][$i] = array(
								'name'=>$v,
								'type'=>$_FILES[$k]['type'][$i],
								'tmp_name'=>$_FILES[$k]['tmp_name'][$i],
								'error'=>$_FILES[$k]['error'][$i],
								'size'=>$_FILES[$k]['size'][$i],
							);
						}
					}
				} else
					$tmp[$k] = $p;
			}
			$_FILES = $tmp;
		}
	}

	/**
	 * Extract the param
	 *
	 * @param string $param
	 * @return array Parameters in array
	 */
	public static function parseParam($param) {
		$ret = array();
		$tmp = explode(self::$cfg->sepParam, $param);
		foreach($tmp as $t) {
			if (strpos($t, self::$cfg->sepParamSub)) {
				list($key, $val) = explode(self::$cfg->sepParamSub, $t);
				$ret[$key] = $val;
			} else
				$ret[] = $t;
		}
		return $ret;
	}

	/**
	 * Create the param string
	 *
	 * @param array|string $param
	 * @return string
	 */
	public static function createParam($param, $urlify = true) {
		$ret = null;
		if (is_array($param)) {
			$tmp = array();
			foreach($param as $key=>$val) {
				if ($urlify)
					$val = utils::urlify($val);
				if (!is_numeric($key))
					$tmp[] = $key.self::$cfg->sepParamSub.$val;
				else
					$tmp[] = $val;
			}
			$ret = implode(self::$cfg->sepParam, $tmp);
		} else
			$ret = $param;

		return $ret;
	}

	/**
	 * USed to forward the actual request to an other
	 *
	 * @param string $request The new request
	 * @return mixed The executed action return
	 */
	public static function forward($request) {
		self::$uriInfo = array_merge(self::$uriInfo, self::analyseRequest($request));
		self::$module = factory::getModule(self::$uriInfo['module']);
		return self::execModule();
	}

	/**
	 * Force the request to be secure, by redirting to the same url with https
	 */
	public static function forceSecure() {
		if (!self::get('secure'))
			response::getInstance()->redirect(str_replace('http://', 'https://', self::uriDef(array('absolute'=>1))));
	}

	/**
	 * Element requested
	 *
	 * @param null|string get Element you want. Possible value:
	 *  - uri
	 *  - pathUri
	 *  - rootUri
	 *  - secure
	 *  - protocol
	 *  - controller
	 *  - domain
	 *  - path
	 *  - pathWithController
	 *  - request
	 *  - lang
	 *  - module
	 *  - action
	 *  - param
	 *  - paramA
	 *  - text
	 *  - out
	 * @return array|mixed uriInfo if $get parameter is null, the whole array
	 */
	public static function get($get = null) {
		if ($get == 'uri')
			return self::get('domain').self::getPathControllerUri().self::get('request');
		if ($get == 'localUri')
			return self::getPathControllerUri().self::get('request');
		if ($get == 'pathUri')
			return self::getPathControllerUri().self::get('request');
		if ($get == 'rootUri')
			return self::get('domain').self::get('path');
		else if ($get == null)
			return self::$uriInfo;
		else
			return self::$uriInfo[$get];
	}

	/**
	 * Get an element requested in the url
	 *
	 * @param null|string $get
	 * @return array|mixed
	 * @see get
	 */
	public static function getRequested($get = null) {
		if (is_null($get)) {
			$uriInfo = self::$requestedUriInfo;
			unset($uriInfo['paramA']);
			return $uriInfo;
		} else if (array_key_exists($get, self::$requestedUriInfo))
			return self::$requestedUriInfo[$get];
		else
			return null;
	}

	/**
	 * Get a request config or the whole config array
	 *
	 * @param string|null $key Key to retrieve or null to retrieve the array
	 * @return mixed
	 */
	public static function getCfg($key = null) {
		if ($key === null)
			return self::$cfg->getVars();
		else
			return self::$cfg->get($key);
	}

	/**
	 * Check if the current request has the $key parameter
	 *
	 * @param string $key parameter name
	 * @return bool
	 */
	public static function hasPrm($key) {
		return array_key_exists($key, self::get('paramA'));
	}

	/**
	 * Get a request parameter
	 *
	 * @param string $key Parameter name
	 * @param mixed $default Default value if not defined
	 * @return mixed
	 */
	public static function getPrm($key, $default = null) {
		$prmA = self::get('paramA');
		return array_key_exists($key, $prmA)? $prmA[$key] : $default;
	}

	/**
	 * Get the URL part between the domain and the request, with the controller name if it's on the current request
	 *
	 * @param bool $forceController Force the Controller script name on the return
	 * @return string
	 */
	public static function getPathControllerUri($forceController = false) {
		$controller = ($forceController || self::get('pathWithController'))? self::get('controller').'/': null;
		return self::get('path').$controller;
	}

	/**
	 * Create an URI starting with /, regarding the current request
	 * By default, the controller will stay the same
	 *
	 * @param string|array $prm URL Parameters:
	 *  - bool absolute If the url should be absolute
	 *  - string sep Seperator if needed (default: cfg config empty)
	 *  - strong controller
	 *  - string lang (if not valid it will be ignored)
	 *  - string module
	 *  - string action
	 *  - array paramA
	 *  - string param (using only if paramA doesn't exist)
	 *  - string text
	 *  - string out (if not valid it will be ignored)
	 * @return string the URL
	 */
	public static function uri($prm = array()) {
		if (!is_array($prm))
			$prm = self::uriString($prm);

		$sep = array_key_exists('sep', $prm)? $prm['sep'] : self::$cfg->sep;

		$tmp = array_fill(0, 4, self::$cfg->empty);

		if (array_key_exists('moduleScaffold', $prm) && !empty($prm['moduleScaffold']))
			$tmp[0] = utils::urlify($prm['moduleScaffold']);
		else if (array_key_exists('module', $prm) && !empty($prm['module']))
			$tmp[0] = utils::urlify($prm['module']);

		if (array_key_exists('action', $prm) && !empty($prm['action']))
			$tmp[1] = utils::urlify($prm['action']);

		if (array_key_exists('paramA', $prm) && is_array($prm['paramA']))
			$tmp[2] = self::createParam($prm['paramA'], false);
		else if (array_key_exists('param', $prm) && !empty($prm['param']))
			$tmp[2] = $prm['param'];

		if (array_key_exists('text', $prm) && !empty($prm['text']))
			$tmp[3] = utils::urlify($prm['text']);

		while(count($tmp) > 0 && (empty($tmp[count($tmp) - 1]) || $tmp[count($tmp) - 1] == self::$cfg->empty))
			array_pop($tmp);

		$out = (array_key_exists('out', $prm) ?
				(self::isOut($prm['out'])? $prm['out'] : null)
				: self::getRequested('out'));
		if ($out) {
			if (false && empty($tmp))
				$tmp[] = self::$cfg->empty.'.'.$out;
			else if (!empty($tmp) && $out != self::$cfg->noOut)
				$tmp[count($tmp) - 1] .= '.'.$out;
		}

		$forceLang = self::$cfg->forceLang ? (self::$cfg->forceLang === true ? self::$cfg->lang : self::$cfg->forceLang) : null;
		if (array_key_exists('lang', $prm)) {
			if (self::isLang($prm['lang']))
				array_unshift($tmp, $prm['lang']);
			else if ($forceLang)
				array_unshift($tmp, $forceLang);
		} else if (self::getRequested('lang'))
			array_unshift($tmp, self::getRequested('lang'));
		else if (self::$cfg->lang != self::get('lang'))
			array_unshift($tmp, self::get('lang'));
		else if ($forceLang && count($tmp))
			array_unshift($tmp, $forceLang);
		if ($forceLang && count($tmp) == 1 && $tmp[0] == $forceLang)
			$tmp = array();

		$prefix = array_key_exists('absolute', $prm) && $prm['absolute']? request::get('domain') : null;
		$prefix.= self::get('path');
		if (array_key_exists('controller', $prm)) {
			if ($prm['controller'])
				array_unshift($tmp, $prm['controller']);
		} else if (self::get('pathWithController'))
			$prefix.= request::get('controller').'/';

		foreach($tmp as &$t)
			$t = str_replace(array(' ', '/'), self::$cfg->empty, $t);

		return $prefix.implode($sep, $tmp);
	}

	/**
	 * Make a valid URI for an uploaded File
	 *
	 * @param string $file The filepath
	 * @param array $prm Array to overwritte default uri construction
	 * @return string The valid URI
	 */
	public static function uploadedUri($file, array $prm = array()) {
		return self::uri(array_merge(array(
					'module'=>'nyroUtils',
					'action'=>'uploadedFiles',
					'param'=>str_replace(array('/', '\\'), array(request::getCfg('sepParam'), request::getCfg('sepParam')), $file),
					'out'=>null
				), $prm));
	}

	/**
	 * Create an array to create an uri with the requested value by default, overwritten by $prm
	 *
	 * @param array $prm Array to use in the uri
	 * @param array $use Element to use by default in the uri
	 * @return string the uri
	 * @see uri
	 */
	public static function uriDef(array $prm = array(), array $use = array('lang', 'module', 'action', 'param', 'out')) {
		$tmp = array();

		foreach($use as $u)
			$tmp[$u] = self::getRequested($u);

		return self::uri(array_merge($tmp, $prm));
	}

	/**
	 * Convert a string uri to an array usuable for request::uri.
	 * Starting with // will remove the controller.
	 * Starting with /// will remove the controller and absolutize the URI.
	 *
	 * @param string $uri
	 * @return array
	 */
	public static function uriString($uri) {
		$uriA = array_values(array_filter(explode(self::$cfg->sep, $uri)));

		if (empty($uriA)) {
			if ($uri == '//')
				return array('controller'=>false);
			else if ($uri == '///')
				return array('absolute'=>true, 'controller'=>false);
			else
				return array();
		}

		$tmp = array();
		if (strpos($uriA[0], '.php'))
			$tmp['controller'] = array_shift($uriA);

		if (!empty($uriA)) {
			if (self::isLang($uriA[0]))
				$tmp['lang'] = array_shift($uriA);
			if (!empty($uriA)) {
				$last = $uriA[count($uriA)-1];
				if ((($pos = strrpos($last, '.')) !== false) && self::isOut($out = substr($last, $pos+1))) {
					$tmp['out'] = $out;
					$uriA[count($uriA)-1] = substr($last, 0, $pos);
				}
				$keys = array('module', 'action', 'param', 'text');
				$min = min(count($keys), count($uriA));
				$keys = array_slice($keys, 0, $min);
				$uriA = array_slice($uriA, 0, $min);
				$tmp = array_merge($tmp, array_combine($keys, $uriA));
			}
		}

		if (substr($uri, 0, 2) == '//')
			$tmp['controller'] = false;
		if (substr($uri, 0, 3) == '///')
			$tmp['absolute'] = true;

		return $tmp;
	}

	public static function webUri($uri) {
		return self::get('path').$uri;
	}

	/**
	 * Get the IP address of the user
	 *
	 * @return string
	 */
	public static function getIp() {
		return $_SERVER['REMOTE_ADDR'];
	}

	/**
	 * Check if informations are posted
	 *
	 * @return bool
	 */
	public static function isPost() {
		return !empty($_POST);
	}

	/**
	 * Check if the request is local
	 *
	 * @return bool
	 */
	public static function isLocal() {
		return self::get('serverName') == 'localhost';
	}

	/**
	 * Check if the current request is an Ajax
	 *
	 * @return bool
	 */
	public static function isAjax() {
		return array_key_exists('HTTP_X_REQUESTED_WITH', $_SERVER);
	}

	/**
	 * Check if the current request is mobile
	 *
	 * @param null|string|array $test 3 differents options:
	 *  - null: no more test are done
	 *  - string: the mobileStatus has to be the same
	 *  - array: mobileStatus has to be in this array
	 * @return boolean
	 */
	public static function isMobile($test = null) {
		if (self::$mobileStatus === false) {
			// code from http://detectmobilebrowsers.mobi/
			// Modified to be simpler and suite the nyroFwk need
			// many comments removed

			$user_agent         = $_SERVER['HTTP_USER_AGENT'];
			$accept             = $_SERVER['HTTP_ACCEPT'];
			self::$mobileStatus = null;

			switch(true) {
				case (preg_match('/ipad/i',$user_agent));
					self::$mobileStatus = 'ipad';
					break;
				case (preg_match('/ipod/i',$user_agent)||preg_match('/iphone/i',$user_agent));
					self::$mobileStatus = 'iphone';
					break;
				case (preg_match('/android/i',$user_agent));
					self::$mobileStatus = 'android';
					break;
				case (preg_match('/opera mini/i',$user_agent));
					self::$mobileStatus = 'opera';
					break;
				case (preg_match('/blackberry/i',$user_agent));
					self::$mobileStatus = 'blackberry';
					break;
				case (preg_match('/(pre\/|palm os|palm|hiptop|avantgo|plucker|xiino|blazer|elaine)/i',$user_agent));
					self::$mobileStatus = 'palm';
					break;
				case (preg_match('/(iris|3g_t|windows ce|opera mobi|windows ce; smartphone;|windows ce; iemobile)/i',$user_agent));
					self::$mobileStatus = 'windows';
					break;
				case (preg_match('/(mini 9.5|vx1000|lge |m800|e860|u940|ux840|compal|wireless| mobi|ahong|lg380|lgku|lgu900|lg210|lg47|lg920|lg840|lg370|sam-r|mg50|s55|g83|t66|vx400|mk99|d615|d763|el370|sl900|mp500|samu3|samu4|vx10|xda_|samu5|samu6|samu7|samu9|a615|b832|m881|s920|n210|s700|c-810|_h797|mob-x|sk16d|848b|mowser|s580|r800|471x|v120|rim8|c500foma:|160x|x160|480x|x640|t503|w839|i250|sprint|w398samr810|m5252|c7100|mt126|x225|s5330|s820|htil-g1|fly v71|s302|-x113|novarra|k610i|-three|8325rc|8352rc|sanyo|vx54|c888|nx250|n120|mtk |c5588|s710|t880|c5005|i;458x|p404i|s210|c5100|teleca|s940|c500|s590|foma|samsu|vx8|vx9|a1000|_mms|myx|a700|gu1100|bc831|e300|ems100|me701|me702m-three|sd588|s800|8325rc|ac831|mw200|brew |d88|htc\/|htc_touch|355x|m50|km100|d736|p-9521|telco|sl74|ktouch|m4u\/|me702|8325rc|kddi|phone|lg |sonyericsson|samsung|240x|x320|vx10|nokia|sony cmd|motorola|up.browser|up.link|mmp|symbian|smartphone|midp|wap|vodafone|o2|pocket|kindle|mobile|psp|treo)/i',$user_agent));
				case ((strpos($accept,'text/vnd.wap.wml')>0)||(strpos($accept,'application/vnd.wap.xhtml+xml')>0));
				case (isset($_SERVER['HTTP_X_WAP_PROFILE'])||isset($_SERVER['HTTP_PROFILE']));
				case (in_array(strtolower(substr($user_agent,0,4)),array('1207'=>'1207','3gso'=>'3gso','4thp'=>'4thp','501i'=>'501i','502i'=>'502i','503i'=>'503i','504i'=>'504i','505i'=>'505i','506i'=>'506i','6310'=>'6310','6590'=>'6590','770s'=>'770s','802s'=>'802s','a wa'=>'a wa','acer'=>'acer','acs-'=>'acs-','airn'=>'airn','alav'=>'alav','asus'=>'asus','attw'=>'attw','au-m'=>'au-m','aur '=>'aur ','aus '=>'aus ','abac'=>'abac','acoo'=>'acoo','aiko'=>'aiko','alco'=>'alco','alca'=>'alca','amoi'=>'amoi','anex'=>'anex','anny'=>'anny','anyw'=>'anyw','aptu'=>'aptu','arch'=>'arch','argo'=>'argo','bell'=>'bell','bird'=>'bird','bw-n'=>'bw-n','bw-u'=>'bw-u','beck'=>'beck','benq'=>'benq','bilb'=>'bilb','blac'=>'blac','c55/'=>'c55/','cdm-'=>'cdm-','chtm'=>'chtm','capi'=>'capi','cond'=>'cond','craw'=>'craw','dall'=>'dall','dbte'=>'dbte','dc-s'=>'dc-s','dica'=>'dica','ds-d'=>'ds-d','ds12'=>'ds12','dait'=>'dait','devi'=>'devi','dmob'=>'dmob','doco'=>'doco','dopo'=>'dopo','el49'=>'el49','erk0'=>'erk0','esl8'=>'esl8','ez40'=>'ez40','ez60'=>'ez60','ez70'=>'ez70','ezos'=>'ezos','ezze'=>'ezze','elai'=>'elai','emul'=>'emul','eric'=>'eric','ezwa'=>'ezwa','fake'=>'fake','fly-'=>'fly-','fly_'=>'fly_','g-mo'=>'g-mo','g1 u'=>'g1 u','g560'=>'g560','gf-5'=>'gf-5','grun'=>'grun','gene'=>'gene','go.w'=>'go.w','good'=>'good','grad'=>'grad','hcit'=>'hcit','hd-m'=>'hd-m','hd-p'=>'hd-p','hd-t'=>'hd-t','hei-'=>'hei-','hp i'=>'hp i','hpip'=>'hpip','hs-c'=>'hs-c','htc '=>'htc ','htc-'=>'htc-','htca'=>'htca','htcg'=>'htcg','htcp'=>'htcp','htcs'=>'htcs','htct'=>'htct','htc_'=>'htc_','haie'=>'haie','hita'=>'hita','huaw'=>'huaw','hutc'=>'hutc','i-20'=>'i-20','i-go'=>'i-go','i-ma'=>'i-ma','i230'=>'i230','iac'=>'iac','iac-'=>'iac-','iac/'=>'iac/','ig01'=>'ig01','im1k'=>'im1k','inno'=>'inno','iris'=>'iris','jata'=>'jata','java'=>'java','kddi'=>'kddi','kgt'=>'kgt','kgt/'=>'kgt/','kpt '=>'kpt ','kwc-'=>'kwc-','klon'=>'klon','lexi'=>'lexi','lg g'=>'lg g','lg-a'=>'lg-a','lg-b'=>'lg-b','lg-c'=>'lg-c','lg-d'=>'lg-d','lg-f'=>'lg-f','lg-g'=>'lg-g','lg-k'=>'lg-k','lg-l'=>'lg-l','lg-m'=>'lg-m','lg-o'=>'lg-o','lg-p'=>'lg-p','lg-s'=>'lg-s','lg-t'=>'lg-t','lg-u'=>'lg-u','lg-w'=>'lg-w','lg/k'=>'lg/k','lg/l'=>'lg/l','lg/u'=>'lg/u','lg50'=>'lg50','lg54'=>'lg54','lge-'=>'lge-','lge/'=>'lge/','lynx'=>'lynx','leno'=>'leno','m1-w'=>'m1-w','m3ga'=>'m3ga','m50/'=>'m50/','maui'=>'maui','mc01'=>'mc01','mc21'=>'mc21','mcca'=>'mcca','medi'=>'medi','meri'=>'meri','mio8'=>'mio8','mioa'=>'mioa','mo01'=>'mo01','mo02'=>'mo02','mode'=>'mode','modo'=>'modo','mot '=>'mot ','mot-'=>'mot-','mt50'=>'mt50','mtp1'=>'mtp1','mtv '=>'mtv ','mate'=>'mate','maxo'=>'maxo','merc'=>'merc','mits'=>'mits','mobi'=>'mobi','motv'=>'motv','mozz'=>'mozz','n100'=>'n100','n101'=>'n101','n102'=>'n102','n202'=>'n202','n203'=>'n203','n300'=>'n300','n302'=>'n302','n500'=>'n500','n502'=>'n502','n505'=>'n505','n700'=>'n700','n701'=>'n701','n710'=>'n710','nec-'=>'nec-','nem-'=>'nem-','newg'=>'newg','neon'=>'neon','netf'=>'netf','noki'=>'noki','nzph'=>'nzph','o2 x'=>'o2 x','o2-x'=>'o2-x','opwv'=>'opwv','owg1'=>'owg1','opti'=>'opti','oran'=>'oran','p800'=>'p800','pand'=>'pand','pg-1'=>'pg-1','pg-2'=>'pg-2','pg-3'=>'pg-3','pg-6'=>'pg-6','pg-8'=>'pg-8','pg-c'=>'pg-c','pg13'=>'pg13','phil'=>'phil','pn-2'=>'pn-2','pt-g'=>'pt-g','palm'=>'palm','pana'=>'pana','pire'=>'pire','pock'=>'pock','pose'=>'pose','psio'=>'psio','qa-a'=>'qa-a','qc-2'=>'qc-2','qc-3'=>'qc-3','qc-5'=>'qc-5','qc-7'=>'qc-7','qc07'=>'qc07','qc12'=>'qc12','qc21'=>'qc21','qc32'=>'qc32','qc60'=>'qc60','qci-'=>'qci-','qwap'=>'qwap','qtek'=>'qtek','r380'=>'r380','r600'=>'r600','raks'=>'raks','rim9'=>'rim9','rove'=>'rove','s55/'=>'s55/','sage'=>'sage','sams'=>'sams','sc01'=>'sc01','sch-'=>'sch-','scp-'=>'scp-','sdk/'=>'sdk/','se47'=>'se47','sec-'=>'sec-','sec0'=>'sec0','sec1'=>'sec1','semc'=>'semc','sgh-'=>'sgh-','shar'=>'shar','sie-'=>'sie-','sk-0'=>'sk-0','sl45'=>'sl45','slid'=>'slid','smb3'=>'smb3','smt5'=>'smt5','sp01'=>'sp01','sph-'=>'sph-','spv '=>'spv ','spv-'=>'spv-','sy01'=>'sy01','samm'=>'samm','sany'=>'sany','sava'=>'sava','scoo'=>'scoo','send'=>'send','siem'=>'siem','smar'=>'smar','smit'=>'smit','soft'=>'soft','sony'=>'sony','t-mo'=>'t-mo','t218'=>'t218','t250'=>'t250','t600'=>'t600','t610'=>'t610','t618'=>'t618','tcl-'=>'tcl-','tdg-'=>'tdg-','telm'=>'telm','tim-'=>'tim-','ts70'=>'ts70','tsm-'=>'tsm-','tsm3'=>'tsm3','tsm5'=>'tsm5','tx-9'=>'tx-9','tagt'=>'tagt','talk'=>'talk','teli'=>'teli','topl'=>'topl','hiba'=>'hiba','up.b'=>'up.b','upg1'=>'upg1','utst'=>'utst','v400'=>'v400','v750'=>'v750','veri'=>'veri','vk-v'=>'vk-v','vk40'=>'vk40','vk50'=>'vk50','vk52'=>'vk52','vk53'=>'vk53','vm40'=>'vm40','vx98'=>'vx98','virg'=>'virg','vite'=>'vite','voda'=>'voda','vulc'=>'vulc','w3c '=>'w3c ','w3c-'=>'w3c-','wapj'=>'wapj','wapp'=>'wapp','wapu'=>'wapu','wapm'=>'wapm','wig '=>'wig ','wapi'=>'wapi','wapr'=>'wapr','wapv'=>'wapv','wapy'=>'wapy','wapa'=>'wapa','waps'=>'waps','wapt'=>'wapt','winc'=>'winc','winw'=>'winw','wonu'=>'wonu','x700'=>'x700','xda2'=>'xda2','xdag'=>'xdag','yas-'=>'yas-','your'=>'your','zte-'=>'zte-','zeto'=>'zeto','acs-'=>'acs-','alav'=>'alav','alca'=>'alca','amoi'=>'amoi','aste'=>'aste','audi'=>'audi','avan'=>'avan','benq'=>'benq','bird'=>'bird','blac'=>'blac','blaz'=>'blaz','brew'=>'brew','brvw'=>'brvw','bumb'=>'bumb','ccwa'=>'ccwa','cell'=>'cell','cldc'=>'cldc','cmd-'=>'cmd-','dang'=>'dang','doco'=>'doco','eml2'=>'eml2','eric'=>'eric','fetc'=>'fetc','hipt'=>'hipt','http'=>'http','ibro'=>'ibro','idea'=>'idea','ikom'=>'ikom','inno'=>'inno','ipaq'=>'ipaq','jbro'=>'jbro','jemu'=>'jemu','java'=>'java','jigs'=>'jigs','kddi'=>'kddi','keji'=>'keji','kyoc'=>'kyoc','kyok'=>'kyok','leno'=>'leno','lg-c'=>'lg-c','lg-d'=>'lg-d','lg-g'=>'lg-g','lge-'=>'lge-','libw'=>'libw','m-cr'=>'m-cr','maui'=>'maui','maxo'=>'maxo','midp'=>'midp','mits'=>'mits','mmef'=>'mmef','mobi'=>'mobi','mot-'=>'mot-','moto'=>'moto','mwbp'=>'mwbp','mywa'=>'mywa','nec-'=>'nec-','newt'=>'newt','nok6'=>'nok6','noki'=>'noki','o2im'=>'o2im','opwv'=>'opwv','palm'=>'palm','pana'=>'pana','pant'=>'pant','pdxg'=>'pdxg','phil'=>'phil','play'=>'play','pluc'=>'pluc','port'=>'port','prox'=>'prox','qtek'=>'qtek','qwap'=>'qwap','rozo'=>'rozo','sage'=>'sage','sama'=>'sama','sams'=>'sams','sany'=>'sany','sch-'=>'sch-','sec-'=>'sec-','send'=>'send','seri'=>'seri','sgh-'=>'sgh-','shar'=>'shar','sie-'=>'sie-','siem'=>'siem','smal'=>'smal','smar'=>'smar','sony'=>'sony','sph-'=>'sph-','symb'=>'symb','t-mo'=>'t-mo','teli'=>'teli','tim-'=>'tim-','tosh'=>'tosh','treo'=>'treo','tsm-'=>'tsm-','upg1'=>'upg1','upsi'=>'upsi','vk-v'=>'vk-v','voda'=>'voda','vx52'=>'vx52','vx53'=>'vx53','vx60'=>'vx60','vx61'=>'vx61','vx70'=>'vx70','vx80'=>'vx80','vx81'=>'vx81','vx83'=>'vx83','vx85'=>'vx85','wap-'=>'wap-','wapa'=>'wapa','wapi'=>'wapi','wapp'=>'wapp','wapr'=>'wapr','webc'=>'webc','whit'=>'whit','winw'=>'winw','wmlb'=>'wmlb','xda-'=>'xda-',)));
					self::$mobileStatus = 'other';
					break;
			}
		}

		if (!is_null(self::$mobileStatus)) {
			if (!is_null($test)) {
				if (is_array($test)) {
					$test = array_map('strtolower', $test);
					return in_array(self::$mobileStatus, $test);
				} else {
					return self::$mobileStatus == strtolower($test);
				}
			}
			return true;
		}
		return false;
	}

	/**
	 * Check if the module requested was scaffolded
	 *
	 * @return bool
	 */
	public static function isScaffolded() {
		return self::$scaffold;
	}

	/**
	 * Analyse a request to extract the get information (after the ?)
	 * The $request variable will be modified to remove the get information
	 *
	 * @param string $request
	 * @param bool $affectGet Affect the information found to the $_GET array
	 * @return array
	 */
	public static function extractGet(&$request, $affectGet = false) {
		$req = explode('?', $request);
		$request = $req[0];
		$get = array_key_exists(1, $req) ? $req[1] : null;
		$ret = array();
		if ($get) {
			$tmp = array_filter(explode('&', $get));
			foreach($tmp as $elm) {
				if (strpos($elm, '=')) {
					list($name, $val) = explode('=', $elm);
					$ret[$name] = $val;
					if ($affectGet)
						$_GET[$name] = $val;
				}
			}
		}
		return $ret;
	}

	/**
	 * Analyse a request. If some element are empty, the default is replaced
	 *
	 * @param string $request The requested string
	 * @return array Array with the key lang, module, action, param, text and out
	 */
	public static function analyseRequest($request) {
		$request = self::alias($request);

		$ret = array();

		$out = strtolower(file::getExt($request));
		if ($out && self::isOut($out)) {
			$ret['out'] = $out;
			$request = substr($request, 0, strlen($request) - (strlen($out) + 1));
		}

		$tmp = explode(self::$cfg->sep, $request);
		if (self::isLang($tmp[0]))
			$ret['lang'] = array_shift($tmp);

		if (($t = array_shift($tmp)) && $t != self::$cfg->empty)
			$ret['module'] = $t;

		if (($t = array_shift($tmp)) && $t != self::$cfg->empty)
			$ret['action'] = $t;

		$ret['paramA'] = array();
		if (($t = array_shift($tmp)) && $t != self::$cfg->empty) {
			$ret['param'] = $t;
			$ret['paramA'] = self::parseParam($ret['param']);
		}

		if (($t = array_shift($tmp)) && $t != self::$cfg->empty)
			$ret['text'] = $t;

		return $ret;
	}

	/**
	 * Parse a request to find an alias match
	 *
	 * @param string $request The request to parse
	 * @return string the real request
	 */
	public static function alias($request) {
		if (substr($request, 0, 1) != '/')
			$request = '/'.$request;
		foreach(self::$cfg->alias as $k=>$v) {
			$req = preg_replace('`^'.$k.'$`', $v, $request);
			if ($req != $request)
				return substr($req, 1);
		}
		return substr($request, 1);
	}

	/**
	 * Get the translated
	 *
	 * @param unknown_type $request
	 * @return unknown
	 */
	public static function removeLangOutUrl($request) {
		return preg_replace('`(.*)(\.'.implode('|\.', array_keys(self::$cfg->outCfg)).')`', '$1',
				preg_replace('`(/'.implode('|/', self::avlLang()).')?(/.*)`', '$2', $request));
	}

	/**
	 * Init the module requested
	 */
	private static function initModule() {
		if (!self::$module) {
			self::$module = factory::getModule(self::$uriInfo['module'], array(), self::$scaffold, self::$cfg->allowScaffold);
			if (self::$module instanceof module_scaffold_controller && !self::$cfg->allowScaffold) {
				// Need to test if the action was expressly defined
				$ref = new nReflection();
				$className = 'module_'.self::$uriInfo['module'].'_controller';

				$prefix = null;
				$action = self::$uriInfo['action'];
				if (array_key_exists(NYROENV, self::$module->getCfg()->basicPrefixExec) &&
						in_array($action, self::$module->getCfg()->getInArray('basicPrefixExec', NYROENV)))
					$prefix = ucfirst(NYROENV);
				else if (self::$module->getCfg()->prefixExec && !in_array($action, self::$module->getCfg()->noPrefixExec))
					$prefix = self::$module->getCfg()->prefixExec;

				$exec = 'exec'.$prefix.ucFirst($action);
				if ($ref->rebuild($className)) {
					if ($ref->getMethod($exec)->getDeclaringClass()->name != $className)
						throw new module_exception('Request - initModule: '.self::$uriInfo['module'].'.'.$exec.' not found.');
				}
			}
			if (self::$scaffold) {
				self::$uriInfo['moduleScaffold'] = self::$uriInfo['module'];
				self::$uriInfo['module'] = 'scaffold';
			}
		}
	}

	/**
	 * Get the module requested
	 *
	 * @return module_abstract
	 */
	public static function getModule() {
		self::initModule();
		return self::$module;
	}

	/**
	 * Execute the action requested
	 *
	 * @return mixed Return from the executed action
	 */
	public static function execModule() {
		self::initModule();
		return self::$module->exec(self::$uriInfo);
	}

	/**
	 * Publish the module requested
	 *
	 * @return string The module published
	 */
	public static function publishModule() {
		self::initModule();
		return self::$module->publish();
	}

	/**
	 * Check if it's an available lang
	 *
	 * @param string $lang
	 * @return bool
	 */
	public static function isLang($lang) {
		return in_array($lang, self::avlLang());
	}

	/**
	 * Get the available langs
	 *
	 * @param bool $withName If the language name are needed
	 * @return array
	 */
	public static function avlLang($withName = false) {
		if ($withName)
			return self::$cfg->avlLang;
		return array_keys(self::$cfg->avlLang);
	}

	/**
	 * Check if it's an available out
	 *
	 * @param string $out
	 * @return bool
	 */
	public static function isOut($out) {
		return $out && array_key_exists($out, self::$cfg->outCfg);
	}

	/**
	 * Get the reponse name associate to the request out
	 *
	 * @return string
	 */
	public static function getResponseName() {
		return self::get('out') ? self::$cfg->outCfg[self::get('out')] : 'http';
	}

}
