<?php
/**
 * @author Cedric Nirousset <cedric@nyrodev.com>
 * @version 0.2
 * @package nyro
 */
/**
 * Initialize some constant juste before they're created by default
 */

define('NYRONAME', 'nyroApp');
if (array_key_exists('windir', $_SERVER) || $_SERVER['SERVER_NAME'] == 'localhost') {
	define('NYROROOT', 'D:\www\nyroFwk\trunk\nyro\\');
	define('DEV', true);
} else {
	define('NYROROOT', '/home/var/nyroFwk/nyro/');
	define('DEV', false);
}

require((defined('NYROROOT') ? NYROROOT : 'nyro/').'start.inc.php');