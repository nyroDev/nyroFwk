<?php
/**
 * @author Cedric Nirousset <cedric@nyrodev.com>
 * @version 0.2
 * @package nyro
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

		// Update alias to allow translation
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
					$t.='\\'.$i.substr($tt, 1);
					$i++;
				}
				if (!preg_match($outs, $k))
					$alias[$k.'(.*)'] = $t.'\\'.$i;
				$alias[$k] = $t;
			}
		}
		$alias = array_reverse($alias, true);
		self::$cfg->alias = $alias;

		$secure = (array_key_exists('HTTPS', $_SERVER) && $_SERVER['HTTPS'] == 'on');

		$protocol = $secure? 'https' : 'http';

		$controller = substr($_SERVER['SCRIPT_FILENAME'], strlen(WEBROOT));

		$serverName = array_key_exists('SERVER_NAME', $_SERVER) ? $_SERVER['SERVER_NAME'] : null;
		$stdPort = $secure ? '443' : '80';
		$port = ($_SERVER['SERVER_PORT'] != $stdPort && $_SERVER['SERVER_PORT'])? ':'.$_SERVER['SERVER_PORT'] : '';
		$domain = $protocol.'://'.$serverName.$port;

		$requestUri = $_SERVER['REQUEST_URI'];
		$scriptName = $_SERVER['SCRIPT_NAME'];

		$redir = null;
		if (self::$cfg->forceServerName
		    && strpos($serverName, 'localhost') === false
		    && strtolower(self::$cfg->forceServerName) != strtolower($serverName)) {
			$redir = $protocol.'://'.self::$cfg->forceServerName.$port.$requestUri;
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
		if ($redir) {
		    header('HTTP/1.0 301 Moved Permanently');
		    header('Location: '.$redir);
		    exit;
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
		if ($i > 0)
			$path = substr($path, 0, -1);
		if ($path == '//')
			$path = '/';

		$pathWithController = (strpos($requestUri, $controller) !== false);

		if ($pathWithController) {
			if (strpos($path, $controller) !== false)
				$path = substr($path, 0, -strlen($controller)-1).'/';
			$request = substr($requestUri, strlen($path.$controller)+1);
		} else
			$request = substr($requestUri, strlen($path));

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
	public static function createParam($param, $urlify=true) {
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
		if (!self::get('secure')) {
			$uri = str_replace('http://', 'https://', self::uri(array('absolute'=>1)));
			response::getInstance()->redirect($uri);
		}
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
	public static function get($get=null) {
		if ($get == 'uri')
			return self::get('domain').self::getPathControllerUri().self::get('request');
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
	public static function getRequested($get=null) {
		if ($get == null) {
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
	public static function getCfg($key=null) {
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
	public static function getPrm($key, $default=null) {
		$prmA = self::get('paramA');
		return array_key_exists($key, $prmA)? $prmA[$key] : $default;
	}

	/**
	 * Get the URL part between the domain and the request, with the controller name if it's on the current request
	 *
	 * @param bool $forceController Force the Controller script name on the return
	 * @return string
	 */
	public static function getPathControllerUri($forceController=false) {
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
	public static function uri($prm=array()) {
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

		if (array_key_exists('lang', $prm)) {
			if (self::isLang($prm['lang']))
				array_unshift($tmp, $prm['lang']);
		} else if (self::getRequested('lang'))
			array_unshift($tmp, self::getRequested('lang'));
		else if (self::$cfg->lang != self::get('lang'))
			array_unshift($tmp, self::get('lang'));

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
	 * @return string The valid URI
	 */
	public static function uploadedUri($file) {
		return self::uri(array(
					'module'=>'nyroUtils',
					'action'=>'uploadedFiles',
					'param'=>str_replace('/', request::getCfg('sepParam'), $file),
					'out'=>null
				));
	}

	/**
	 * Create an array to create an uri with the requested value by default, overwritten by $prm
	 *
	 * @param array $prm Array to use in the uri
	 * @param array $use Element to use by default in the uri
	 * @return string the uri
	 * @see uri
	 */
	public static function uriDef(array $prm, array $use=array('lang', 'module', 'action', 'param', 'out')) {
		$tmp = array();

		foreach($use as $u) {
			$tmp[$u] = self::getRequested($u);
		}

		return self::uri(array_merge($tmp, $prm));
	}

	/**
	 * Convert a string uri to an array usuable for request::uri
	 *
	 * @param string $uri
	 * @return array
	 */
	public static function uriString($uri) {
		$uriA = array_values(array_filter(explode(self::$cfg->sep, $uri)));

		if (empty($uriA))
			return array();

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

		return $tmp;
	}

	public static function webUri($uri) {
		return self::get('path').$uri;
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
	 * Get the IP address of the user
	 *
	 * @return string
	 */
	public static function getIp() {
		return $_SERVER['REMOTE_ADDR'];
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
	public static function extractGet(&$request, $affectGet=false) {
		$req = explode('?', $request);
		$request = $req[0];
		$get = array_key_exists(1, $req)? $req[1] : null;
		$ret = array();
		if ($get) {
			$tmp = explode('&', $get);
			foreach($tmp as $elm) {
				list($name,$val) = explode('=', $elm);
				$ret[$name] = $val;
				if ($affectGet)
					$_GET[$name] = $val;
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
		return self::$cfg->outCfg[self::get('out')];
	}
}
