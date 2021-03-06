<?php
/**
 * @author Cédric Nirousset <cedric@nyrodev.com>
 * @version 0.2
 * @package nyroFwk
 */
/**
 * To read and write files
 * Same use like globals variables
 * Singleton
 */
final class file {

	/**
	 * File configuration
	 *
	 * @var config
	 */
	private static $cfg;

	/**
	 * Checkif a file exists
	 *
	 * @param string $file The file path
	 * @return bool
	 */
	private static $searchFiles = array();

	/**
	 * Indicate if the file path cache should be saved
	 *
	 * @var bool
	 */
	private static $saveCacheFiles = false;

	/**
	 * Cache object for nyroExists
	 *
	 * @var cache_abstract
	 */
	private static $cacheFiles = null;

	/**
	 * No instanciation for this class
	 */
	private function __construct() {}

	/**
	 * Init the file elements
	 */
	public static function init() {
		self::initCache();
	}

	/**
	 * Init the file configuration
	 */
	private static function initCfg() {
		if (!self::$cfg)
			self::$cfg = new config(factory::loadCfg(__CLASS__));
	}

	/**
	 * Initialize the cache object
	 */
	private static function initCache() {
		self::$saveCacheFiles = false;
		self::$cacheFiles = cache::getInstance();
		$tmp = self::$searchFiles;
		self::$searchFiles = array();
		self::$cacheFiles->get(self::$searchFiles, array(
			'ttl'=>0,
			'id'=>'nyroExists',
			'request'=>array('uri'=>false,'meth'=>array()),
			'serialize'=>true,
		));
		self::$searchFiles = array_merge($tmp, self::$searchFiles);
		self::$saveCacheFiles = true;
	}

	/**
	 * Save the cache
	 */
	public static function saveCache() {
		if (!self::$saveCacheFiles) {
			self::$cacheFiles->save();
			self::$saveCacheFiles = true;
		}
	}

	/**
	 * Check if a file exists
	 *
	 * @param string $file File path
	 * @return bool
	 */
	public static function exists($file) {
		/*
		if (empty(self::$searchFiles)) {
			$search = explode(',', SEARCHROOT);
			foreach($search as $k=>$d) {
				foreach( new RegexFindFile($d, '`(.*)`') as $f) {
					self::$searchFiles[] = $f->getPathname();
				}
			}
		}
		return in_array($file, self::$searchFiles);
		*/
		return file_exists($file);
	}

	/**
	 * Get the filename from a path
	 *
	 * @param string $file
	 * @return string
	 */
	public static function name($file) {
		return basename($file);
	}

	/**
	 * Try to find the file location, in the nyro installation in this order:
	 * my directory, plugin directory and nyro directory.
	 * The order can be reverse by setting rtl parameter to false.
	 * If the file is located on a subdirectory, use _ to replace /.
	 *
	 * @param array $prm Possible values :
	 *  - name (required) string: FileName (with _ to replace /)
	 *  - realName boolean: Indicate if the given name is real or should be parsed
	 *  - type string: class, extend, cfg, tpl, lib or other (default: class)
	 *  - rtl bool: see description (default: true)
	 *  - list bool: Search all the matched files and return the order list. (default: false)
	 *  - tplExt string: Tpl Extension (default: request::get('out'))
	 * @return false|string|array The absolute file location, an absolute file location array or false if not found
	 */
	public static function nyroExists($prm) {
		if (!config::initTab($prm, array(
					'name'=>null,
					'realName'=>false,
					'type'=>'class',
					'rtl'=>true,
					'list'=>false,
					'tplExt'=>''
				)))
			throw new nException('File - nyroExists : name to search is empty.');

		$cacheKey = implode(':', array_filter($prm, 'is_string'));
		if (!isset(self::$searchFiles[$cacheKey])) {
			$dir = explode(',', SEARCHROOT);

			$nameTmp = $prm['realName'] ? $prm['name'] : str_replace('_', DS, $prm['name']);

			$name = array();
			if ($prm['type'] == 'cfg') {
				$ext = 'cfg';
				$out = request::get('out');
				$name[] = $nameTmp.'.'.$ext.'.'.EXTPHP;
				$name[] = $nameTmp.'.'.$out.'.'.$ext.'.'.EXTPHP;
				$name[] = $nameTmp.'.'.request::get('lang').'.'.$ext.'.'.EXTPHP;
				$name[] = $nameTmp.'.'.request::get('lang').'.'.$out.'.'.$ext.'.'.EXTPHP;
				$name[] = $nameTmp.'.'.NYROENV.'.'.$ext.'.'.EXTPHP;
				$name[] = $nameTmp.'.'.NYROENV.'.'.$out.'.'.$ext.'.'.EXTPHP;
				$name[] = $nameTmp.'.'.NYROENV.'.'.request::get('lang').'.'.$ext.'.'.EXTPHP;
				$name[] = $nameTmp.'.'.NYROENV.'.'.request::get('lang').'.'.$out.'.'.$ext.'.'.EXTPHP;
			} else if ($prm['type'] == 'tpl') {
				$ext = $prm['tplExt'] ? $prm['tplExt'] : request::get('out');
				$name[] = $nameTmp.'.'.NYROENV.'.'.request::get('lang').'.'.$ext.'.'.EXTPHP;
				$name[] = $nameTmp.'.'.NYROENV.'.'.request::get('lang').'.'.$ext;
				$name[] = $nameTmp.'.'.NYROENV.'.'.request::get('lang').'.'.EXTPHP;
				$name[] = $nameTmp.'.'.request::get('lang').'.'.$ext.'.'.EXTPHP;
				$name[] = $nameTmp.'.'.request::get('lang').'.'.$ext;
				$name[] = $nameTmp.'.'.request::get('lang').'.'.EXTPHP;
				$name[] = $nameTmp.'.'.NYROENV.'.'.$ext.'.'.EXTPHP;
				$name[] = $nameTmp.'.'.NYROENV.'.'.$ext;
				$name[] = $nameTmp.'.'.NYROENV.'.'.EXTPHP;
				$name[] = $nameTmp.'.'.$ext.'.'.EXTPHP;
				$name[] = $nameTmp.'.'.$ext;
				$name[] = $nameTmp.'.'.EXTPHP;
			} else if ($prm['type'] == 'other')
				$name[] = $nameTmp;
			else
				$name[] = $nameTmp.'.'.$prm['type'].'.'.EXTPHP;

			if (!$prm['rtl'])
				$dir = array_reverse($dir);

			if ($prm['list'])
				$ret = array();

			/*
			array_walk($dir, create_function('&$v', '$v = substr($v, strlen(ROOT));'));
			$regex = str_replace('\\', '\\\\', '`('.implode('|', $dir).')('.implode('|', $name).')`');
			foreach(new RegexFindFile(ROOT, $regex) as $file) {
				if ($prm['list'])
					$ret[] = $file->getPathname();
				else {
					stEnd();
					return $file->getPathname();
				}
			}
			// */
			//*
			foreach($dir as &$d) {
				foreach($name as &$n) {
					if (self::exists($file = $d.$n)) {
						if ($prm['list'])
							$ret[] = $file;
						else if (!isset(self::$searchFiles[$cacheKey]))
							self::$searchFiles[$cacheKey] = $file;
					}
				}
				reset($name);
			}
			// */

			if ($prm['list'])
				self::$searchFiles[$cacheKey] = $ret;
			else if (!isset(self::$searchFiles[$cacheKey]))
				self::$searchFiles[$cacheKey] = false;
			self::$saveCacheFiles = false;
		}
		return self::$searchFiles[$cacheKey];
	}

	/**
	 * Check if a file exists in the web directory
	 *
	 * @param string $file Filename
	 * @return bool
	 */
	public static function webExists($file) {
		return self::exists(WEBROOT.DS.$file);
	}

	/**
	 * Read a file
	 *
	 * @param string $file The file path
	 * @return string|false The file content
	 */
	public static function read($file) {
		if (self::exists($file))
			return file_get_contents($file);
		else
			return false;
	}

	/**
	 * Write into a file
	 *
	 * @param string $file The file path
	 * @param string $content The file content
	 * @return bool True if success
	 */
	public static function write($file, $content) {
		self::createDir(pathinfo($file, PATHINFO_DIRNAME));
		return (file_put_contents($file, $content) === self::size($file));
	}

	/**
	 * Copy a file to a new location
	 *
	 * @param string $oldName Old name path
	 * @param string $newName New name path
	 * @return bool True if success
	 */
	public static function copy($oldName, $newName) {
		return copy($oldName, $newName);
	}

	/**
	 * Move a file to a new location
	 *
	 * @param string $oldName Old name path
	 * @param string $newName New name path
	 * @return bool True if success
	 */
	public static function move($oldName, $newName) {
		return rename($oldName, $newName);
	}

	/**
	 * Create a directory, and all subdirectory if needed
	 * If directory already exists, nothing is done
	 *
	 * @param string $path
	 * @param string $chmod
	 * @return bool True if directory exists
	 */
	public static function createDir($path, $chmod=0777) {
		umask(0002);
		return is_dir($path) || mkdir($path, $chmod, true);
	}

	/**
	 * Get the file update date
	 *
	 * @param string $file The file path
	 * @return int Timestamp
	 */
	public static function date($file) {
		clearstatcache();
		return filemtime($file);
	}

	/**
	 * Compare 2 date files
	 *
	 * @param string $file1 The first file path
	 * @param string $file2 The second file path
	 * @return bool True if the first file is later than the second
	 */
	public static function isLater($file1, $file2) {
		clearstatcache();
		return (filemtime($file1) > filemtime($file2));
	}

	/**
	 * Get a file size
	 *
	 * @param string $file The file path
	 * @return int The file size
	 */
	public static function size($file) {
		clearstatcache();
		if (self::exists($file))
			return filesize($file);
		return 0;
	}
	
	/**
	 * Get a human file size
	 *
	 * @param string $file The file path
	 * @param boolean $sizeGiven Indicates if the size is given in firt param
	 * @return string The human size
	 */
	public static function humanSize($file, $sizeGiven = false) {
		$size = $sizeGiven ? $file : self::size($file);
		$mod = 1024;
		$units = explode(' ', 'B KB MB GB TB PB');
		for ($i = 0; $size > $mod; $i++) {
			$size /= $mod;
		}
		return round($size, 2).' '.$units[$i];
	}

	/**
	 * Delete a file
	 *
	 * @param string $file The file path
	 * @return bool True if success
	 */
	public static function delete($file) {
		if (self::exists($file)) {
			if (is_dir($file)) {
				self::multipleDelete($file.'/*');
				@rmdir($file);
			} else {
				@unlink($file);
				clearstatcache();
				if (self::exists($file)) {
					$filesys = str_replace("/", "\\", $file);
					@system("del $filesys");
					clearstatcache();
					if (self::exists($file)) {
						@chmod($file, 0775);
						@unlink($file);
						@system("del $filesys");
					}
				}
			}
			clearstatcache();
		}
		return !self::exists($file);
	}

	/**
	 * Delete files with a pattern
	 *
	 * @param string $pattern The pattern to delete files (glob used)
	 * @param string $matchPattern A more specific pattern to be applied with preg_match
	 * @return int Number of files deleted
	 */
	public static function multipleDelete($pattern, $matchPattern = null) {
		$nb = 0;
		foreach(self::search($pattern, $matchPattern) as $f)
			if (self::delete($f))
				$nb++;
		return $nb;
	}

	/**
	 * Get the file extension
	 *
	 * @param string $file The filename
	 * @return null|string The extension
	 */
	public static function getExt($file) {
		$ext = pathinfo($file, PATHINFO_EXTENSION);
		if (!$ext)
			$ext = substr($file, strrpos($file, '.') + 1);
		return $ext;
	}

	/**
	 * Get the Mime Type of a file
	 *
	 * @param string $file File path name
	 * @return string
	 */
	public static function getType($file) {
		self::initCfg();
		$ret = self::$cfg->getInArray('mimes', strtolower(self::getExt($file)));
		if (!$ret)
			$ret = self::$cfg->getInArray('mimes', 'unknown');
		return $ret;

		$ret = false;

		if (self::exists($file)) {
			$finfo = new finfo(FILEINFO_MIME);
			if ($finfo) {
				$ret = $finfo->file($filename);
				$finfo->close();
			} else
				$ret = mime_content_type($file);
		}

		return $ret;
	}

	/**
	 * Fetch a file with vars (used in tpl)
	 *
	 * @param string $file File path name
	 * @param array $vars Variables used in the php file
	 * @return string The file fetched
	 */
	public static function fetch($file, array $vars = array()) {
		extract($vars, EXTR_REFS OR EXTR_OVERWRITE);
		ob_start();
		include($file);
		$contents = ob_get_contents();
		ob_end_clean();
		return $contents;
	}

	/**
	 * Search files regarding a pattern
	 *
	 * @param string $pattern
	 * @param string $matchPattern A more specific pattern to be applied with preg_match
	 * @return array
	 */
	public static function search($pattern, $matchPattern = null) {
		$ret = glob($pattern);
		if (!is_null($matchPattern)) {
			$tmp = array();
			foreach($ret as $r) {
				if (preg_match($matchPattern, $r))
					$tmp[] = $r;
			}
			$ret = $tmp;
		}
		return $ret;
	}

}
