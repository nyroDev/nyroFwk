<?php
/**
 * @author Cedric Nirousset <cedric@nyrodev.com>
 * @version 0.2
 * @package nyro
 */
/**
 * To manage the library
 */
final class lib {

	/**
	 * Cache the files path
	 *
	 * @var array
	 */
	private static $libFiles = array();

	/**
	 * No instanciation for this class
	 */
	private function __construct() {}

	/**
	 * Check if a library is accessible
	 * ie: if lib/$name/init.php exists
	 *
	 * @param string $name The library name
	 * @return bool
	 */
	public static function exists($name) {
		return !(self::initFile($name) === false);
	}

	/**
	 * Check if a library is still loaded
	 *
	 * @param string $name The library name
	 * @return bool
	 */
	public static function isLoaded($name) {
		return in_array(self::initFile($name), get_included_files());
	}

	/**
	 * Load a library
	 *
	 * @param string $name The library name
	 * @return bool True if the library is loaded (just now or before)
	 */
	public static function load($name) {
		if (self::isLoaded($name))
			return true;

		if ($file = self::initFile($name))
			return require($file);

		return false;
	}

	/**
	 * Find the init file for a library
	 * ie: nyro/lib/$name/init.lib.php
	 *
	 * @param string $name The library name
	 * @return false|string The file path or false if not found
	 */
	public static function initFile($name) {
		if (!array_key_exists($name, self::$libFiles)) {
			self::$libFiles[$name] = file::nyroExists(array(
				'name'=>'lib_'.$name.'_init',
				'type'=>'lib'
			));
		}
		return self::$libFiles[$name];
	}
}
