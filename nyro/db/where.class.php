<?php

class db_where extends object implements Countable {

	protected $clauses;

	protected function afterInit() {
		$this->clauses = $this->cfg->clauses;
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
				'op'=>'=',
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
	public function toArray() {
		$bind = array();
		$where = array();

		foreach($this->clauses as $c) {
			if (is_object($c)) {
				$tmp = $c->toArray();
				$where[] = $tmp['where'];
				$bind = array_merge($bind, $tmp['bind']);
			} else if (is_array($c)) {
				$where[] = $this->getDb()->quoteIdentifier($c['field']).' '.$c['op'].' ?';
				$bind[] = is_array($c['val']) ? '('.implode(',', $c['val']).')' : $this->getDb()->quoteValue($c['val']);
			} else {
				// Should be a raw string
				$where[] = $c;
			}
		}

		return array(
			'bind'=>$bind,
			'where'=>'('.implode(') '.$this->cfg->op.' (', $where).')',
		);
	}

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
	public function toString() {
		$prm = $this->toArray();

		$tmp = explode('?', $prm['where'], count($prm['bind'])+1);
		array_splice($prm['bind'], count($tmp));

		$where = '';
		while($tmp2 = array_shift($tmp)) {
			$where.= $tmp2.array_shift($prm['bind']);
		}
		return $where;
	}

	public function __toString() {
		return $this->toString();
	}

}