<?php
/**
 * @author Cedric Nirousset <cedric@nyrodev.com>
 * @version 0.2
 * @package nyro
 */
/**
 * Interface for db classes
 */
class db_row extends object {

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

	/**
	 * i18n rows parsed
	 *
	 * @var array
	 */
	protected $i18nRows = array();

	protected function afterInit() {
		if (get_class($this) == 'db_row')
			$this->cfg->overload('db_row_'.$this->cfg->name);

		$this->table = $this->cfg->table;

		if (!empty($this->cfg->data)) {
			$data = $this->cfg->data;

			if (array_key_exists($this->table->getIdent(), $data) && $data[$this->table->getIdent()])
				$this->setNew(false);
			else {
				$primary = $this->table->getPrimary();
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
		} else if (!empty($this->cfg->findId)) {
			$tmp = $this->table->find($this->cfg->findId);
			$this->cfg->data = $tmp->getValues('data');
			$this->setNew(false);
			unset($tmp);
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
	public function getForm($showFields=null, array $formParam = array(), $passConfirm=true) {
		$form = factory::get('form_db', array_merge($formParam, array(
			'table'=>$this->getTable()
		)));
		/* @var $form form_db */
		foreach($this->table->getField() as $f) {
			if ($f['name'] != $this->table->getIdent() && !$f['auto'] &&
					(empty($showFields) || in_array($f['name'], $showFields))) {
				$f['label'] = $this->table->getLabel($f['name']);
				$f['link'] = $this->table->getLinked($f['name']);
				$f['value'] = $this->get($f['name']);
				$obj = $form->addFromField($f);
				if ($passConfirm && $obj instanceof form_password) {
					$name = $f['name'];
					$f['name'].= 'Confirm';
					$f['label'] = $this->table->getLabel($f['name']);
					$form->addFromField($f)->addRule('equal', $obj);
					$form->addNotValue($f['name']);
					if (!$this->isNew()) {
						// remove the required validation for the 2 password fields but keep the equal
						$form->get($name)->getValid()->delRule('required');
						$form->get($f['name'])->getValid()->delRule('required');
					}
				}
			}
		}

		foreach($this->table->getRelated() as $t=>$r) {
			if (empty($showFields) || in_array($r['tableLink'], $showFields)) {
				$r['name'] = $r['tableLink'];
				$r['label'] = $this->table->getLabel($r['table']);
				$r['valid'] = false;
				$form->addFromRelated($r);
			}
		}

		$form->setValues($this->getValues('flat'));

		$i18nFields = $this->table->getI18nFields();
		$i18nFieldsT = array();
		foreach($i18nFields as $f) {
			$i18nFieldsT[] = $f['name'];
			$f['label'] = $this->table->getI18nTable()->getLabel($f['name']);
			$form->addFromField($f, true);
		}

		$form->finalize();

		if ($i18nRows = $this->getI18nRows()) {
			$tmp = array();
			$primary = $this->table->getI18nTable()->getPrimary();
			foreach($i18nRows as $r) {
				$lang = $r->get($primary[1]);
				foreach($r->getValues() as $k=>$v) {
					$tmp[db::getCfg('i18n').'['.$lang.']['.$k.']'] = $v;
				}
			}
			$form->setValues($tmp);
		}

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
		$values = array_merge($this->getValues('flat'), $this->getChangesTable());
		unset($values[$this->table->getIdent()]);
		$id = $this->table->insert($values);
		$this->set($this->table->getIdent(), $id);
		$this->setNew(false);
		$this->saveRelated();
		$this->saveI18n();
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
			$this->table->update($changesTable, $this->whereClause());

		$this->saveRelated();
		$this->saveI18n();

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
		foreach($this->table->getRelated() as $r) {
			if (array_key_exists($r['tableLink'], $changes)) {
				$values = array(
					$r['fk1']['name'] => $this->getId()
				);
				$r['tableObj']->delete($values);
				if ($tmp = $changes[$r['tableLink']]) {
					foreach($tmp as $t)
						$r['tableObj']->insert(array_merge($values, array($r['fk2']['name'] => $t)));
				}
			}
		}
	}

	/**
	 * Save the i18n values
	 *
	 * @throws nException if is new
	 */
	public function saveI18n() {
		if ($this->isNew())
			throw new nException('db_row::saveI18n: try to save i18n for a new row');

		if (!empty($this->i18nRows)) {
			list($fkId, $lang) = ($this->table->getI18nTable()->getPrimary());
			foreach($this->i18nRows as $r) {
				if ($r->isNew())
					$r->set($fkId, $this->getId());
				$r->save();
			}
		}
	}

	/**
	 * Delete the current row
	 *
	 * @return bool True if successful
	 */
	public function delete() {
		foreach($this->table->getRelated() as $related) {
			$related['tableObj']->delete(array(
				$related['fk1']['name']=>$this->getId()
			));
		}
		$nb = $this->table->delete($this->whereClause());
		return ($nb > 0);
	}

	/**
	 * Return the current id
	 *
	 * @return mixed
	 */
	public function getId() {
		return $this->get($this->table->getIdent());
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
		return in_array($key, $this->table->getCols());
	}

	/**
	 * Get a value
	 *
	 * @param string $key Fieldname
	 * @param string $mode Mode to retrieve the value, only used for related (flat or flatReal)
	 * @return mixed The value
	 */
	public function get($key, $mode='flat') {
		if (db::isI18nName($key))
			return $this->getI18n(db::unI18nName($key), $mode);

		if ($this->keyExists($key)) {
			if ($this->hasChange($key))
				$val = $this->changes[$key];
			else
				$val = $this->cfg->getInarray('data', $key);
			return $this->table->getField($key, 'htmlOut')? utils::htmlOut($val) : $val;
		} else if ($val = $this->cfg->getInarray('data', $key)) {
			return $val;
		} else if ($this->table->isRelated($key)) {
			$key = $this->table->getRelatedTableName($key);
			$values = $this->getValues($mode);
			return array_key_exists($key, $values)? $values[$key] : null;
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
	public function getI18n($key, $mode='flat', $lang=null) {
		return $this->getI18nRow($lang)->get($key, $mode);
	}

	/**
	 * Get a i18nRow
	 *
	 * @param string $lang Lang needed (if null, the current will be used or a new row will be created)
	 * @return db_row
	 */
	public function getI18nRow($lang) {
		if (is_null($lang))
			$lang = request::get('lang');
		if (!array_key_exists($lang, $this->i18nRows)) {
			$primary = $this->table->getI18nTable()->getPrimary();
			$this->i18nRows[$lang] = $this->table->getI18nTable()->getRow();
			$this->i18nRows[$lang]->setValues(array(
				$primary[0]=>$this->getId(),
				$primary[1]=>$lang
			));
		}
		return $this->i18nRows[$lang];
	}

	/**
	 * Get the instancied i18n rows
	 *
	 * @return array
	 */
	public function getI18nRows() {
		return $this->i18nRows;
	}

	/**
	 * Get the values in an array
	 *
	 * @param string $mode Return mode (data, flat, flatNoRelated, flatReal, flatRealNoRelated)
	 * @return array
	 */
	public function getValues($mode='data') {
		switch ($mode) {
			case 'flat':
			case 'flatNoRelated':
				$data = array_merge($this->cfg->data, $this->getChanges());
				$tmp = $this->getTable()->getCols();

				if ($mode == 'flat') {
					foreach($this->table->getLinked() as $k=>$v) {
						$tmp[] = $k;
						if (array_key_exists($key = $k.'_'.$v['ident'], $data))
							$data[$k] = $data[$k.'_'.$v['ident']];
					}
					if (array_key_exists('related', $data)) {
						foreach($this->table->getRelated() as $k=>$v) {
							$tmp[] = $k;
							$data[$k] = array();
							foreach($data['related'][$v['fk2']['link']['table']] as $vv) {
								$data[$k][] = $vv[$v['fk2']['link']['ident']];
							}
						}
					}
				}
				return array_intersect_key($data, array_flip($tmp));
				break;
			case 'flatReal':
			case 'flatRealNoRelated':
				$data = array_merge($this->cfg->data, $this->getChanges());
				$tmp = $this->getTable()->getCols();

				if ($mode == 'flatReal' && array_key_exists('related', $data)) {
					foreach($this->table->getRelated() as $k=>$v) {

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
								$tmp2[] = $vv[$f];
							$data[$k][] = implode($v['fk2']['link']['sep'], $tmp2);
						}
					}
					$data[$k] = utils::htmlOut($data[$k]);
				}

				return array_intersect_key($data, array_flip($tmp));
				break;
			case 'data':
			default:
				return $this->cfg->data;
				break;
		}
	}

	/**
	 * Get only one value, using the getValues function
	 *
	 * @param string $name Fieldname
	 * @param string $mode Mode used to retrieve data
	 * @return mixed|null The value found or null
	 * @see getValues
	 */
	public function getInValues($name=null, $mode='flatReal') {
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
	 * @throws nException If the key doesn't exist
	 */
	public function set($key, $value) {
		if ($key == db::getCfg('i18n'))
			return $this->setI18n($value);

		$field = $this->table->getField($key);
		$fct = null;
		
		if (is_array($field['comment'])) {
			foreach($field['comment'] as $k=>$v) {
				if (!is_array($v) && strpos($v, 'fct:') === 0) {
					$fct = substr($v, 4);
					break;
				}
			}
		}
		if (!is_null($fct) && function_exists($fct))
			$value = $fct($value);
		if ($this->get($key) !== $value)
			$this->changes[$key] = $value;
	}

	/**
	 * Set i18n values
	 *
	 * @param array $values Values
	 * @param string|null $lg Lang
	 */
	public function setI18n(array $values, $lg=null) {
		if (!is_null($lg)) {
			$this->getI18nRow($lg)->setValues($values);
		} else {
			foreach($values as $lg=>$val)
				$this->setI18n($val, $lg);
		}
	}

	/**
	 * Set a values array
	 *
	 * @param array $values
	 */
	public function setValues(array $values) {
		foreach($values as $k=>$v)
			$this->set($k, $v);
	}

	/**
	 * Get the changes for the table or only one field
	 *
	 * @param null|string $name Field Name or null
	 * @return array|mixed|null
	 */
	public function getChanges($name=null) {
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
		return array_intersect_key($this->getChanges(), $this->table->getField());
	}

	/**
	 * Get the changes for the related table
	 *
	 * @return array
	 */
	public function getChangesOther() {
		return array_diff_key($this->getChanges(), array_merge($this->table->getField(), array(db::getCfg('i18n')=>true)));
	}

	/**
	 * Get the i18n changes
	 *
	 * @return array
	 */
	public function getChangesI18n() {
		$tmp = $this->getChanges();
		if (array_key_exists(db::getCfg('i18n'), $tmp))
			return $tmp[db::getCfg('i18n')];
		return array();
	}

	/**
	 * Check if the table has a change or only one field
	 *
	 * @param null|string $name Field Name or null
	 * @return bool
	 */
	public function hasChange($name=null) {
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
	 * @return db_row|null
	 */
	public function getLinked($field=null) {
		if ($this->table->isLinked($field)) {
			if (!array_key_exists($field, $this->linked))
				$this->linked[$field] = $this->table->getLinkedTableRow($field);
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
	public function setLinked($linked, $field=null) {
		if (!is_null($field)) {
			if ($this->table->isLinked($field)) {
				if ($linked instanceof db_row)
					$this->linked[$field] = $linked;
				else
					$this->linked[$field] = $this->table->getLinkedTableRow($field, $linked);
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
	public function getRelated($name) {
		$related = $this->table->getRelated($this->table->getRelatedTableName($name));
		return $related['tableObj']->getLinkedTable('module_nom')->select(array(
			'where'=>$this->getWhere(array(
				'clauses'=>factory::get('db_whereClause', array(
					'name'=>$related['fk2']['link']['table'].'.'.$related['fk2']['link']['ident'],
					'in'=>$this->getDb()->selectQuery(array(
						'fields'=>$related['fk2']['name'],
						'table'=>$related['tableLink'],
						'where'=>$related['fk1']['name'].'='.$this->getId()
					))
				))
			))
		));
	}
	
	/**
	 * Set new linked values
	 *
	 * @param array|db_row $related array of db_row or values of array OR db_row or array of values should be provide $name
	 * @param string|null $name Table Name (null if array set)
	 */
	public function setRelated($related, $name=null) {
		if (!is_null($name)) {
			if ($this->table->getI18nTable() && $name == $this->table->getI18nTable()->getName()) {
				$primary = $this->table->getI18nTable()->getPrimary();
				foreach($related as $r) {
					$this->i18nRows[$r[$primary[1]]] = $this->table->getI18nTable()->getRow(
						array_merge($r, array($primary[0]=>$this->getId())));
				}
			} else {
				$name = $this->table->getRelatedTableName($name);
				if ($this->table->isRelated($name)) {
					if (!array_key_exists($name, $this->related))
						$this->related[$name] = array();

					if ($related instanceof db_row)
						$this->related[$name][] = $related;
					else {
						foreach($related as $v) {
							if ($v instanceof db_row)
								$this->related[$name][] = $v;
							else
								$this->related[$name][] = $this->table->getRelatedTableRow($name, $v);
						}
					}
				}
			}
		} else {
			foreach($related as $t=>$v)
				$this->setRelated($v, $t);
		}
	}

	/**
	 * Construct Where clause regarding the id
	 *
	 * @return string
	 */
	protected function whereClause() {
		$primary = $this->table->getPrimary();
		$where = array();
		foreach($primary as $p) {
			$where[] = $this->table->getName().'.'.$p.='="'.$this->get($p).'"';
		}
		return implode(' AND ', $where);
	}

	public function __call($name, $prm) {
		if ($this->table->isLinked($name)) {
			if (empty($prm)) {
				return $this->getLinked($name);
			} else {
				return $this->setLinked($prm[0], $name);
			}
		} else if ($this->table->isRelated($name)) {
			if (empty($prm)) {
				return $this->getRelated($name);
			} else {
				return $this->setLinked($prm[0], $name);
			}
		}
	}

	public function __get($name) {
		return $this->get($name);
	}

	public function __set($name, $val) {
		return $this->set($name, $val);
	}
}
