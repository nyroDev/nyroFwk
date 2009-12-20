<?php
/**
 * @author Cédric Nirousset <cedric@nyrodev.com>
 * @version 0.2
 * @package nyroFwk
 */
/**
 * To retrieve data from environnement
 */
final class http_vars {

	/**
	 * Unique instance
	 *
	 * @var http_vars
	 */
	private static $instance = false;

	/**
	 * No direct instanciation for this class
	 */
	private function __construct() {}

	/**
	 * Get the unique session instance
	 *
	 * @return http_vars The instance
	 */
	public static function getInstance() {
		if (self::$instance === false)
			self::$instance = new http_vars();
		return self::$instance;
	}

	/**
	 * Get a variable
	 *
	 * @param string|array $prm if it's a string, it's the variable name. if it's an array, options avaiable are:
	 *  - string name: Variable name
	 *  - string nameIn: Variable name in array
	 *  - array|string method: Method where to get the variable
	 *     in order of preference (default: array('post','get'))
	 *     available are: get, post, session, files or cookie
	 *  - mixed default: Possibilities array, first values will be the default or default return, if not found
	 *  - bool trim: True if need to automaticly trim the return
	 * @return mixed The found variable or the default value or null
	 */
	public function getVar($prm) {
		if (is_string($prm))
			$p = array('name'=>$prm);
		else
			$p = &$prm;
		$ret = null;
		if (config::initTab($p, array(
					'name'=>null,
					'nameIn'=>'',
					'method'=>array('post','get'),
					'trim'=>true
				))) {
			$matches = explode('|', str_replace(
				array('[]', '][', '[', ']'),
				array('', '|', '|', ''),
				$p['name']
			));
			$name = empty($matches)? array($p['name']): $matches;
			if (is_array($p['method'])) {
				for($i = 0; $i < count($p['method']) && $ret === null; $i++) {
					$act = '_'.strtoupper($p['method'][$i]);
					$ret = utils::getValInArray($GLOBALS[$act], $name);
				}
			} else {
				$act = '_'.strtoupper($p['method']);
				$ret = utils::getValInArray($GLOBALS[$act], $name);
			}
			if ($p['trim'] && !is_null($ret) && !is_array($ret))
				$ret = trim($ret);
		}
		$prm = array_merge(array('default'=>null), $p);
		if (is_array($prm['default'])) {
			if ($ret === null || !in_array($ret, $prm['default'])) {
				$ret = $prm['default'][0];
			}
		} else if ($ret === null)
			$ret = $prm['default'];

		$ret = utils::htmlIn($ret);
		if (is_array($ret) && !empty($p['nameIn']))
			return array_key_exists($p['nameIn'], $ret)? $ret[$p['nameIn']] : null;
		else
			return $ret;
	}

	/**
	 * Get a POST variable
	 *
	 * @param string $name Variable Name
	 * @param mixed default: Possibilities array, first values will be the default or default return, if not found
	 * @return mixed The found variable or the default value or null
	 * @see getVar
	 */
	public function post($name, $default=null) {
		return $this->getVar(array(
			'name'=>$name,
			'method'=>'post',
			'default'=>$default));
	}

	/**
	 * Get all type variable of one type
	 *
	 * @param String $method Method name (get or post)
	 * @return array
	 */
	public function getVars($method='post') {
		$act = '_'.strtoupper($method);
		return utils::htmlIn($GLOBALS[$act]);
	}

	/**
	 * Get all post variables
	 *
	 * @return array
	 * @see getVars
	 */
	public function posts() {
		return $this->getvars('post');
	}

	/**
	 * Get all get variables
	 *
	 * @return array
	 * @see getVars
	 */
	public function gets() {
		return $this->getvars('get');
	}

	/**
	 * Get a GET variable
	 *
	 * @param string $name Variable Name
	 * @param mixed default: Possibilities array, first values will be the default or default return, if not found
	 * @return mixed The found variable or the default value or null
	 * @see getVar
	 */
	public function get($name, $default=null) {
		return $this->getVar(array(
			'name'=>$name,
			'method'=>'get',
			'default'=>$default));
	}

	/**
	 * Get a SESSION variable
	 *
	 * @param string $name Variable Name
	 * @param mixed default: Possibilities array, first values will be the default or default return, if not found
	 * @return mixed The found variable or the default value or null
	 * @see getVar
	 */
	public function session($name, $default=null) {
		return $this->getVar(array(
			'name'=>$name,
			'method'=>'session',
			'default'=>$default));
	}

	/**
	 * Get a COOKIE variable
	 *
	 * @param string $name Variable Name
	 * @param mixed default: Possibilities array, first values will be the default or default return, if not found
	 * @return mixed The found variable or the default value or null
	 * @see getVar
	 */
	public function cookie($name, $default=null) {
		return $this->getVar(array(
			'name'=>$name,
			'method'=>'cookie',
			'default'=>$default));
	}

}
