<?php
/**
 * @author Cédric Nirousset <cedric@nyrodev.com>
 * @version 0.2
 * @package nyroFwk
 */
/**
 * rowset object for fetching select results
 */
class db_pdo_rowset extends db_rowset implements Iterator, Countable, ArrayAccess {

	/**
	 * Get a specific item of the rowset
	 *
	 * @param int $number Index of the ement
	 * @return db_row Element from the collection
	 */
	public function get($number) {
		if (!array_key_exists($number, $this->_rows) && $this->cfg->checkInArray('data', $number)) {
			$this->_rows[$number] = $this->getDb()->getRow($this->getTable(), array(
				'data'=>$this->cfg->getInArray('data', $number),
			));
		}
		return isset($this->_rows[$number]) ? $this->_rows[$number] : null;
	}

}