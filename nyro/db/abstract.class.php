<?php
/**
 * @author Cédric Nirousset <cedric@nyrodev.com>
 * @version 0.2
 * @package nyroFwk
 */
/**
 * Abstract class db classes
 */
abstract class db_abstract extends object {

	/**
	 * Get the configuration parameter used to create this object
	 *
	 * @return string|array
	 */
	public function getInstanceCfg() {
		return $this->cfg->getInstanceCfg;
	}
	

	/**
	 * Get a db object
	 *
	 * @param string $type Element type (table, rowset or row)
	 * @param db_table|string $table
	 * @param array $prm Array parameter for the factory
	 * @param boolean $fromCache Get table from cache or not
	 * @return db_table|db_rowset|db_row
	 */
	public function get($type, $table, array $prm = array(), $fromCache = true) {
		if ($fromCache && $type == 'table' && isset($this->tables[$table]))
			return $this->tables[$table];

		if ($table instanceof db_table) {
			$tableName = $table->getName();
			if (!isset($prm['table']))
				$prm['table'] = $table;
		} else {
			$tableName = $table;
			if ($type == 'table' && !isset($prm['table']))
				$prm['name'] = $table;
			else if ($type == 'row' && !isset($prm['table'])) {
				$prm['table'] = $this->getTable($tableName);
			}
		}

		$prm['db'] = $this;

		if (!($className = $this->cfg->getInArray($type, $tableName)) &&
			(!factory::isCreable($className = 'db_'.$type.'_'.$tableName)))
			$className = $this->cfg->get($type.'Class');

		if ($type == 'table') {
			$tbl = factory::get($className, $prm);
			if (!$fromCache)
				return $tbl;
			$this->tables[$table] = $tbl;
			return $this->tables[$table];
		}

		return factory::get($className, $prm);
	}
	
	/**
	 * Get a table object
	 *
	 * @param string $tableName The table name
	 * @param array $prm The configuration for the where object
	 * @param boolean $fromCache Get table from cache or not
	 * @return db_table
	 */
	public function getTable($tableName, array $prm = array(), $fromCache = true) {
		return $this->get('table', $tableName, $prm, $fromCache);
	}
	
	/**
	 * Get a row object
	 *
	 * @param db_table|string $table
	 * @param array $prm The configuration for the where object
	 * @return db_table
	 */
	public function getRow($table, array $prm = array()) {
		return $this->get('row', $table, $prm);
	}
	
	/**
	 * Get a rowset object
	 *
	 * @param db_table|string $table
	 * @param array $prm The configuration for the where object
	 * @return db_table
	 */
	public function getRowset($table, array $prm = array()) {
		return $this->get('rowset', $table, $prm);
	}

	/**
	 * Get a where object
	 *
	 * @param array $prm The configuration for the where object
	 * @return db_where
	 */
	public function getWhere(array $prm = array()) {
		return factory::get($this->cfg->whereClass, array_merge(array(
			'db'=>$this
		), $prm));
	}

	/**
	 * Get a whereClause object
	 *
	 * @param array $prm The configuration for the where object
	 * @return db_whereClause
	 */
	public function getWhereClause(array $prm = array()) {
		return factory::get($this->cfg->whereClauseClass, array_merge(array(
			'db'=>$this
		), $prm));
	}

	/**
	 * Returns a list of the tables with the parameters provided
	 *
	 * @param array $prm The parameters for the search:
	 *  - string start
	 *  - string contains
	 *  - string end
	 * @return array
	 */
	public function getTablesWith(array $prm) {
		$tmp = array_fill(0, 3, '');

		if (array_key_exists('start', $prm))
			$tmp[0] = $prm['start'];

		if (array_key_exists('contains', $prm))
			$tmp[1] = $prm['contains'];

		if (array_key_exists('end', $prm))
			$tmp[2]= $prm['end'];

		$regex = '/^'.implode('(.*)', $tmp).'$/';

		return array_merge(array_filter($this->getTables(),
			create_function('$val', 'return preg_match("'.$regex.'", $val);')));
	}

	/**
	 * Get a cache instance
	 *
	 * @return cache_abstract
	 */
	public function getCache() {
		return cache::getInstance($this->cfg->cache);
	}
	
	/**
	 * Magic function to allow serialisation
	 *
	 * @return array
	 */
	public function __sleep() {
		return array('cfg');
	}

	/**
	 * Abstract Methods
	 */

    /**
	 * Insert on the database
	 *
	 * @param array $prm The parameter for the insert query :
	 *  - string table (required) : The table to work in
	 *  - array values (required) : The values to insert. The key are the identifier
	 * @return mixed the inserted id
	 */
    abstract public function insert(array $prm);

    /**
	 * Replace on the database
	 *
	 * @param array $prm The parameter for the replace query :
	 *  - string table (required) : The table to work in
	 *  - array values (required) : The values to replace. The key are the identifier
	 * @return mixed the inserted id
	 */
    abstract public function replace(array $prm);

	/**
	 * Update on the database
	 *
	 * @param array $prm The parameter for the replace query :
	 *  - string table (required) : The table to work in
	 *  - array values (required) : The values to update. A string index array: index are the field
	 *  - db_where|array|string where : The where clause. If array, they are used with AND. (default: none)
	 *  - string whereOp : The operator for the where clause if it's an array (default: AND)
	 * @return int Affected rows (Can return 0 if no change)
	 * @throws nException
	 */
	abstract public function update(array $prm);

    /**
      * Deletes table rows based on a WHERE clause.
	 *
	 * @param array $prm The parameter for the replace query :
	 *  - string table (required) : The table to work in
	 *  - db_where|array|string where : The where clause. If array, they are used with AND. (default: none)
	 *  - string whereOp : The operator for the where clause if it's an array (default: AND)
	 *  - bool optim : Make an optimization after the delete (default: true)
	 * @return int Deleted rows
	 */
    abstract public function delete(array $prm);

	/**
	 * Create a Select Query
	 *
	 * @param array|string $prm Query as string or array: The parameter for the select query and plus:
	 *  - int result : The result type MYSQL_ASSOC, MYSQL_NUM or MYSQL_BOTH (default: MYSQL_BOTH)
	 * @return array Numeric array. Each line is one result
	 */
	abstract public function select($prm);

	/**
	 * Count the number of result
	 *
	 * @param array $prm Same option than select
	 * @return int
	 * @see select
	 */
	abstract public function count(array $prm);

	/**
	 * Returns a list of the tables in the database.
	 *
	 * @param boolean $unPrefix Indicate if the table name shold remove paramettred prefix
	 * @return array
	 */
	abstract public function getTables($unPrefix = true);
	
	/**
	 * Add a prefix that was previsouly removed for a table name
	 * 
	 * @param string $table Table name
	 * @return string Table name with it's prefix, if existing
	 */
	public function prefixTable($table) {
		if ($this->cfg->prefix) {
			$tables = $this->getTables(false);
			$index = array_search($table, $tables);
			if ($index)
				$table = $index;
		}
		return $table;
	}

}
