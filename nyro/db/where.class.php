<?php
/**
 * @author Cédric Nirousset <cedric@nyrodev.com>
 * @version 0.2
 * @package nyroFwk
 */
/**
 * Where clause to be used in queries
 */
abstract class db_where extends object implements Countable {

	const OPLINK_AND = 'AND';
	const OPLINK_OR = 'OR';
	
	const OP_EQUAL = '=';
	const OP_GTE = '>=';
	const OP_LTE = '<=';
	const OP_IN = 'IN';
	const OP_DIFF = '<>';
	const OP_LIKE = 'LIKE';
	const OP_LIKEALMOST = 'LIKE_ALMOST';
	
	/**
	 * The wher clauses
	 *
	 * @var array
	 */
	protected $clauses;

	protected function afterInit() {
		if (is_array($this->cfg->clauses))
			$this->clauses = $this->cfg->clauses;
		else
			$this->clauses = array($this->cfg->clauses);
	}

	/**
	 * Get the db object
	 *
	 * @return db_abstract
	 */
	public function getDb() {
		return $this->cfg->db;
	}

	/**
	 * Clear all clauses
	 */
	public function clear() {
		$this->clauses = array();
	}

	/**
	 * Get all clauses
	 *
	 * @return array
	 */
	public function getClauses() {
		return $this->clauses;
	}

	/**
	 * Set a new array of clauses
	 *
	 * @param array $clauses
	 */
	public function setClauses(array $clauses) {
		$this->clauses = $clauses;
	}

	/**
	 * Add a new clause
	 *
	 * @param string|array $prm String for a raw clause or an array with the keys:
	 *  - field string The field on which the clause is tested (required)
	 *  - op string The operator for testing (default: =)
	 *  - val string The value to test against (required)
	 */
	public function add($prm) {
		if (is_array($prm) && !config::initTab($prm, array(
				'field'=>null,
				'op'=>db_where::OP_EQUAL,
				'val'=>null
			)))
			return;
		$this->clauses[] = $prm;
	}

	/**
	 * Get the clauses as an array
	 *
	 * @return array With keys:
	 *  - bind array value to bind
	 *  - where string Full Where clause to use
	 */
	abstract public function toArray();

	/**
	 * Get the number of clauses
	 *
	 * @return int
	 */
	public function count() {
		return count($this->clauses);
	}

	/**
	 * Get the where clause as a string
	 *
	 * @return string
	 */
	abstract public function toString();

	public function __toString() {
		return $this->toString();
	}

}