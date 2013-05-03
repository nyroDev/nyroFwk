<?php
class db_mongo_rowset extends db_rowset {
	
	protected $initedNumber = 0;
	
	protected function afterInit() {
		$this->_count = $this->cfg->data->count();
	}
	
	/**
	 * Get the cursor
	 *
	 * @return MongoCursor
	 */
	public function getCursor() {
		return $this->cfg->data;
	}
	
	public function get($number) {
		if (!isset($this->_rows[$number]) && $this->getCursor()->count() >= $number) {
			for ($i = $this->initedNumber; $i <= $number; $i++) {
				if (!isset($this->_rows[$i]))
					$this->_rows[$i] = $this->getDb()->getRow($this->getTable(), array(
						'data'=>$this->getCursor()->getNext()
					));
				$this->initedNumber = $i;
			}
		}
		return isset($this->_rows[$number]) ? $this->_rows[$number] : null;
	}

}