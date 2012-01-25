<?php
/**
 * @author Cédric Nirousset <cedric@nyrodev.com>
 * @version 0.2
 * @package nyroFwk
 */
/**
 * Initialize some constant just before they're created by default
 */

define('NYRONAME', 'nyroApp');
if (array_key_exists('windir', $_SERVER) || $_SERVER['SERVER_NAME'] == 'localhost') {
	define('NYROROOT', 'D:\www\nyroFwk\nyro\\');
	define('DEV', true);
} else {
	define('NYROROOT', '/home/nyrofwk/src/nyro/');
}

require((defined('NYROROOT') ? NYROROOT : 'nyro/').'start.inc.php');