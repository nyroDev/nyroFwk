<?php
/**
 * @author Cédric Nirousset <cedric@nyrodev.com>
 * @version 0.2
 * @package nyro
 */
/**
 * Manage security
 * Singleton
 */
final class security {

	/**
	 * Unique instance
	 *
	 * @var security_abstract
	 */
	private static $instance = false;

	/**
	 * security configuration
	 *
	 * @var config
	 */
	private static $cfg;

	/**
	 * No instanciation for this class
	 */
	private function __construct() {}

	/**
	 * Get the unique security instance
	 *
	 * @return security_abstract The instance
	 */
	public static function getInstance() {
		if (self::$instance === false)
			self::init();
		return self::$instance;
	}

	/**
	 * Initialize the security instance parametred
	 */
	private static function init() {
		self::$cfg = new config(factory::loadCfg(__CLASS__));
		self::$instance = factory::get('security_'.self::$cfg->use);
	}

}
