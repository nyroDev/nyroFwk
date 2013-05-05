<?php
abstract class db_row extends object implements ArrayAccess {

	const VALUESMODE_DATA = 'data';
	const VALUESMODE_FLAT = 'flat';
	const VALUESMODE_FLAT_NORELATED = 'flatNoRelated';
	const VALUESMODE_FLATREAL = 'flatReal';
	const VALUESMODE_FLATREAL_NORELATED = 'flatRealNoRelated';
	
	const VALUESFILTER_COLS = 'cols';
	const VALUESFILTER_NONE = 'none';
	const VALUESFILTER_NORMDB = 'normDb';
	
	
	/**
	 * Changes done
	 *
	 * @var array
	 */
	protected $changes = array();

	/**
	 * Indicates if the row is new
	 *
	 * @var bool
	 */
	protected $new = true;

	/**
	 * Table object related to the row
	 *
	 * @var db_table
	 */
	protected $table;

	/**
	 * Linked array values
	 *
	 * @var array
	 */
	protected $linked = array();

	/**
	 * Related array values
	 *
	 * @var array
	 */
	protected $related = array();
	
	protected function afterInit() {
		$defaultClass = 'db_row_'.$this->cfg->table->getName();
		if (get_class($this) != $defaultClass)
			$this->cfg->overload($defaultClass);

		$this->table = $this->cfg->table;

		if (!empty($this->cfg->data)) {
			$this->loadData($this->cfg->data);
		} else if (!empty($this->cfg->findId)) {
			$tmp = $this->getTable()->find($this->cfg->findId);
			if ($tmp) {
				$this->cfg->data = $tmp->getValues(db_row::VALUESMODE_DATA);
				$this->setNew(false);
			}
			unset($tmp);
		}
	}

	/**
	 * Load data in the row
	 *
	 * @param array $data
	 */
	public function loadData(array $data) {
		$this->cfg->data = $data;
		if (array_key_exists($this->getTable()->getIdent(), $data) && $data[$this->getTable()->getIdent()]) {
			$this->setNew(false);
		} else {
			$primary = $this->getTable()->getPrimary();
			$p = 0;
			foreach($primary as $pp) {
				if (array_key_exists($pp, $data) && $data[$pp])
					$p++;
			}
			if ($p == count($primary))
				$this->setNew(false);
		}

		$linkedKey = db::getCfg('linked');
		if (array_key_exists($linkedKey, $data)) {
			$this->setLinked($data[$linkedKey]);
		}

		$relatedKey = db::getCfg('related');
		if (array_key_exists($relatedKey, $data)) {
			$this->setRelated($data[$relatedKey]);
		}
	}

	/**
	 * Reload the row data against the db
	 * 
	 * @param bool $force Force reload or do it only if needed
	 */
	public function reload($force = true) {
		if (!$this->isNew() && $this->getId() && ($force || $this->cfg->needReload)) {
			$this->loadData($this->getTable()->find($this->getId())->getValues());
			$this->cfg->needReload = false;
		}
	}

	/**
	 * Return the db object
	 *
	 * @return db_abstract
	 */
	public function getDb() {
		return $this->cfg->db;
	}

	/**
	 * Return the table object
	 *
	 * @return db_table
	 */
	public function getTable() {
		return $this->table;
	}

	/**
	 * Get a where object
	 *
	 * @param array $prm The configuration for the where object
	 * @return db_where
	 */
	public function getWhere(array $prm = array()) {
		return $this->getDb()->getWhere($prm);
	}

	/**
	 * Get the default form with the field information
	 *
	 * @param null|array $showFields null to get every fields or array to select the fields
	 * @param array $formParam Parameter for the form
	 * @param bool $passConfirm Indicate if the confirmation password should ba automatically added
	 * @return form_db
	 */
	public function getForm($showFields = null, array $formParam = array(), $passConfirm = true) {
		$form = factory::get('form_db', array_merge($formParam, array(
			'table'=>$this->getTable()
		)));
		/* @var $form form_db */
		foreach($this->getTable()->getField() as $f) {
			if ($f['name'] != $this->getTable()->getIdent() && !$f['auto'] &&
					(empty($showFields) || in_array($f['name'], $showFields))) {
				$f['label'] = $this->getTable()->getLabel($f['name']);
				$f['link'] = $this->getTable()->getLinked($f['name']);
				$f['value'] = $this->get($f['name']);
				$obj = $form->addFromField($f);
				if ($passConfirm && $obj instanceof form_password) {
					$name = $f['name'];
					$f['name'].= 'Confirm';
					$f['label'] = $this->getTable()->getLabel($f['name']);
					$moreConf = $this->getTable()->getCfg()->getInArray('fields', $f['name']);
					if (is_array($moreConf) && array_key_exists('comment', $moreConf)) {
						factory::mergeCfg($f, $moreConf);
					}
					$form->addFromField($f)->addRule('equal', $obj);
					$form->addNotValue($f['name']);
					if (!$this->isNew()) {
						// remove the required validation for the 2 password fields but keep the equal
						$form->get($name)->getValid()->delRule('required');
						$form->get($f['name'])->getValid()->delRule('required');
					}
					if (!empty($showFields) && !in_array($f['name'], $showFields)) {
						$key = array_search($name, $showFields);
						array_splice($showFields, $key+1, 0, array($f['name']));
					}
				}
			}
		}

		$relatedUpdated = false;
		$related = $this->cfg->getInArray('data', 'related');
		foreach($this->getTable()->getRelated() as $t=>$r) {
			if (empty($showFields) || in_array($r['tableLink'], $showFields)) {
				$r['name'] = $r['tableLink'];
				$r['label'] = $this->getTable()->getLabel($r['table']);
				$r['valid'] = false;
				$form->addFromRelated($r);
				
				if (!$this->getTable()->getCfg()->autoJoin) {
					$relatedUpdated = true;
					$tmp = $this->getRelated($t);
					$related[$r['table']] = $tmp;
				}
			}
		}
		
		if ($relatedUpdated)
			$this->cfg->setInArray('data', 'related', $related);

		$form->setValues($this->getValues(db_row::VALUESMODE_FLAT));

		$i18nFields = $this->getTable()->getI18nFields();
		$i18nFieldsT = array();
		
		foreach($i18nFields as $f) {
			if ((empty($showFields) || in_array(db::getCfg('i18n').$f['name'], $showFields))) {
				$i18nFieldsT[] = $f['name'];
				$f['label'] = $this->getTable()->getI18nLabel($f['name']);
				$form->addFromField($f, true);
			}
		}

		$form->finalize();

		if ($i18nValues = $this->getI18nValues()) {
			$tmp = array();
			foreach($i18nValues as $lang=>$values) {
				foreach($values as $k=>$v)
					$tmp[db::getCfg('i18n').'['.$lang.']['.$k.']'] = $v;
			}
			$form->setValues($tmp);
		}

		$form->setBound(false);
		if (is_array($showFields) && !empty($showFields))
			$form->reOrder($showFields);

		return $form;
	}

	/**
	 * Check if the row is new
	 *
	 * @return bool
	 */
	public function isNew() {
		return $this->new;
	}

	/**
	 * Set the new status
	 *
	 * @param bool $new
	 */
	public function setNew($new) {
		$this->new = (bool) $new;
	}

	/**
	 * Create a new row with the current values
	 *
	 * @return mixed The last inserted id
	 */
	public function insert() {
		$values = array_intersect_key(array_merge($this->getValues(db_row::VALUESMODE_FLAT, db_row::VALUESFILTER_NORMDB), $this->getChangesTable()), $this->getTable()->getField());
		unset($values[$this->getTable()->getIdent()]);
		$id = $this->getTable()->insert($values);
		$this->set($this->getTable()->getIdent(), $id);
		$this->setNew(false);
		$this->saveRelated();
		$this->getTable()->clearCache();
		return $id;
	}

	/**
	 * Save the row in the database
	 *
	 * @return bool|mixed True if successful or no changes was done|mixed if new and inserted, will be last insert id
	 */
	public function save() {
		if ($this->isNew())
			return $this->insert();

		if (!$this->hasChange())
			return true;

		if ($changesTable = $this->getChangesTable())
			$this->getTable()->update($changesTable, $this->whereClause());

		$this->saveRelated();
		$this->getTable()->clearCache();

		return true;
	}

	/**
	 * Save the related values if need
	 *
	 * @throws nException if is new
	 */
	public function saveRelated() {
		if ($this->isNew())
			throw new nException('db_row::saveRelated: try to save related for a new row');

		$changes = $this->getChangesOther();
		foreach($this->getTable()->getRelated() as $r) {
			if (array_key_exists($r['tableLink'], $changes)) {
				$values = array(
					$r['fk1']['name'] => $this->getId()
				);
				$r['tableObj']->delete($values);
				$hasFields = isset($r['fields']) && count($r['fields']);
				if (($tmp = $changes[$r['tableLink']]) && is_array($tmp)) {
					foreach($tmp as $t) {
						$curValues = $values;
						if ($hasFields) {
							foreach($t as $k=>$v) {
								if ($k == $this->getDb()->getKeyConfig('relatedValue'))
									$curValues[$r['fk2']['name']] = $v;
								else
									$curValues[$k] = $v;
							}
						} else
							$curValues = array_merge($values, array($r['fk2']['name'] => $t));
						$r['tableObj']->insert($curValues);
					}
				}
			}
		}
	}

	/**
	 * Delete the current row
	 *
	 * @return bool True if successful
	 */
	public function delete() {
		foreach($this->getTable()->getRelated() as $related) {
			$related['tableObj']->delete(array(
				$related['fk1']['name']=>$this->getId()
			));
		}
		$nb = $this->getTable()->delete($this->whereClause());
		return ($nb > 0);
	}

	/**
	 * Return the current id
	 *
	 * @return mixed
	 */
	public function getId() {
		return $this->get($this->getTable()->getIdent());
	}

	/**
	 * Clear all changes
	 */
	public function clear() {
		$this->changes = array();
	}

	/**
	 * Check if a key exists in the current row
	 *
	 * @return bool
	 */
	public function keyExists($key) {
		return in_array($key, $this->getTable()->getCols());
	}

	/**
	 * Get a value
	 *
	 * @param string $key Fieldname
	 * @param string $mode Mode to retrieve the value (flat or flatReal)
	 * @return mixed The value
	 */
	public function get($key, $mode = db_row::VALUESMODE_FLAT) {
		if (db::isI18nName($key))
			return $this->getI18n(db::unI18nName($key), $mode);

		if ($this->keyExists($key)) {
			if ($mode == db_row::VALUESMODE_FLAT && $this->hasChange($key))
				$val = $this->changes[$key];
			else
				$val = $this->cfg->getInArray('data', $key);
			
			if (!$this->getTable()->getCfg()->autoJoin && $this->getTable()->isLinked($key) && $mode == db_row::VALUESMODE_FLATREAL) {
				$linkedObj = $this->getLinked($key, true);
				return $linkedObj->get(substr($key, strlen($linkedObj->getTable()->getName())+1));
			}
			
			return $this->getTable()->getField($key, 'htmlOut') ? utils::htmlOut($val) : $val;
		} else if ($val = $this->cfg->getInArray('data', $key)) {
			return $val;
		} else if ($this->getTable()->isRelated($key)) {
			$key = $this->getTable()->getRelatedTableName($key);
			$values = $this->getValues($mode);
			if (isset($values[$key]))
				return $values[$key];
			
			if (!$this->getTable()->getCfg()->autoJoin && ($mode == db_row::VALUESMODE_FLAT || $mode == db_row::VALUESMODE_FLATREAL)) {
				$tmp = $this->getRelated($key);
				if (count($tmp)) {
					$v = $this->getTable()->getRelated($key);
					$ret = array();
					$fields = explode(',', $v['fk2']['link']['fields']);
					$i18nFields = explode(',', $v['fk2']['link']['i18nFields']);
					array_walk($i18nFields, create_function('&$v', '$v = "'.db::getCfg('i18n').'".$v;'));
					$fields = array_filter(array_merge($fields, $i18nFields));
					
					$hasFields = isset($v['fields']) && count($v['fields']);
					
					foreach($tmp as $vv) {
						if ($mode == db_row::VALUESMODE_FLAT) {
							if ($hasFields) {
								$curVal = array(
									db::getCfg('relatedValue')=>$vv->get($v['fk2']['link']['ident'])
								);
								foreach($v['fields'] as $kF=>$vF) {
									$curVal[$kF] = $vv->get($kF);
								}
								$ret[] = $curVal;
							} else {
								$ret[] = $vv->get($v['fk2']['link']['ident']);
							}
						} else {
							$tmp2 = array();
							foreach($fields as $f) {
								if ($vv->get($f))
									$tmp2[] = $vv->get($f);
							}
							$ret[] = utils::htmlOut(implode($v['fk2']['link']['sep'], $tmp2));
						}
					}
					if (count($ret))
						return $ret;
				}
			}
		}
		return null;
	}

	/**
	 * Get a i18n value
	 *
	 * @param string $key Fieldname
	 * @param string $mode Mode to retrieve the value, only used for related (flat or flatReal)
	 * @return mixed The value
	 */
	abstract public function getI18n($key, $mode = db_row::VALUESMODE_FLAT, $lang = null);

	/**
	 * Get the i18n values, indexed by lang
	 * 
	 * @return array
	 */
	abstract public function getI18nValues();

	/**
	 * Get the values in an array
	 *
	 * @param string $mode Return mode (data, flat, flatNoRelated, flatReal, flatRealNoRelated)
	 * @param string $filterResults Filters results to apply (not used for data mode)
	 * @return array
	 */
	public function getValues($mode = db_row::VALUESMODE_DATA, $filterResults = db_row::VALUESFILTER_COLS) {
		switch ($mode) {
			case db_row::VALUESMODE_FLAT:
			case db_row::VALUESMODE_FLAT_NORELATED:
				$data = array_merge($this->cfg->data ? $this->cfg->data : array(), $this->getChanges());
				$tmp = $this->getTable()->getCols();

				if ($mode == db_row::VALUESMODE_FLAT) {
					$linked = $this->getTable()->getLinked();
					if (is_array($linked)) {
						foreach($linked as $k=>$v) {
							$tmp[] = $k;
							if (array_key_exists($key = $k.'_'.$v['ident'], $data))
								$data[$k] = $data[$k.'_'.$v['ident']];
						}
					}
					if (array_key_exists('related', $data) && is_array($data['related'])) {
						foreach($this->getTable()->getRelated() as $k=>$v) {
							$tmp[] = $k;
							$data[$k] = array();
							$hasFields = isset($v['fields']) && count($v['fields']);
							if (isset($data['related'][$v['fk2']['link']['table']]) && (is_array($data['related'][$v['fk2']['link']['table']]) || $data['related'][$v['fk2']['link']['table']] instanceof db_rowset)) {
								foreach($data['related'][$v['fk2']['link']['table']] as $vv) {
									if ($hasFields) {
										$curVal = array(
											db::getCfg('relatedValue')=>$vv[$v['fk2']['link']['ident']]
										);
										foreach($v['fields'] as $kF=>$vF) {
											$curVal[$kF] = $vv[$kF];
										}
										$data[$k][] = $curVal;
									} else {
										$data[$k][] = $vv[$v['fk2']['link']['ident']];
									}
								}
							}
						}
					}
				}
				
				if ($filterResults == db_row::VALUESFILTER_COLS)
					return array_intersect_key($data, array_flip($tmp));
				else if ($filterResults == db_row::VALUESFILTER_NORMDB)
					return $this->normalizeValues($data);
				return $data;
				break;
			case db_row::VALUESMODE_FLATREAL:
			case db_row::VALUESMODE_FLATREAL_NORELATED:
				$data = array_merge($this->cfg->data, $this->getChanges());
				$tmp = $this->getTable()->getCols();

				if ($mode == db_row::VALUESMODE_FLATREAL && array_key_exists('related', $data)) {
					foreach($this->getTable()->getRelated() as $k=>$v) {
						$tmp[] = $k;
						$data[$k] = array();
						$fields = explode(',', $v['fk2']['link']['fields']);
						$i18nFields = explode(',', $v['fk2']['link']['i18nFields']);
						array_walk($fields, create_function('&$v', '$v = "'.$v['fk2']['link']['table'].'_".$v;'));
						array_walk($i18nFields, create_function('&$v', '$v = "'.$v['fk2']['link']['table'].'_'.db::getCfg('i18n').'".$v;'));
						$fields = array_merge($fields, $i18nFields);
						foreach($data['related'][$v['fk2']['link']['table']] as $vv) {
							$tmp2 = array();
							foreach($fields as $f)
								if (array_key_exists($f, $vv))
									$tmp2[] = $vv[$f];
							$data[$k][] = implode($v['fk2']['link']['sep'], $tmp2);
						}
						$data[$k] = utils::htmlOut($data[$k]);
					}
				}
				
				if ($filterResults == db_row::VALUESFILTER_COLS)
					return array_intersect_key($data, array_flip($tmp));
				else if ($filterResults == db_row::VALUESFILTER_NORMDB)
					return $this->normalizeValues($data);
				return $data;
				break;
			case db_row::VALUESMODE_DATA:
			default:
				return $this->cfg->data;
				break;
		}
	}
	
	/**
	 * Normalize data to be used in database
	 *
	 * @param array $data
	 * @return array
	 */
	public function normalizeValues(array $data) {
		return $data;
	}

	/**
	 * Get only one value, using the getValues function
	 *
	 * @param string $name Fieldname
	 * @param string $mode Mode used to retrieve data
	 * @return mixed|null The value found or null
	 * @see getValues
	 */
	public function getInValues($name = null, $mode = db_row::VALUESMODE_FLATREAL) {
		$tmp = $this->getValues($mode);
		if(array_key_exists($name, $tmp))
			return $tmp[$name];
		return null;
	}

	/**
	 * Set a value
	 *
	 * @param string $key Fieldname
	 * @param mixed $value Value
	 * @param bool $force Indicates if the value should be replaced even if it's the same
	 * @throws nException If the key doesn't exist
	 */
	public function set($key, $value, $force = false) {
		if ($key == db::getCfg('i18n'))
			return $this->setI18n($value, $force);

		$field = $this->getTable()->getField($key);
		$fct = null;

		if (isset($field['comment']) && is_array($field['comment'])) {
			foreach($field['comment'] as $k=>$v) {
				if (!is_array($v) && strpos($v, 'fct:') === 0) {
					$fct = substr($v, 4);
					break;
				}
			}
		}
		if (!is_null($fct) && function_exists($fct))
			$value = $fct($value);
		if ($force || $this->get($key) != $value)
			$this->changes[$key] = $value;
	}

	/**
	 * Set i18n values
	 *
	 * @param array $values Values
	 * @param bool $force Indicates if the value should be replaced even if it's the same
	 * @param string|null $lg Lang
	 */
	abstract public function setI18n(array $values, $force = false, $lg = null);

	/**
	 * Set a values array
	 *
	 * @param array $values
	 * @param bool $force Indicates if the value should be replaced even if it's the same
	 */
	public function setValues(array $values, $force = false) {
		foreach($values as $k=>$v)
			$this->set($k, $v, $force);
	}

	/**
	 * Get the changes for the table or only one field
	 *
	 * @param null|string $name Field Name or null
	 * @return array|mixed|null
	 */
	public function getChanges($name = null) {
		if (is_null($name))
			return $this->changes;

		if ($this->hasChange($name))
			return $this->changes[$name];
		return null;
	}

	/**
	 * Get the changes in the table only
	 *
	 * @return array
	 */
	public function getChangesTable() {
		return array_intersect_key($this->getChanges(), $this->getTable()->getField());
	}

	/**
	 * Get the changes for the related table
	 *
	 * @return array
	 */
	public function getChangesOther() {
		return array_diff_key($this->getChanges(), array_merge($this->getTable()->getField(), array(db::getCfg('i18n')=>true)));
	}

	/**
	 * Check if the table has a change or only one field
	 *
	 * @param null|string $name Field Name or null
	 * @return bool
	 */
	public function hasChange($name = null) {
		if (is_null($name))
			return !empty($this->changes) || $this->hasChange(db::getCfg('i18n'));
		else if ($name == db::getCfg('i18n')) {
			$hasChange = false;
			foreach($this->i18nRows as $r)
				$hasChange = $hasChange || $r->hasChange();
			return $hasChange;
		}
		return array_key_exists($name, $this->changes);
	}

	/**
	 * Get a linked row
	 *
	 * @param string $field Field Name
	 * @param bool $reload indicate if the row should be reloaded
	 * @return db_row|null
	 */
	public function getLinked($field = null, $reload = false) {
		if ($this->getTable()->isLinked($field)) {
			if (!array_key_exists($field, $this->linked)) {
				$data = array();
				if ($val = $this->get($field, db_row::VALUESMODE_FLAT)) {
					$tmp = $this->getTable()->getLinked($field);
					$data[$tmp['ident']] = $val;
				} else if ($val = $this->get($field, db_row::VALUESMODE_FLATREAL)) {
					$tmp = $this->getTable()->getLinked($field);
					$data[$tmp['ident']] = $val;
				}
				$this->linked[$field] = $this->getTable()->getLinkedTableRow($field, $data);
			}
			if ($reload && array_key_exists($field, $this->linked) && !is_null($this->linked[$field]))
				$this->linked[$field]->reload(false);
			return $this->linked[$field];
		}
		return null;
	}

	/**
	 * Set new linked values
	 *
	 * @param array|db_row $linked array of db_row or values of array OR db_row or array of values should be provide $field
	 * @param string|null $field Field Name (null if array set)
	 */
	public function setLinked($linked, $field = null) {
		if (!is_null($field)) {
			if ($this->getTable()->isLinked($field)) {
				if ($linked instanceof db_row)
					$this->linked[$field] = $linked;
				else
					$this->linked[$field] = $this->getTable()->getLinkedTableRow($field, $linked);
			}
		} else {
			foreach($linked as $f=>$v)
				$this->setLinked($v, $f);
		}
	}

	/**
	 * Get the related rows
	 *
	 * @param string Related Name
	 * @return db_rowset
	 */
	abstract public function getRelated($name);

	/**
	 * Set new linked values
	 *
	 * @param array|db_row $related array of db_row or values of array OR db_row or array of values should be provide $name
	 * @param string|null $name Table Name (null if array set)
	 */
	abstract public function setRelated($related, $name = null);
	
	/**
	 * Construct Where clause regarding the id
	 *
	 * @return mixed
	 */
	abstract public function whereClause();

	/**
	 * Get the value of a field around the row
	 *
	 * @param array $prm Parameter with key:
	 * - string field: Fieldname on which the comparison should be done
	 * - string returnId: True to return id instead of field
	 * - string where: Where clause to filter results
	 * - boolean asRow: Indicates if the result should be retrieved as db_row object or simple value. Should be set to true only when using ident
	 * @return array With 2 indexes; 0 -> field value of the previous row (or null), 1 for the next one
	 */
	abstract public function getAround(array $prm = array());

	public function __call($name, $prm) {
		if (strpos($name, 'get') === 0 || strpos($name, 'set') === 0) {
			$tblName = strtolower(substr($name, 3, 1)).substr($name, 4);
			$found = false;
			$linked = $this->getTable()->getLinked();
			if (is_array($linked)) {
				foreach(array_keys($linked) as $v) {
					if (strpos($v, $tblName.'_') === 0) {
						$name = $v;
						$found = true;
						break;
					}
				}
			}
			if (!$found) {
				$tblName = $this->getTable()->getName().'_'.substr($tblName, 0, -1);
				$related = $this->getTable()->getRelated();
				if (is_array($related)) {
					foreach(array_keys($related) as $v) {
						if ($v == $tblName) {
							$name = $v;
							$found = true;
							break;
						}
					}
				}
			}
		}
		if ($this->getTable()->isLinked($name)) {
			if (empty($prm) || is_bool($prm[0])) {
				return $this->getLinked($name, isset($prm[0]) ? $prm[0] : false);
			} else {
				return $this->setLinked($prm[0], $name);
			}
		} else if ($this->getTable()->isRelated($name)) {
			if (empty($prm)) {
				return $this->getRelated($name);
			} else {
				return $this->setRelated($prm[0], $name);
			}
		}
	}
	
	/**
	 * Check if an index exists.
	 * Required by interface ArrayAccess
	 *
	 * @param string $offset
	 * @return bool
	 */
	public function offsetExists($offset) {
		return !is_null($this->getTable()->getField($offset));
	}

	/**
	 * Get a value.
	 * Required by interface ArrayAccess
	 *
	 * @param string $offset
	 * @return mixed
	 */
	public function offsetGet($offset) {
		return $this->get($offset);
	}

	/**
	 * Set a value.
	 * Required by interface ArrayAccess
	 *
	 * @param string $offset
	 * @param db_row $value
	 */
	public function offsetSet($offset, $value) {
		$this->set($offset, $value);
	}
	
	/**
	 * Remove an element.
	 * Required by interface ArrayAccess
	 *
	 * @param string $offset
	 */
	public function offsetUnset($offset) {
		$this->set($offset, null);
	}

	public function __get($name) {
		return $this->get($name);
	}

	public function __set($name, $val) {
		return $this->set($name, $val);
	}

	public function __toString() {
		return $this->getTable()->getName().'-'.$this->getId();
	}

}