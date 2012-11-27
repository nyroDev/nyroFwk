<?php
/**
 * @author Cédric Nirousset <cedric@nyrodev.com>
 * @version 0.2
 * @package nyroFwk
 */
/**
 * Initialize some constant if they doesn't exists
 */

/**
 * string Installation name
 */
if (!defined('NYRONAME'))
	define('NYRONAME', 'nyro');

/**
 * string Environnement
 */
if (!defined('NYROENV'))
	define('NYROENV', 'front');

/**
 * Enabling the dev mode
 */
if (!defined('DEV'))
	define('DEV', isset($_SERVER['SERVER_NAME']) && $_SERVER['SERVER_NAME'] == 'localhost');

/**
 * string Directory Separator shortcut
 */
if (!defined('REQUIRED'))
	define('REQUIRED', 'REQUIRED');

/**
 * string Directory Separator shortcut
 */
if (!defined('DS'))
	define('DS', DIRECTORY_SEPARATOR);

/**
 * string Php extension
 */
if (!defined('EXTPHP'))
	define('EXTPHP', 'php');

/**
 * string Absolute path to the installation
 */
if (!defined('ROOT'))
	define('ROOT', isset($_SERVER['PWD']) ? dirname($_SERVER['PWD']).DS : dirname(dirname($_SERVER['SCRIPT_FILENAME'])).DS);

/**
 * string Absolute path to the nyro directory
 */
if (!defined('NYROROOT'))
	define('NYROROOT', ROOT.'nyro'.DS);

/**
 * string Absolute path to the my directory
 */
if (!defined('MYROOT'))
	define('MYROOT', ROOT.'my'.DS);

/**
 * string Absolute path to the www directory
 */
if (!defined('WEBROOT'))
	define('WEBROOT', ROOT.'www'.DS);

/**
 * string Absolute path to the files directory
 */
if (!defined('FILESROOT'))
	define('FILESROOT', ROOT.'files'.DS);

/**
 * string Absolute paths where search all the class, from the most user to the nyro
 * separate by ,
 */
if (!defined('SEARCHROOT'))
	define('SEARCHROOT', MYROOT.','.NYROROOT);

/**
 * string Key to be used in the configuration array to indicate that the key should stay unique even with numeric value.
 */
if (!defined('KEEPUNIQUE'))
	define('KEEPUNIQUE', 'keepUnique');

/**
 * string Key to be used in the configuration array to indicate that the value should replace what's in the parent configuration
 */
if (!defined('REPLACECONF'))
	define('REPLACECONF', 'replaceConf');

ini_set('include_path', MYROOT.PATH_SEPARATOR.NYROROOT);

/**
 * string Absolute path to the my directory
 */
if (!defined('TMPROOT'))
	define('TMPROOT', ROOT.'tmp'.DS);

/**
 * string Absolute path to the my directory
 */
if (!defined('URLSEPARATOR'))
	define('URLSEPARATOR', '-');

/**
 * boolean Indicates if the URLs should be lowered (used in utils::urlify)
 */
if (!defined('URLLOWER'))
	define('URLLOWER', false);

/**
 * Load first classes to increase performance
 */
require(NYROROOT.'file.class.'.EXTPHP);
require(NYROROOT.'factory.class.'.EXTPHP);
require(NYROROOT.'config.class.'.EXTPHP);
require(NYROROOT.'autoload.'.EXTPHP);
//*
require(NYROROOT.'nyro.class.'.EXTPHP);
require(NYROROOT.'cache.class.'.EXTPHP);
require(NYROROOT.'nException.class.'.EXTPHP);
require(NYROROOT.'nReflection.class.'.EXTPHP);
require(NYROROOT.'object.class.'.EXTPHP);
require(NYROROOT.'cache'.DS.'abstract.class.'.EXTPHP);
require(NYROROOT.'cache'.DS.'file.class.'.EXTPHP);
require(NYROROOT.'request.class.'.EXTPHP);
require(NYROROOT.'debug.class.'.EXTPHP);
require(NYROROOT.'errorHandler.'.EXTPHP);
//*/