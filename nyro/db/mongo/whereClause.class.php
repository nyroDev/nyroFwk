<?php

class db_mongo_whereClause extends db_whereClause {
	
	public function toArray() {
		$ret = array();
		
		// @todo
		/*
		if (!is_null($this->cfg->mt))
			$tmp[0] = '('.$this->cfg->name.'>"'.$this->cfg->mt.'")';
		if (!is_null($this->cfg->mte) && (is_null($this->cfg->mt) || $this->cfg->mte >= $this->cfg->mt))
			$tmp[0] = '('.$this->cfg->name.'>="'.$this->cfg->mte.'")';

		if (!is_null($this->cfg->lt))
			$tmp[1] = '('.$this->cfg->name.'<"'.$this->cfg->lt.'")';
		if (!is_null($this->cfg->lte) && (is_null($this->cfg->lt) || $this->cfg->lte <= $this->cfg->lt))
			$tmp[1] = '('.$this->cfg->name.'<="'.$this->cfg->lte.'")';

		if (!is_null($this->cfg->eq))
			$tmp[] = '('.$this->cfg->name.'="'.$this->cfg->eq.'")';

		if (!is_null($this->cfg->df))
			$tmp[] = '('.$this->cfg->name.'!="'.$this->cfg->df.'")';

		if (!is_null($this->cfg->contains))
			$tmp[] = '('.$this->cfg->name.' LIKE "%'.$this->cfg->contains.'%")';

		if (!is_null($this->cfg->start))
			$tmp[] = '('.$this->cfg->name.' LIKE "'.$this->cfg->start.'%")';

		if (!is_null($this->cfg->end))
			$tmp[] = '('.$this->cfg->name.' LIKE "%'.$this->cfg->end.'")';

		if (!is_null($this->cfg->raw))
			$tmp[] = '('.$this->cfg->raw.')';

		if (!empty($this->cfg->in)) {
			if (is_array($this->cfg->in)) {
				$in = $this->cfg->in;
				array_walk($in, create_function('&$v', '$v = \'"\'.$v.\'"\';'));
				$tmp[] = '('.$this->cfg->name.' IN ('.implode(',', $in).'))';
			} else
				$tmp[] = '('.$this->cfg->name.' IN ('.$this->cfg->in.'))';
		}

		if (!empty($this->cfg->freeClause))
			$tmp = array_merge($tmp, $this->cfg->freeClause);
		 */
		
		if (!empty($this->cfg->in))
			$ret['$in'] = $this->cfg->in;
		
		return count($ret) > 0 ? array($this->cfg->name=>$ret) : array();
	}
	
	public function __toString() {
		return '';
	}	
}