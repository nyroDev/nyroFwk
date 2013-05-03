<?php
class db_mongo_table extends db_table {

	protected function _initIdent() {
		$this->cfg->ident = $this->cfg->defId;
		if (empty($this->cfg->primary))
			$this->cfg->primary = array($this->getIdent());
		else if(is_string($this->cfg->primary))
			$this->cfg->primary = array($this->cfg->primary);
	}

	public function count(array $prm) {
		
	}

	public function findText($text, $filter = null) {
		
	}

	public function getI18nFields() {
		return array();
	}

	public function getI18nLabel($field = null) {
		
	}

	public function getI18nLinked($field) {
		
	}

	public function getI18nWhereClause($field, $val) {
		
	}
	
	/**
	 * Get the configured fields for this table
	 *
	 * @return array
	 */
	protected function getConfiguredFields() {
		$fields = $this->cfg->getInArray('configuration', 'fields');
		$default = $this->cfg->getInArray('configuration', 'defaultField');
		foreach ($fields as $name=>&$field) {
			$field['name'] = $name;
			config::initTab($field, $default);
		}
		return $fields;
	}

	public function getRange($field = null) {
		
	}

	public function getSortBy($sortBy, $query) {
		
		return array(
			'sortBy'=>$sortBy,
			'query'=>$query
		);
	}

	public function geti18nField($field = null, $keyVal = null) {
		
	}

	public function hasI18n() {
		
	}

	public function select(array $prm = array()) {
		$prm = $this->selectQuery($prm);

		$ret = array();
		$cache = $this->getDb()->getCache();
		$canCache = $this->cfg->cacheEnabled;
		if (!$canCache || !$cache->get($ret, array('id'=>$this->getName().'-'.sha1(serialize($prm))))) {
			$ret = $this->getDb()->select($prm);
			if ($canCache)
				$cache->save();
		}

		if (isset($prm['first']) && $prm['first']) {
			if (!empty($ret))
				return  $this->getDb()->getRow($this, array(
					'data'=>$ret->getNext(),
				));
			else
				return null;
		} else
			return $this->getDb()->getRowset($this, array(
				'data'=>$ret,
			));
	}
	
	public function selectQuery(array $prm) {
		config::initTab($prm, array(
			'where'=>'',
			'whereOp'=>db_where::OPLINK_AND,
			'order'=>'',
		));

		if (is_array($prm['where'])) {
			foreach($prm['where'] as $k=>$v) {
				$posP = strpos($k, '.');
				if (!is_numeric($k) &&  $posP!== false) {
					$newK = substr($k, $posP + 1);
					if (!array_key_exists($newK, $prm['where'])) {
						$prm['where'][$newK] = $v;
						unset($prm['where'][$k]);
					}
				}
			}
		} else if (!empty($prm['where']) && !is_array($prm['where']) && !is_object($prm['where'])
			&& (strpos($prm['where'], '=') === false && strpos($prm['where'], '<') === false
					&& strpos($prm['where'], '>') === false && stripos($prm['where'], 'LIKE') === false
					 && stripos($prm['where'], 'IN') === false)) {
			$prm['where'] = array($this->getIdent()=>new MongoId($prm['where']));
		}
		
		$prm['table'] = $this->rawName;
		
		return $prm;
	}

}