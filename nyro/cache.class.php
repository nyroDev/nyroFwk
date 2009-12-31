<?php
/**
 * @author Cédric Nirousset <cedric@nyrodev.com>
 * @version 0.2
 * @package nyro
 */
/**
 * Final class for cache
 */
final class cache {

	/**
	 * Cache configuration
	 *
	 * @var config
	 */
	private static $cfg;

	/**
	 * No instanciation for this class
	 */
	private function __construct() {}

	/**
	 * Get the unique cache instance
	 *
	 * @param array $cfg Config for the cache instance
	 * @return cache_abstract The instance
	 */
	public static function getInstance(array $cfg = array()) {
		if (!self::$cfg)
			self::$cfg = new config(factory::loadCfg(__CLASS__));
		return factory::get('cache_'.self::$cfg->use, $cfg);
	}

	/**
	 * Create an id depending on the request and options
	 * get, post, session or cookie
	 *
	 * @param array $prm Configuration array with keys:
	 * - boolean uri Indicate if the uri should be used (default true)
	 * - array meth Which method used for creating the id (default array('get','post','session'))
	 * @return string The id
	 */
	public static function idRequest(array $prm = array()) {
		$tmp = '';

		config::initTab($prm, array(
			'uri'=>true,
			'meth'=>array('get','post','session')
		));

		if ($prm['uri'] && !empty($_SERVER['REQUEST_URI']))
			$tmp.= $_SERVER['REQUEST_URI'];

		ksort($prm['meth']);
		foreach($prm['meth'] as $m) {
			$vars = &$GLOBALS['_'.strtoupper($m)];
			if (!empty($vars)) {
				ksort($vars);
				$tmp.= '@'.$m.'=';
				foreach($vars as $k=>$v)
					$tmp.= $k.':'.$v.'&';
			}
		}

		if (!empty($tmp))
			return sha1($tmp);
		else
			return '';
	}

}
