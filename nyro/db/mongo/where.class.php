<?php

class db_mongo_where extends db_where {
	
	public function add($prm) {
		
	}

	public function toArray() {
		$ret = array();

		foreach($this->clauses as $c) {
			if ($c instanceof db_where) {
				$ret = array_merge($ret, $c->toArray());
			} else if ($c instanceof db_whereClause) {
				$ret = array_merge($ret, $c->toArray());
			} else if (is_array($c)) {
				$val = $c['val'];
				$op = $c['op'];
				
				if ($op == db_where::OP_LIKEALMOST) {
					$val = '%'.$val.'%';
					$op = 'LIKE';
				}
				
				$ret[] = $this->getDb()->quoteIdentifier($c['field']).' '.$op.' ?';
				
				if (is_array($val))
					$val = '('.implode(',', array_map(array($this->getDb(), 'quoteValue'), $val)).')';
				else
					$val = $this->getDb()->quoteValue($val);
			} else {
				// Should be a raw string
				$ret[] = $c;
			}
		}

		return $ret;
	}

	public function toString() {
		return '';
	}
	
}