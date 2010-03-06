<?php
/**
 * @author Cédric Nirousset <cedric@nyrodev.com>
 * @version 0.2
 * @package nyro
 */
/**
 * To retrieve the response
 */
final class response {

	/**
	 * The unique response instance
	 *
	 * @var response_abstract
	 */
	private static $inst;

	private static $proxy;

	/**
	 * Get the response object according to the requested out
	 *
	 * @return response_abstract
	 */
	public static function getInstance() {
		if (self::$proxy)
			return self::$proxy;
		if (!self::$inst) {
			self::$inst = factory::get('response_'.request::getResponseName());
			self::$inst->setContentType(request::get('out'));
		}
		return self::$inst;
	}

	/**
	 * Set the proxy response
	 *
	 * @param response_proxy $proxy
	 */
	public static function setProxy(response_proxy $proxy) {
		self::$proxy = $proxy;
	}

	/**
	 * Return the current proxy
	 *
	 * @return response_proxy
	 */
	public static function getProxy() {
		return self::$proxy;
	}

	/**
	 * Clear the proxy response
	 */
	public static function clearProxy() {
		self::$proxy = null;
	}
}