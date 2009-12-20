<?php
/**
 * @author Cédric Nirousset <cedric@nyrodev.com>
 * @version 0.2
 * @package nyro
 */
/**
 * Interface for db classes
 */
abstract class db_pdo_abstract extends db_abstract {

	/**
	 * Database connection
	 *
	 * @var object|resource|null
	 */
	protected $connection;

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
	 * Gets the last ID generated automatically by an IDENTITY/AUTOINCREMENT column.
	 *
	 * @return string
	 */
	public function lastInsertId() {
		$this->_connect();
        return $this->connection->lastInsertId();
	}

	/**
	 * Begin a transaction.
	 */
	protected function _beginTransaction() {
        $this->_connect();
        $this->connection->beginTransaction();
	}

	/**
	 * Commit a transaction.
	 */
	protected function _commit() {
        $this->_connect();
        $this->connection->commit();
	}

	/**
	 * Roll-back a transaction.
	 */
	protected function _rollBack() {
        $this->_connect();
        $this->connection->rollBack();
	}

}
