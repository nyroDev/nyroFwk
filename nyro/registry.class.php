<?php
/**
 * @author Cédric Nirousset <cedric@nyrodev.com>
 * @version 0.2
 * @package nyroFwk
 */
/**
 * To store data available for other object.
 * Same use like globals variables
 * Singleton
 */
final class registry {

	/**
	 * Variables registred
	 *
	 * @var array
	 * @see get, set
	 */
	private static $vars = array();

	/**
	 * No instanciation for this class
	 */
	private function __construct() {}

	/**
	 * Get the registred variable
	 *
	 * @param string $name Value requested
	 * @return mixed The value requested, false if it doesn't exists
	 */
	public static function get($name) {
		if (self::check($name))
			return self::$vars[$name];
		return null;
	}

	/**
	 * Registred a variable
	 *
	 * @param string $name Name to registred
	 * @param mixed $value Value to registred
	 * @return bool true if sucessful
	 * @throws nException If the $name alreadey exists
	 */
	public static function set($name, $val) {
		if (array_key_exists($name, self::$vars))
			throw new nException('Registry: property '.$name.' already exists.');

		self::$vars[$name] = $val;
		return true;
	}

	/**
	 * Add a string in an array registred variable
	 *
	 * @param string $name Name to registred
	 * @param mixed $value Value to registred
	 * @param bool $unique Indicate if the value must be unique
	 * @return bool true if sucessful
	 */
	public static function setInArray($name, $val, $unique=true) {
		if (!array_key_exists($name, self::$vars))
			self::$vars[$name] = array();

		if (!$unique || !array_key_exists($val, self::$vars[$name]))
			self::$vars[$name][] = $val;
		return true;
	}

	/**
	 * Check if a variable is registred
	 *
	 * @param string $name Value to test
	 * @return bool
	 */
	public static function check($name) {
		return array_key_exists($name, self::$vars);
	}

}
