<?php
/**
 * @author Cédric Nirousset <cedric@nyrodev.com>
 * @version 0.2
 * @package nyroFwk
 */
/**
 * Where clause  to be used in queries
 */
class db_pdo_where extends db_where {

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
	public function toArray() {
		$bind = array();
		$where = array();

		foreach($this->clauses as $c) {
			if ($c instanceof db_where) {
				$tmp = $c->toArray();
				$where[] = $tmp['where'];
				$bind = array_merge($bind, $tmp['bind']);
			} else if ($c instanceof db_whereClause) {
				$where[] = ''.$c;
			} else if (is_array($c)) {
				$val = $c['val'];
				$op = $c['op'];
				
				if ($op == db_where::OP_LIKEALMOST) {
					$val = '%'.$val.'%';
					$op = 'LIKE';
				}
				
				$where[] = $this->getDb()->quoteIdentifier($c['field']).' '.$op.' ?';
				
				if (is_array($val))
					$val = '('.implode(',', array_map(array($this->getDb(), 'quoteValue'), $val)).')';
				else
					$val = $this->getDb()->quoteValue($val);
				$bind[] = $val;
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

}