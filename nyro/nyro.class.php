<?php
/**
 * @author Cedric Nirousset <cedric@nyrodev.com>
 * @version 0.2
 * @package nyro
 */
/**
 * It's the website main
 * Singleton
 */
final class nyro {

	/**
	 * Nyro configuration
	 *
	 * @var config
	 */
	private static $cfg;

	/**
	 * No instanciation for this class
	 */
	private function __construct() {}

	/**
	 * Initialize nyro
	 */
	private static function init() {
		if (!self::$cfg) {
			factory::init();
			if (DEV) {
				debug::timer('nyro');
				debug::timer('nyroProcess');
			}
			request::init();
			self::$cfg = new config(factory::loadCfg(__CLASS__));
			session::initFlash();
		}
	}

	/**
	 * Website main
	 */
	public static function main() {

		define('NYROVERSION', '0.2');

		try {
			self::init();

			$resp = response::getInstance();
			self::$cfg->overload(__CLASS__.'Response');

			request::execModule();

			if (DEV) {
				debug::timer('nyroProcess');
				debug::timer('nyroRender');
			}

			$resp->setContent(request::publishModule());
		} catch (module_exception $e) {
			session::setFlash('nyroError', 'MODULE or ACTION NOT FOUND<br />'.self::handleError($e));
			$resp->error(null, 404);
		} catch (nException $e) {
			session::setFlash('nyroError', self::handleError($e));
			$resp->error(null, 500);
		} catch (PDOException $e) {
			session::setFlash('nyroError', self::handleError($e));
			$resp->error(null, 500);
		} catch (Exception $e) {
			session::setFlash('nyroError', self::handleError($e));
			$resp->error(null, 500);
		}

		try {
			factory::saveCache();

			echo $resp->send();
		} catch (Exception $e) {
			echo debug::trace($e);
		}
	}
	
	public static function getGlobalCfg($name) {
		if (self::$cfg && self::$cfg->check($name))
			return self::$cfg->get($name);
		return array();
	}

	/**
	 * Get the config object
	 *
	 * @return config
	 */
	public static function getCfg() {
		return self::$cfg;
	}

	private static function handleError(Exception $err) {
		echo $err; exit;
		debug::trace($err, 2);
		return debug::trace($err);
	}

}
