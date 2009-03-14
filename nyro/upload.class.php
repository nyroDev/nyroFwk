<?php
/**
 * Class to deal with uploaded files
 *
 */
final class upload {

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
	 * Initialize the upload instance config
	 */
	private static function init() {
		if (!self::$cfg)
			self::$cfg = new config(factory::loadCfg(__CLASS__));
	}

	/**
	 * Show the file to the client
	 *
	 * @param string $prm File requested
	 */
	public static function get($prm) {
		self::init();
		$prm = self::$cfg->dir.
			str_replace(
				array(self::$cfg->webDir.'/', '/'),
				array('', DS)
				, $prm);
		if (self::$cfg->getInArray('forceDownload', file::getExt($prm)))
			response::getInstance()->sendFile($prm);
		else
			response::getInstance()->showFile($prm);
	}

}