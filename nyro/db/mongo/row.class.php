<?php
class db_mongo_row extends db_row {

	/**
	 * Save the row in the database
	 *
	 * @return bool|mixed True if successful or no changes was done|mixed if new and inserted, will be last insert id
	 */
	public function save() {
		$ret = $this->getDb()->save($this);
		if ($ret) {
			$this->saveRelated();
			$this->getTable()->clearCache();
		}
		return $ret;
	}
	
	public function getAround(array $prm = array()) {
		
	}

	public function getI18n($key, $mode = 'flat', $lang = null) {
		
	}

	public function getI18nValues() {
		
	}

	public function getRelated($name) {
		
	}

	public function setI18n(array $values, $force = false, $lg = null) {
		
	}

	public function setRelated($related, $name = null) {
		
	}

	public function whereClause() {
		return array(
			$this->getTable()->getIdent()=>$this->getId()
		);
	}

}