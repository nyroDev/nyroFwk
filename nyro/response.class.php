<?php
/**
 * @author Cedric Nirousset <cedric@nyrodev.com>
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

	/**
	 * Get the response object according to the requested out
	 *
	 * @return response_abstract
	 */
	public static function getInstance() {
		if (!self::$inst) {
			self::$inst = factory::get('response_'.request::getResponseName());
			self::$inst->setContentType(request::get('out'));
		}
		return self::$inst;
	}
}