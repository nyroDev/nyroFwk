<?php
/**
 * @author Cedric Nirousset <cedric@nyrodev.com>
 * @version 0.2
 * @package nyro
 */
/**
 * Manage session variable
 */
final class session {

	/**
	 * Session configuration
	 *
	 * @var config
	 */
	private static $cfg;

	/**
	 * Flash session instance
	 *
	 * @var session_abstract
	 */
	private static $flash;

	/**
	 * Variables flashed in the last page
	 *
	 * @var array
	 */
	private static $flashVars;

	/**
	 * No instanciation for this class
	 */
	private function __construct() {}

	/**
	 * Get the unique session instance
	 *
	 * @param array $cfg Configuration array for the session
	 * @return session_abstract The instance
	 */
	public static function getInstance(array $cfg = array()) {
		self::initCfg();
		return factory::get('session_'.self::$cfg->use, $cfg);
	}

	/**
	 * Init the session configuration
	 */
	private static function initCfg() {
		if (!self::$cfg)
			self::$cfg = new config(factory::loadCfg(__CLASS__));
	}


	/**
	 * Init the flash instance
	 */
	public static function initFlash() {
		if (self::$flash)
			return;

		self::$flash = self::getInstance(array(
			'prefix'=>'flash',
			'nameSpace'=>'flash',
		));

		self::$flashVars = self::$flash->getAll();
		self::$flash->clear();
	}

	/**
	 * Check if a flash var exists
	 *
	 * @param string $name Flash var name
	 * @return bool
	 */
	public static function hasFlash($name) {
		self::initFlash();
		return array_key_exists($name, self::$flashVars);
	}

	/**
	 * Get a Flash var
	 *
	 * @param string $name Flash var name
	 * @return mixed
	 */
	public static function getFlash($name) {
		self::initFlash();
		return self::hasFlash($name)? self::$flashVars[$name] : null;
	}

	/**
	 * Set a Flash var
	 *
	 * @param array|string $name Array to set multiple vars or string for the Flash var name
	 * @param mixed $val The value if set a single var
	 */
	public static function setFlash($name, $val=null) {
		self::initFlash();
		if (is_array($name)) {
			foreach($name as $k=>$v)
				self::setFlash($k, $v);
		} else {
			self::$flash->set(array(
				'name'=>$name,
				'val'=>$val
			));
		}
	}
}
