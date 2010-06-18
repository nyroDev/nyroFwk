<?php
/**
 * @author Cédric Nirousset <cedric@nyrodev.com>
 * @version 0.2
 * @package nyroFwk
 */
/**
 * Get translation
 */
final class tr {

	/**
	 * Session configuration
	 *
	 * @var config
	 */
	private static $cfg;

	/**
	 * No instanciation for this class
	 */
	private function __construct() {}

	/**
	 * Init the session configuration
	 */
	private static function initCfg() {
		if (!self::$cfg)
			self::$cfg = new config(factory::loadCfg(__CLASS__));
	}

	/**
	 * Get the translation for a keyword
	 *
	 * @param string $key The keyword
	 * @param bool $show Indicate if the word found should be directly shown
	 * @param bool $out Indicate if the word should be outted
	 * @return null|string
	 */
	public static function __($key, $show=false, $out=true) {
		self::initCfg();
		$ret = null;
		if (strpos($key, '_') !== false) {
			$keys = explode('_', $key);
			$ret = self::$cfg->getInArray($keys[0], $keys[1]);
		} else
			$ret = self::$cfg->get($key);
		if ($out)
			$ret = utils::htmlOut($ret);
		$ret = nl2br($ret);
		if ($show)
			echo $ret;
		else
			return $ret;
	}
}