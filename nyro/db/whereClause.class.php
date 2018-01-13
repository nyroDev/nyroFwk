<?php
/**
 * @author Cédric Nirousset <cedric@nyrodev.com>
 * @version 0.2
 * @package nyroFwk
 */
/**
 * SQL Where clause
 */
class db_whereClause extends nObject {

	/**
	 * Using to set the whole configuration parameter
	 */
	public function __set($name, $val) {
		$this->cfg->set($name, $val);
	}

	/**
	 * Using to set the 'mt' and 'lt' parameter in the same time
	 */
	public function between($st, $end) {
		$this->cfg->mt = $st;
		$this->cfg->lt = $end;
	}

	/**
	 * Using to set the 'mte' and 'lte' parameter in the same time
	 */
	public function betweenEq($st, $end) {
		$this->cfg->mte = $st;
		$this->cfg->lte = $end;
	}

	/**
	 * Transform the object in string to be using in SQL statement
	 *
	 * @return string
	 */
	public function __toString() {
		$tmp = array();

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

		return implode(' '.$this->cfg->op.' ', $tmp);
	}

}
