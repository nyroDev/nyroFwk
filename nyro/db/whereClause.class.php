<?php
/**
 * @author Cédric Nirousset <cedric@nyrodev.com>
 * @version 0.2
 * @package nyroFwk
 */
/**
 * SQL Where clause
 */
abstract class db_whereClause extends object {

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
	abstract public function __toString();

}
