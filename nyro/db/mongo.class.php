<?php
class db_mongo extends db_abstract {

	/**
	 * MongoClient connection
	 *
	 * @var MongoClient
	 */
	protected $connection;

	/**
	 * MongoDB instance
	 *
	 * @var MongoDB
	 */
	protected $mongoDb;
	
	/**
	 * Cached collections
	 *
	 * @var array
	 */
	protected $collections = array();
	
	/**
	 * Get the current MongoDB connection (create a new one if needed)
	 *
	 * @return MongoClient
	 */
	public function getConnection() {
		if (is_null($this->connection))
			$this->connection = new MongoClient($this->cfg->server, $this->cfg->options);
		return $this->connection;
	}
	
	/**
	 * Get the current MongoDB connection
	 *
	 * @return MongoDB
	 */
	public function getMongoDb() {
		if (is_null($this->mongoDb))
			$this->mongoDb = $this->getConnection()->selectDB($this->cfg->dbName);
		return $this->mongoDb;
	}
	
	/**
	 * Get a Mongo Collection
	 *
	 * @param string $name Collection name
	 * @return MongoCollection
	 */
	public function getMongoCollection($name) {
		if (!isset($this->collections[$name]))
			$this->collections[$name] = $this->getMongoDb()->selectCollection($name);
		return $this->collections[$name];
	}
	
	public function count(array $prm) {
		
	}

	public function delete(array $prm) {
		
	}

	public function fields($table) {
		
	}


	/**
	 * Cached tables of the DB
	 *
	 * @var array
	 */
	protected $tables;
	
	public function getTables($unPrefix = true) {
		return $this->cfg->getInArray('configuration', 'tables');
	}

	/**
	 * 
	 * @param db_mongo_row $row
	 */
	public function save(db_mongo_row $row) {
		$values = $row->getValues(db_row::VALUESMODE_FLAT);
		$ident = $row->getTable()->getIdent();
		if ($row->isNew() && isset($values[$ident]))
			unset($values[$ident]);
		return $this->getMongoCollection($row->getTable()->getName())->save($values);
	}
	
	public function insert(array $prm) {
		throw new nException('db_abstract - insert : @todo');
	}

	public function replace(array $prm) {
		throw new nException('db_abstract - replace : @todo');
	}

	public function update(array $prm) {
		if (config::initTab($prm, Array(
				'table'=>null,
				'values'=>null,
				'where'=>'',
				'whereOp'=>db_where::OPLINK_AND
			))) {
			throw new nException('db_abstract - update : @todo');
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

	public function select($prm) {
		if (config::initTab($prm, array(
					'table'=>null,
					'where'=>'',
					'whereOp'=>db_where::OPLINK_AND,
					'order'=>'',
					'start'=>0,
					'nb'=>''
				))) {
			$table = $prm['table'];
			$collection = $this->getMongoCollection($table);
			$query = $this->selectQuery($prm);
			db::log('db_mongo SELECT : '.$prm['table'], $query);
			$cursor = $collection->find($query);
			
			if ($prm['order'])
				$cursor->sort(is_array($prm['order']) ? $prm['order'] : array($prm['order']));
			if ($prm['start'])
				$cursor->skip($prm['start']);
			if ($prm['nb'])
				$cursor->limit($prm['nb']);
			
			return $cursor;
		} else
			throw new nException('db_mongo - selectQuery : The table is missing.');
	}
	
	public function selectQuery(array $prm) {
		$query = array();
		
		if (isset($prm['where']))
			$query = array_merge($query, $this->makeWhere($prm['where'], isset($prm['whereOp']) ? $prm['whereOp'] : db_where::OPLINK_AND));
		
		return $query;
	}
	
    /**
     * Make a where clause from a where parameter (select, update or delete)
     *
     * @param string|array $where
     * @param string $whereOp Operator (AND or OR)
     * @return array the where array
     */
    public function makeWhere($where, $whereOp = db_where::OPLINK_AND) {
		$query = array();
		if (!empty($where)) {
			if ($where instanceof db_where) {
				$query = $where->toArray();
			} else if (is_array($where)) {
				$where = array_filter($where);
				if (empty($where))
					return $query;
				$query = $where;
			} else
				$query = array($where);
		}
		return $query;
	}

}