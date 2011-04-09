<?php
/**
 * @author Cédric Nirousset <cedric@nyrodev.com>
 * @version 0.2
 * @package nyroFwk
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
			file::init();
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

	/**
	 * Get a configuration setting
	 *
	 * @param string $name Confirguration key needed
	 * @return array|mixed Array if not found
	 */
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

	/**
	 * Handle an error to be shown to the user
	 *
	 * @param Exception $err
	 * @return string The debug to be shown
	 */
	private static function handleError(Exception $err) {
		return debug::trace($err, DEV? 2 : 0);
	}

}
