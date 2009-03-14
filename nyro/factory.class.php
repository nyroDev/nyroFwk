<?php
/**
 * @author Cedric Nirousset <cedric@nyrodev.com>
 * @version 0.2
 * @package nyro
 */
/**
 * To create new object, with automaticly configuration
 */
final class factory {

	/**
	 * Factory configuration
	 *
	 * @var config
	 */
	private static $cfg;

	/**
	 * configuration loaded or cached
	 *
	 * @var array
	 */
	private static $loadedCfg = array();

	/**
	 * Class yet loaded
	 *
	 * @var array
	 */
	private static $loadedClass = array();

	/**
	 * Class definition file path loaded or cached
	 *
	 * @var array
	 */
	private static $loadFiles = array();

	/**
	 * Cache object for the configuration
	 *
	 * @var cache_abstract
	 */
	private static $cacheCfg = null;

	/**
	 * Indicate if the configuration cache should be saved
	 *
	 * @var bool
	 */
	private static $saveCacheCfg = false;

	/**
	 * Cache object for the file path
	 *
	 * @var cache_abstract
	 */
	private static $cacheLoad = null;

	/**
	 * Indicate if the file path cache should be saved
	 *
	 * @var bool
	 */
	private static $saveCacheLoad = false;

	/**
	 * Contains the constants
	 *
	 * @var array
	 */
	private static $constants;


	/**
	 * No instanciation for this class
	 */
	private function __construct() {}

	/**
	 * Initialize the factory configuration
	 */
	public static function init() {
		self::$constants = get_defined_constants(true);
		self::initCache();
	}

	/**
	 * Initialize the cache objects
	 */
	public static function initCache() {
		self::$cacheCfg = cache::getInstance();
		self::$cacheCfg->get(self::$loadedCfg, array(
			'ttl'=>0,
			'id'=>'cfg',
			'request'=>array('uri'=>false,'meth'=>array()),
			'serialize'=>true
		));
		self::$saveCacheCfg = false;
		self::$saveCacheLoad = false;
		self::$cacheLoad = cache::getInstance();
		self::$cacheLoad->get(self::$loadFiles, array(
			'ttl'=>0,
			'id'=>'load',
			'request'=>array('uri'=>false,'meth'=>array()),
			'serialize'=>true
		));
	}

	/**
	 * Save the cache
	 */
	public static function saveCache() {
		if (self::$saveCacheCfg)
			self::$cacheCfg->save();

		if (self::$saveCacheLoad)
			self::$cacheLoad->save();
	}

	/**
	 * Return the configuration for a className
	 *
	 * @param string $className ClassName to load its configuration
	 * @param bool $searchParent Indicate if the parent and implements class configuration should be searched
	 * @return array Cfg Array parameter
	 */
	public static function loadCfg($className, $searchParent=true) {
		if (!array_key_exists($className, self::$loadedCfg)) {
			self::$loadedCfg[$className] = array();
			self::$saveCacheCfg = true;

			if ($searchParent) {
				$ref = new nReflection($className);
				// Load the parent class configuration
				if ($parent = $ref->getParentClass())
						self::mergeCfg(self::$loadedCfg[$className], self::loadCfg($parent->getName()));

				// Load the implements class configuration
				if (count($implements = $ref->getInterfaces()) > 0)
					foreach($implements as $imp)
						self::mergeCfg(self::$loadedCfg[$className], self::loadCfg($imp->getName()));
			}

			$listCfg = file::nyroExists(Array(
				'name'=>$className,
				'type'=>'cfg',
				'rtl'=>false,
				'list'=>true
			));

			if (!empty($listCfg))
				foreach($listCfg as $lc) {
					include($lc);
					if ($cfg)
						self::mergeCfg(self::$loadedCfg[$className], $cfg, $className);
					$cfg = null;
				}
			self::removeKeepUnique(self::$loadedCfg[$className]);
		}
		return self::$loadedCfg[$className];
	}

	/**
	 * Merge two cfg arrays
	 *
	 * @param array $prm Initial array
	 * @param array $cfg Array with the parameter to overload
	 */
	private static function mergeCfg(array &$prm, array $cfg) {
		$keepUnique = array_key_exists(KEEPUNIQUE, $prm);
		foreach($cfg as $k=>&$v) {
			if (is_numeric($k) && !$keepUnique) {
				$prm[] = &$v;
			} else if (is_array($v) && array_key_exists($k, $prm) && is_array($prm[$k]))
				self::mergeCfg($prm[$k], $v);
			else
				$prm[$k] = &$v;
		}
		/*
		foreach($cfg as $k=>&$v) {
			if (is_array($v)
					&& array_key_exists($k, $prm)
					&& is_array($prm[$k]))
				$prm[$k] = array_merge($prm[$k], $v);
			else
				$prm[$k] = &$v;
		}
		// */
	}

	private static function removeKeepUnique(array &$prm) {
		unset($prm[KEEPUNIQUE]);
		foreach($prm as &$v) {
			if (is_array($v))
				self::removeKeepUnique($v);
		}
	}

	/**
	 * Get a new object, with loading its definition and configuration
	 *
	 * @param string $className The classname to create
	 * @param array $cfg The config
	 * @return stdClass The new object
	 */
	public static function get($className, array $cfg = array()) {
		self::load($className);

		$ref = new nReflection();
		if ($ref->rebuild($className)) {
			$prm = self::loadCfg($className);
			self::mergeCfg($prm, $cfg);
			$inst = $ref->newInstanceCfg(new config($prm));
			return $inst;
		} else
			throw new nException('Factory - load: Unable to bluid '.$className.'.');
	}

	/**
	 * Get a new helper object, with loading its definition and configuration
	 *
	 * @param string $className The helper to create
	 * @param array $cfg The config
	 * @return stdClass The new object
	 */
	public static function getHelper($className, array $cfg = array()) {
		return self::get('helper_'.$className, $cfg);
	}

	/**
	 * Get a module, with scaffholding if possible
	 *
	 * @param string $name Module name (or table name)
	 * @param array $cfg Configuration array for the module
	 * @return module_abstract The new module
	 * @throws module_exception If module not creable
	 */
	public static function getModule($name, array $cfg=array(), &$scaffold = false) {
		$className = 'module_'.$name.'_controller';
		if (!self::isCreable($className)) {
			if (in_array($name, db::getInstance()->getTables())) {
				$className = 'module_scaffold_controller';
				$cfg['name'] = $name;
				$scaffold = true;
			} else
				throw new module_exception('Factory - getModule: Name '.$name.' unknown (even with scaffolding).');
		}
		return self::get($className, $cfg);
	}

	/**
	 * Load a class definition
	 *
	 * @param string $className The className to load
	 * @return true If success
	 * @throws nExecption If the file isn't find
	 */
	public static function load($className) {
		if (!class_exists($className) && !in_array($className, self::$loadedClass)) {
			if (!array_key_exists($className, self::$loadFiles)) {
				if ($file = file::nyroExists(Array('name'=>$className))) {
					require($file);
					self::$loadFiles[$className] = array($file);
					self::$saveCacheLoad = true;
					self::$loadedClass[] = $className;

					if (defined('RUNKIT_VERSION')) {
						$filesExtend = file::nyroExists(Array(
							'name'=>$className,
							'type'=>'extend',
							'rtl'=>false,
							'list'=>true
						));
						if (!empty($filesExtend)) {
							self::$loadFiles[$className][1] = $filesExtend;
							foreach($filesExtend as $fe)
								runkit_import($fe);
						}
					}
				} else if (!lib::load($className)) {
					throw new nException('Factory - load: Unable to find the file for '.$className.'.');
				}
			} else {
				require(self::$loadFiles[$className][0]);
				self::$loadedClass[] = $className;
				if (defined('RUNKIT_VERSION') && array_key_exists(1, self::$loadFiles[$className])) {
					foreach(self::$loadFiles[$className][1] as $fe)
						runkit_import($fe);
				}
			}
		}
		return true;
	}

	/**
	 * Check if a className is creable (ie if it's file exists)
	 *
	 * @return bool
	 */
	public static function isCreable($className) {
		return (file::nyroExists(array('name'=>$className)) !== false);
	}
}
