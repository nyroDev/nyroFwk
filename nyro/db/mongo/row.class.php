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
			$this->setNew(false);
			$this->saveRelated();
			$this->getTable()->clearCache();
		}
		return $ret;
	}
	
	public function normalizeValues(array $data) {
		$fields = $this->getTable()->getField();
		$fieldsI18n = $this->getTable()->getI18nFields();
		$i18n = db::getCfg('i18n');
		$ret = array();
		foreach($data as $k=>$v) {
			if (isset($fields[$k])) {
				$ret[$k] = $this->normalizeValue($v, $fields[$k]['type']);
			} else if ($k == $i18n) {
				$ret[$k] = array();
				foreach($v as $lg=>$vals) {
					$ret[$k][$lg] = array();
					foreach($vals as $kk=>$vv) {
						if (isset($fieldsI18n[$kk])) {
							$ret[$k][$lg][$kk] = $this->normalizeValue($vv, $fieldsI18n[$kk]['type']);
						}
					}
				}
			}
		}
		return $ret;
	}
	
	protected function normalizeValue($value, $type) {
		switch ($type) {
			case 'int':
				$value = intval($value);
				break;
			case 'number':
				$value = floatval($value);
				break;
			case 'boolean':
				$value = (bool) $value;
				break;
		}
		return $value;
	}
	
	public function getAround(array $prm = array()) {
		
	}

	public function getI18n($key, $mode = db_row::VALUESMODE_FLAT, $lang = null) {
		if (is_null($lang) || !$lang)
			$lang = request::get('lang');
		$values = $this->getValues($mode, db_row::VALUESFILTER_NONE);
		$i18n = db::getCfg('i18n');
		if (isset($values[$i18n]) && isset($values[$i18n][$lang]) && isset($values[$i18n][$lang][$key]))
			return $values[$i18n][$lang][$key];
		return null;
	}

	public function getI18nValues() {
		$ret = array();
		if ($this->getTable()->hasI18n()) {
			$values = $this->getValues(db_row::VALUESMODE_FLAT, db_row::VALUESFILTER_NONE);
			$i18n = db::getCfg('i18n');
			if (isset($values[$i18n]))
				$ret = $values[$i18n];
		}
		return $ret;
	}

	public function getRelated($name) {
		$related = $this->getTable()->getRelated($this->getTable()->getRelatedTableName($name));
		$id = $this->getId();
		if (!$id)
			return array();
		
		$ids = array();
		$tmp = $related['tableObj']->select(array(
			'where'=>array(
				$related['fk1']['name']=>$id
			),
		));
		$tmpRes = array();
		$tbl = $related['tableObj']->getLinkedTable($related['fk2']['name']);
		foreach($tmp as $t)
			$tmpRes[] = $tbl->find($t->get($related['fk2']['name']));
		
		return $tmpRes;
	}

	public function setRelated($related, $name = null) {
		if (!is_null($name)) {
			$name = $this->getTable()->getRelatedTableName($name);
			if ($this->getTable()->isRelated($name)) {
				if (!array_key_exists($name, $this->related))
					$this->related[$name] = array();

				if ($related instanceof db_row)
					$this->related[$name][] = $related;
				else {
					foreach($related as $v) {
						if ($v instanceof db_row)
							$this->related[$name][] = $v;
						else
							$this->related[$name][] = $this->getTable()->getRelatedTableRow($name, $v);
					}
				}
			}
			debug::trace($this->related, 2);
		} else {
			foreach($related as $t=>$v)
				$this->setRelated($v, $t);
		}
	}

	public function setI18n(array $values, $force = false, $lg = null) {
		if (!is_null($lg) && $lg) {
			$i18n = db::getCfg('i18n');
			if (!isset($this->changes[$i18n]))
				$this->changes[$i18n] = array();
			if (!isset($this->changes[$i18n][$lg]))
				$this->changes[$i18n][$lg] = array();
			$this->changes[$i18n][$lg] = array_merge($values, $this->changes[$i18n][$lg]);
		} else {
			foreach($values as $lg=>$val)
				$this->setI18n($val, $force, $lg);
		}
	}

	public function whereClause() {
		return array(
			$this->getTable()->getIdent()=>$this->getId()
		);
	}

}