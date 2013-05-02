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

	public function insert(array $prm) {
		
	}

	public function replace(array $prm) {
		
	}

	public function select($prm) {
		
	}

	public function update(array $prm) {
		
	}

}