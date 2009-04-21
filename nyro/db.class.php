<?php
/**
 * @author Cedric Nirousset <cedric@nyrodev.com>
 * @version 0.2
 * @package nyro
 */
/**
 * factory class for all dbo sources
 */
final class db {

	const FETCH_ASSOC = PDO::FETCH_ASSOC;
	const FETCH_BOTH = PDO::FETCH_BOTH;
	const FETCH_NUM = PDO::FETCH_NUM;
	const FETCH_ROWSET = PDO::FETCH_CLASS;
	const FETCH_COLUMN = PDO::FETCH_COLUMN;

	/**
	 * db insrances
	 *
	 * @var array
	 */
	private static $instances = array();

	/**
	 * Db configuration
	 *
	 * @var config
	 */
	private static $cfg = null;

	/**
	 * Tables object cache
	 *
	 * @var array
	 */
	private static $tables = array();

	/**
	 * Queries log
	 *
	 * @var array
	 */
	private static $log = array();

	/**
	 * No instanciation for this class
	 */
	private function __construct() {}

	/**
	 * Get db instance
	 *
	 * @param string|array $cfg String if use a configuration (and retrieve a singleton)
	 * or array to have an unique connection. Array must contain key use to specify the type to use
	 * @return db_abstract The instance requested
	 */
	public static function getInstance($cfg=null) {
		if (is_array($cfg)) {
			$cfg['getInstanceCfg'] = $cfg;
			return factory::get('db_'.$cfg['use'], $cfg);
		}

		self::init();

        if (is_null($cfg))
            $cfg = self::$cfg->get('defCfg');

		if (!array_key_exists($cfg, self::$instances)) {
			$tmp = self::$cfg->get($cfg);
			$tmp['getInstanceCfg'] = $cfg;
			$use = $tmp['use'];
			unset($tmp['use']);
			self::$instances[$cfg] = factory::get('db_'.$use, $tmp);
		}
		return self::$instances[$cfg];
	}

	/**
	 * Get a db object
	 *
	 * @param string $type Element type (table, rowset or row)
	 * @param db_table|string $table
	 * @param array $prm Array parameter for the factory
	 * @return db_table|db_rowset|db_row
	 */
	public static function get($type, $table, array $prm = array()) {
		if ($type == 'table' && array_key_exists($table, self::$tables))
			return self::$tables[$table];

		self::init();

		if ($table instanceof db_table) {
			$tableName = $table->getName();
			if (!array_key_exists('table', $prm))
				$prm['table'] = $table;
		} else {
			$tableName = $table;
			if ($type == 'table' && !array_key_exists('name', $prm))
				$prm['name'] = $table;
			else if ($type == 'row' && !array_key_exists('table', $prm)) {
				$db = array_key_exists('db', $prm)? $prm['db'] : self::getInstance();
				$prm['table'] = self::get('table', $tableName, array('db'=>$db));
			}
		}

		if (!($className = self::$cfg->getInArray($type, $tableName)) &&
			(!factory::isCreable($className = 'db_'.$type.'_'.$tableName)))
			$className = self::$cfg->get($type.'Class');

		if ($type == 'table') {
			self::$tables[$table] = factory::get($className, $prm);
			return self::$tables[$table];
		}

		return factory::get($className, $prm);
	}

	/**
	 * Get a db configuration value
	 *
	 * @param string $key Ket value
	 * @return mixed
	 */
	public static function getCfg($key) {
		self::$cfg;
		return self::$cfg->get($key);
	}

	/**
	 * indicate if a field is i18n
	 *
	 * @param string $name Field name to test
	 * @return bool
	 */
	public static function isI18nName($field) {
		return substr($field, 0, strlen(self::getCfg('i18n'))) == self::getCfg('i18n');
	}

	/**
	 * Remove the i18n indicator part of a field (if presente
	 *
	 * @param string $field
	 * @return string
	 */
	public static function unI18nName($field) {
		return self::isI18nName($field)? substr($field, strlen(self::getCfg('i18n'))) : $field;
	}

	/**
	 * Add a new query log or get the whole array of log
	 *
	 * @param string|null $sql The query or null to get the whole log
	 * @param array|null $bind Values Binded or null
	 * @return void|array Array if $sql is null
	 */
	public static function log($sql=null, $bind=null) {
		if (is_null($sql))
			return self::$log;
		else if (empty($bind))
			self::$log[] = $sql;
		else
			self::$log[] = array($sql, $bind);
	}

	/**
	 * Init the config object
	 */
	protected static function init() {
		if (self::$cfg == null)
			self::$cfg = new config(factory::loadCfg(__CLASS__));
	}

}
