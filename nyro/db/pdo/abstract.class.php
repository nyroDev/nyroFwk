<?php
/**
 * @author Cédric Nirousset <cedric@nyrodev.com>
 * @version 0.2
 * @package nyroFwk
 */
/**
 * Interface for db classes
 */
abstract class db_pdo_abstract extends db_abstract {

	const FETCH_ASSOC = PDO::FETCH_ASSOC;
	const FETCH_BOTH = PDO::FETCH_BOTH;
	const FETCH_NUM = PDO::FETCH_NUM;
	const FETCH_ROWSET = PDO::FETCH_CLASS;
	const FETCH_COLUMN = PDO::FETCH_COLUMN;

	/**
	 * Database connection
	 *
	 * @var object|resource|null
	 */
	protected $connection;
	
	/**
	 * Number of transaction requested
	 *
	 * @var int
	 */
	protected $transactionCounter = 0;

	/**
	 * Returns the connection object, ressource.
	 * Initialize the connection if need.
	 *
	 * @return object|resource|null
	 */
	public function getConnection() {
		$this->_connect();
		return $this->connection;
	}

	/**
	 * Creates a connection to the database.
	 */
	protected function _connect() {
        if ($this->connection)
            return;

		$this->connection = new PDO(
			$this->_dsn(),
			$this->cfg->user,
			$this->cfg->pass,
			$this->cfg->driverOptions);
		
		foreach($this->cfg->conQuery as $q)
			$this->connection->query($q);

		$this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	}

	/**
	 * Create The dsn string for the connection.
	 *
	 * @return string
	 */
	protected function _dsn() {
		return $this->cfg->driver.':host='.$this->cfg->host.';dbname='.$this->cfg->base;
	}

	/**
	 * Force the connection to close.
	 *
	 * @return void
	 */
	public function closeConnection() {
		$this->connection = null;
	}

	/**
	 * Leave autocommit mode and begin a transaction.
	 *
	 * @return bool True
	 */
	public function beginTransaction() {
		if(!$this->transactionCounter++) {
			$this->_connect();
			return $this->connection->beginTransaction();
		}
		return $this->transactionCounter >= 0;
	}

	/**
	 * Commit a transaction and return to autocommit mode.
	 *
	 * @return bool True
	 */
	public function commit() {
		if(!--$this->transactionCounter) {
			$this->_connect();
			return $this->connection->commit();
		}
		return $this->transactionCounter >= 0; 
	}

	/**
	 * Roll back a transaction and return to autocommit mode.
	 *
	 * @return bool True
	 */
	public function rollBack() {
		if($this->transactionCounter >= 0) {
            $this->transactionCounter = 0;
			$this->_connect();
			$this->connection->rollBack();
        }
		$this->transactionCounter = 0;
        return false;
	}

	/**
	 * Prepare and execute a query, with binding if provided
	 *
	 * @param string $sql The query to execute
	 * @return PDOStatement
	 */
	public function query($sql, array $bind=array()) {
		$this->_connect();
		$stmt = $this->prepare($sql);
		db::log($sql, $bind);
		$nbTry = 0;
		$executed = false;
		$lastException = null;
		while (!$executed && $nbTry < 3) {
			try {
				$nbTry++;
				$stmt->execute($bind);
				$executed = true;
			} catch (Exception $e) {
				$lastException = $e;
			}
		}
		if (!$executed)
			throw $lastException;
		return $stmt;
	}

	/**
	 * Prepare a statement and return a PDOStatement-like object.
	 *
	 * @param string $sql SQL query
	 * @param array $options Driver option
	 * @return PDOStatement
	 */
	public function prepare($sql, array $options=array()) {
		$this->_connect();
		return $this->connection->prepare($sql, $options);
	}

    /**
	 * Insert on the database
	 *
	 * @param array $prm The parameter for the insert query :
	 *  - string table (required) : The table to work in
	 *  - array values (required) : The values to insert. The key are the identifier
	 * @return mixed the inserted id
	 */
    public function insert(array $prm) {
		if (config::initTab($prm, Array(
					'table'=>null,
					'values'=>null
				))) {

			$cols = array_map(array($this, 'quoteIdentifier'), array_keys($prm['values']));
			$vals = count($cols) > 0? array_fill(0, count($cols), '?') : array();

			$sql = 'INSERT INTO '.$this->quoteIdentifier($prm['table']);
			$sql.= ' ('.implode(',',$cols).') VALUES ('.implode(',',$vals).')';

	        $stmt = $this->query($sql, array_values($prm['values']));
	        return $this->lastInsertId();
		} else
			throw new nException('db_pdo_abstract - insert: The table or the values is missing.');
    }

	/**
	 * Gets the last ID generated automatically by an IDENTITY/AUTOINCREMENT column.
	 *
	 * @return string
	 */
	public function lastInsertId() {
		$this->_connect();
        return $this->connection->lastInsertId();
	}

    /**
	 * Replace on the database
	 *
	 * @param array $prm The parameter for the replace query :
	 *  - string table (required) : The table to work in
	 *  - array values (required) : The values to replace. The key are the identifier
	 * @return mixed the inserted id
	 */
    public function replace(array $prm) {
		if (config::initTab($prm, Array(
					'table'=>null,
					'values'=>null
				))) {

			$cols = array_map(array($this, 'quoteIdentifier'), array_keys($prm['values']));
			$vals = array_fill(0, count($cols), '?');

			$sql = 'REPLACE INTO '.$this->quoteIdentifier($prm['table']);
			$sql.= ' ('.implode(',',$cols).') VALUES ('.implode(',',$vals).')';

	        $stmt = $this->query($sql, array_values($prm['values']));
	        return $this->lastInsertId();
		} else
			throw new nException('db_pdo_abstract - replace: The table or the values is missing.');
    }

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
	public function update(array $prm) {
		if (config::initTab($prm, Array(
				'table'=>null,
				'values'=>null,
				'where'=>'',
				'whereOp'=>db_where::OPLINK_AND
			))) {

			$set = array();
			foreach($prm['values'] as $col=>$val)
				$set[] = $this->quoteIdentifier($col).'=?';

			$sql = 'UPDATE '.$this->quoteIdentifier($prm['table']);
			$sql.= ' SET '.implode(',',$set);
			$sql.= $this->makeWhere($prm['where'], $prm['whereOp']);
	        $stmt = $this->query($sql, array_values($prm['values']));
	        return $stmt->rowCount();
		} else
			throw new nException('db_abstract - update : The table or the values is missing.');
    }

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
    public function delete(array $prm) {
		if (config::initTab($prm, Array(
					'table'=>null,
					'where'=>'',
					'whereOp'=>db_where::OPLINK_AND,
					'optim'=>true
				))) {

			$sql = 'DELETE FROM '.$this->quoteIdentifier($prm['table'])
						.$this->makeWhere($prm['where'], $prm['whereOp']);

			$stmt = $this->query($sql);
			$nb = $stmt->rowCount();

			if ($prm['optim'])
				$this->optimize($prm['table']);

			return $nb;
		} else
			throw new nException('db_abstract - delete : The table is missing.');
    }

    /**
     * Make a where clause from a where parameter (select, update or delete)
     *
     * @param string|array $where
     * @param string $whereOp Operator (AND or OR)
	 * @param bool $incWhere Indicates if the WHERE keywords should be included at the beginning
     * @return null|string the where string, starting with WHERE
     */
    public function makeWhere($where, $whereOp = db_where::OPLINK_AND, $incWhere = true) {
    	$query = null;
		if (!empty($where)) {
			if ($where instanceof db_where)
				$query = $where->toString();
			else if (is_array($where)) {
				$tmp = array();
				$where = array_filter($where);
				if (empty($where))
					return $query;
				foreach($where as $k=>$v) {
					$tmp[] = is_numeric($k) ? $v : $this->quoteIdentifier($k).'="'.$v.'"';
				}
				$query = '('.implode(' '.$whereOp.' ', $tmp).')';
			} else
				$query = $where;
			$query = ($incWhere && $query? ' WHERE ' : null).$query;
		}
		return $query;
    }

	/**
	 * Get the fetch mode.
	 *
	 * @return int
	 */
	public function getFetchMode() {
		return $this->cfg->fetchMode;
	}

	/**
	 * Set the fetch mode.
	 *
	 * @param int $mode
	 */
	public function setFetchMode($mode) {
		$this->cfg->fetchMode = (int) $mode;
	}

	/**
	 * Create a Select Query
	 *
	 * @param array $prm The parameter for the select query :
	 *  - array|string fields : The fields to select (default: *)
	 *  - string table (required) : The table to work in
	 *  - string moreTables : More table to select (string or array, if array, keys will be used as alias if non numeric)
	 *  - array join : tables to join. Keys are:
	 *   - string table (required) : table to join
	 *   - string dir: how to join (default: 'left')
	 *   - string on: on Clause to join the table (default: 1)
	 *   - string alias: table alias (default: none)
	 *  - array bind : Data to bind
	 *  - bool bindData : Bind the data inside the query
	 *  - db_where|array|string where : The where clause. If array, they are used with AND. (default: none)
	 *  - string whereOp : The operator for the where clause if it's an array (default: AND)
	 *  - string order : The order clause (default: none)
	 *  - int start : The select start (default: 0)
	 *  - int nb : The select limit (default: unlimited)
	 *  - string group : The group clause (default: none)
	 *  - string groupAfter : The group clause to be done after everything else (useful for order grouping queries) (default: none)
	 *  - string having : The having clause (default: none)
	 * @return string The select query
	 * @throws nException if no table provided
	 */
	public function selectQuery(array $prm) {
		if (config::initTab($prm, array(
					'fields'=>'*',
					'i18nFields'=>'',
					'table'=>null,
					'moreTables'=>false,
					'join'=>'',
					'bind'=>array(),
					'bindData'=>false,
					'where'=>'',
					'whereOp'=>db_where::OPLINK_AND,
					'order'=>'',
					'start'=>0,
					'nb'=>'',
					'group'=>'',
					'groupAfter'=>'',
					'having'=>''
				))) {

			$table = $this->getTable($prm['table']);
			$tableName = $this->quoteIdentifier($prm['table']);
			if (is_array($prm['fields'])) {
				$f = implode(',', array_map(array($this, 'quoteIdentifier'), $prm['fields']));
			} else {
				if (strpos($prm['fields'], $this->cfg->quoteIdentifier) === false) {
					$f = implode(',', array_map(array($this, 'quoteIdentifier'), explode(',', $prm['fields'])));
				} else
					$f = $prm['fields'];
			}

			if (!empty($prm['i18nFields'])) {
				$i18nTable = $this->getTable($prm['table'].db::getCfg('i18n'));
				$i18nTableName = $this->quoteIdentifier($i18nTable->getRawName());
				$primary = $i18nTable->getPrimary();
				$prm['join'][] = array(
					'table'=>$i18nTable->getRawName(),
					'on'=>$tableName.'.'.$table->getIdent().'='.$i18nTableName.'.'.$primary[0].
							' AND '.$i18nTableName.'.'.$primary[1].'="'.request::get('lang').'"'
				);
				if (is_array($prm['i18nFields'])) {
					array_walk($prm['i18nFields'], array($this, 'quoteIdentifier'));
					$f.= ','.$i18nTableName.'.'.implode(','.$i18nTableName.'.', $prm['fields']);
				} else if ($prm['i18nFields']) {
					foreach(explode(',', $prm['i18nFields']) as $t) {
						$f.= ','.$i18nTableName.'.'.$t;
					}
				}
			}

			$query = 'SELECT '.$f.' FROM ';
			
			if ($prm['moreTables']) {
				if (is_array($prm['moreTables'])) {
					foreach($prm['moreTables'] as $k=>$v) {
						if (is_numeric($k)) {
							$query.= $this->quoteIdentifier($v).', ';
						} else {
							$query.= $this->quoteIdentifier($v).' '.$this->quoteIdentifier($k).', ';
						}
					}
				} else {
					$query.= $prm['moreTables'].', ';
				}
			}
			$query.= $tableName;

			$tblAlias = array();
			if (is_array($prm['join'])) {
				$join = array();
				foreach($prm['join'] as &$v) {
					$v = array_merge(array('dir'=>'left', 'on'=>1, 'alias'=>''), $v);
					$alias = null;
					if (!empty($v['alias'])) {
						$alias = ' AS '.$this->quoteIdentifier($v['alias']);
						if ($v['table'] != $table->getRawName())
							$tblAlias[$v['table']] = $v['alias'];
					}
					$join[] = strtoupper($v['dir']).' JOIN '.$this->quoteIdentifier($v['table']).$alias.' ON '.$v['on'];
				}
				$query.= ' '.implode(' ', $join).' ';
			}

			$query.= $this->makeWhere($prm['where'], $prm['whereOp']);

			if (!empty($prm['group']))
				$query.= ' GROUP BY '.$prm['group'];

			if (!empty($prm['having']))
				$query.= ' HAVING '.$prm['having'];

			if (!empty($prm['order']))
				$query.= ' ORDER BY '.$prm['order'];

			if (!empty($prm['nb'])) {
				if (empty($prm['start']))
					$prm['start'] = 0;
				$query.= ' LIMIT '.$prm['start'].','.$prm['nb'];
			}

			if ($prm['bindData'] && !empty($prm['bind']) && is_array($prm['bind'])) {
				$tmp = explode('?', $query, count($prm['bind'])+1);
				array_splice($prm['bind'], count($tmp));

				$query = '';
				while($tmp2 = array_shift($tmp)) {
					$query.= $tmp2.array_shift($prm['bind']);
				}
			}

			if ($prm['groupAfter'])
				$query = 'SELECT * FROM ('.$query.') AS res GROUP BY '.$prm['groupAfter'];

			return $this->tableAlias($query, $tblAlias);
		} else
			throw new nException('db_pdo_abstract - selectQuery : The table is missing.');
	}

	/**
	 * Apply the table alias to a query
	 *
	 * @param string $query The query
	 * @param array $tblAlias The alias (keys: tablenames, values: aliases)
	 * @return string The query with the aliases applied
	 */
	protected function tableAlias($query, array $tblAlias) {
		$search = array();
		$replace = array();
		foreach($tblAlias as $tbl=>$alias) {
			$search = array_merge($search, array(
				' '.$tbl.'.',
				' '.$this->quoteIdentifier($tbl).'.',
				'('.$this->quoteIdentifier($tbl).'.'
			));
			$replace = array_merge($replace, array(
				' '.$alias.'.',
				' '.$this->quoteIdentifier($alias).'.',
				'('.$this->quoteIdentifier($alias).'.'
			));
		}
		return str_replace($search, $replace, $query);
	}

	/**
	 * Create a Select Query
	 *
	 * @param array|string $prm Query as string or array: The parameter for the select query (@see selectQuery) and plus:
	 *  - int result : The result type MYSQL_ASSOC, MYSQL_NUM or MYSQL_BOTH (default: MYSQL_BOTH)
	 * @return array Numeric array. Each line is one result
	 */
	public function select($prm) {
		if (is_array($prm)) {
			config::initTab($prm, array(
					'bind'=>array(),
					'forceFetchMode'=>0,
				));
			$stmt = $this->query($this->selectQuery($prm), $prm['bind']);
			$fetchMode = $prm['forceFetchMode'] ? $prm['forceFetchMode'] : $this->cfg->fetchMode;
		} else {
			$stmt = $this->query($prm);
			$fetchMode = $this->cfg->fetchMode;
		}
		if ($fetchMode == PDO::FETCH_CLASS) {
			$tmp = $stmt->fetchAll(PDO::FETCH_ASSOC);
			return $tmp;

			$cfg = array(
				'db'=>$this,
				'table'=>$prm['table'],
				'props'=>array_keys($tmp[0])
			);
			foreach($tmp as $t) {
				$row = factory::get($className, $cfg);
				$row->setValues($t);
				$ret[] = $row;
			}
			return $ret;
		} else {
			return $stmt->fetchAll($fetchMode);
		}
	}

	/**
	 * Count the number of result
	 *
	 * @param array $prm Same option than select
	 * @return int
	 * @see select
	 */
	public function count(array $prm) {
		$subQuery = $this->selectQuery(array_merge($prm, array('bindData'=>true)));
		$stmt = $this->query('SELECT COUNT(*) AS count FROM ('.$subQuery.') AS subquerycount');
		$tmp = $stmt->fetch(MYSQL_ASSOC);
		$count = $tmp['count'];
		$stmt->fetchAll();
		$stmt->closeCursor();
		$stmt = null;
		return $count;
	}

	/**
	 * Fetches all SQL result rows as an associative array.
	 * Same parameter as select.
	 *
	 * The first column is the key, the entire row array is the
	 * value.  You should construct the query to be sure that
	 * the first column contains unique values, or else
	 * rows with duplicate values in the first column will
	 * overwrite previous data.
	 *
	 * @param array $prm: same as select
	 * @return array
	 * @see select
	 */
	public function fetchAssoc(array $prm) {
		$prm['forceFetchMode'] = PDO::FETCH_ASSOC;
		return $this->select($prm);
	}

	/**
	 * Fetches all SQL result rows as an array of key-value pairs.
	 *
	 * The first column is the key, the second column is the
	 * value.
	 *
	 * @param array $prm: same as select.
	 * @return array
	 * @see select
	 */
	public function fetchPairs(array $prm) {
		$prm['forceFetchMode'] = PDO::FETCH_NUM;
		return $this->select($prm);
	}

	/**
	 * Fetches the first row of the SQL result.
	 * Uses the current fetchMode for the adapter.
	 *
	 * @param array $prm: same as select.
	 * @return array
	 * @see select
	 */
	public function fetchRow(array $prm) {
		$prm['nb'] = 1;
		$prm['start'] = 0;
		return array_pop($this->select($prm));
	}

	/**
	 * Quotes an identifier.
	 *
	 * @param string $ident The identifier.
	 * @return string The quoted identifier.
	 */
	public function quoteIdentifier($ident) {
		if (strpos($ident, '(') !== false)
			return $ident;
		$tmpSpace = explode(' ', $ident);
		$tmp = explode('.', $tmpSpace[0]);
		if (count($tmp) == 1 && $tmp[0] == '*')
			return '*';
		else if (count($tmp) == 2 && $tmp[1] == '*')
			return $this->cfg->quoteIdentifier.$tmp[0].$this->cfg->quoteIdentifier.'.*';
		$tmpSpace[0] = $this->cfg->quoteIdentifier
			.implode($this->cfg->quoteIdentifier.'.'.$this->cfg->quoteIdentifier, $tmp)
			.$this->cfg->quoteIdentifier;
		return implode(' ', $tmpSpace);
	}

	/**
	 * Quotes a value.
	 *
	 * @param string $value The value.
	 * @return string The quoted value.
	 */
	public function quoteValue($value) {
		return $this->cfg->quoteValue.addcslashes($value, $this->cfg->quoteValue).$this->cfg->quoteValue;
	}

	/**
	 * Optimize a table
	 *
	 * @param string $table The table name
	 */
	public function optimize($table) {
		$this->query('OPTIMIZE TABLE '.$table);
	}

	/**
	 * Get the i18n tablename
	 *
	 * @param string $table table name
	 * @return string|null The i18n tablename or null if not found
	 */
	public function getI18nTable($table) {
		$tmp = $this->getTablesWith(array(
			'end'=>$table.db::getCfg('i18n')
		));
		if (count($tmp) == 1)
			return $tmp[0];
		return null;
	}

	/**
	 * Returns the fields
	 *
	 * @param string $table TableName
	 * @return array
	 */
	abstract public function fields($table);

}
