<?php
/**
 * @author Cédric Nirousset <cedric@nyrodev.com>
 * @version 0.2
 * @package nyroFwk
 */
/**
 * Database Table interface
 */
abstract class db_table extends object {

	/**
	 * Raw table name
	 *
	 * @var string
	 */
	protected $rawName;
	
	/**
	 * Fields informations
	 *
	 * @var array
	 */
	protected $fields = array();

	/**
	 * Columns
	 *
	 * @var array
	 */
	protected $cols = array();

	/**
	 * Linked tables (in the fileds list)
	 *
	 * @var array
	 */
	protected $linkedTables;

	/**
	 * Linked table names
	 *
	 * @var array
	 */
	protected $linkedTableNames;

	/**
	 * Related tables (with an other table)
	 *
	 * @var array
	 */
	protected $relatedTables;

	/**
	 * Targeting tables
	 *
	 * @var array
	 */
	protected $targetingTables;

	protected function afterInit() {
		$defaultClass = 'db_table_'.$this->cfg->name;
		if (get_class($this) != $defaultClass)
			$this->cfg->overload($defaultClass);

		$this->rawName = $this->getDb()->prefixTable($this->cfg->name);
		
		$this->_initFields();
		$this->_initIdent();
		$this->_initLinkedTables();
		$this->_initRelatedTables();
		$this->_initLabels();
	}

	/**
	 * Indicate if the thable has a i18n table
	 *
	 * @return bool
	 */
	abstract public function hasI18n();
	
	/**
	 * Get the i18n fields information
	 *
	 * @param string|null $field Field name. If null, the whole field array will be returned
	 * @param string|null $keyVal Value to retrieve directly
	 * @return array|null
	 */
	abstract public function geti18nField($field = null, $keyVal = null);
	
	/**
	 * Get the i18nFields
	 *
	 * @return array
	 */
	abstract public function getI18nFields();

	/**
	 * Initialize the fields
	 */
	protected function _initFields() {
		$this->fields = $this->getDb()->fields($this->cfg->name);
		$this->cols = array_keys($this->fields);

		if (array_key_exists($this->cfg->inserted, $this->fields))
			$this->fields[$this->cfg->inserted]['auto'] = true;

		if (array_key_exists($this->cfg->updated, $this->fields))
			$this->fields[$this->cfg->updated]['auto'] = true;

		if (array_key_exists($this->cfg->deleted, $this->fields))
			$this->fields[$this->cfg->deleted]['auto'] = true;
		
		if ($this->cfg->check('fields') && is_array($this->cfg->fields) && !empty($this->cfg->fields)) {
			factory::mergeCfg($this->fields, array_intersect_key($this->cfg->fields, $this->fields));
			foreach($this->fields as &$f) {
				if (is_array($f['default']))
					$f['default'] = array_key_exists(1, $f['default']) ? $f['default'][1] : $f['default'][0];
			}
		}
	}

	/**
	 * Initialize the primary and ident information, if needed
	 */
	abstract protected function _initIdent();

	/**
	 * Initialize the linked tables, if needed
	 */
	protected function _initLinkedTables() {
		if ($this->linkedTables === null && !$this->isI18n()) {
			$this->linkedTables = array();
			$this->linkedTableNames = array();
			foreach($this->cols as $c) {
				if (strpos($c, '_') && $this->fields[$c]['type'] != 'file') {
					$tmp = explode('_', $c);
					$num = is_numeric($tmp[0])? array_shift($tmp) : 1;
					$table = array_shift($tmp);
					$fields = array();
					$i18nFields = array();
					foreach($tmp as $t) {
						if (db::isI18nName($t))
							$i18nFields[] = db::unI18nName($t);
						else
							$fields[] = $t;
					}
					$list = array();
					$sep = $this->cfg->defSep;
					$nbFieldGr = 0;
					$sepGr = null;
					$more = array();
					if (!empty($this->fields[$c]['comment'])) {
						$com = $this->fields[$c]['comment'];
						foreach($com as $kk=>$cc) {
							if (!is_numeric($kk)) {
								$more[$kk] = $cc;
								unset($com[$kk]);
							}
						}
						$sep = !empty($com[0])? array_shift($com) : $this->cfg->defSep;
						$list = array(array_shift($com));
						$nbFieldGr = array_shift($com);
						$sepGr = array_shift($com);
					}
					$this->linkedTableNames[$c] = $table;
					$this->linkedTables[$c] = array_merge(array(
						'field'=>$c,
						'table'=>$table,
						'ident'=>$this->cfg->defId,
						'fields'=>implode(',', $fields),
						'i18nFields'=>implode(',', $i18nFields),
						'label'=>ucfirst($table),
						'num'=>$num,
						'sep'=>$sep,
						'list'=>$list,
						'nbFieldGr'=>$nbFieldGr,
						'sepGr'=>$sepGr,
						'where'=>null,
					), $more);
				}
			}
		}
		
		if ($this->cfg->check('linked') && is_array($this->cfg->linked) && !empty($this->cfg->linked))
			factory::mergeCfg($this->linkedTables, $this->cfg->linked);
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
	 * Get a where object
	 *
	 * @param array $prm The configuration for the where object
	 * @return db_where
	 */
	public function getWhere(array $prm = array()) {
		return $this->getDb()->getWhere($prm);
	}

	/**
	 * Check if a field name is linked to a table
	 *
	 * @param string $field Field name
	 * @return bool
	 */
	public function isLinked($field) {
		return is_array($this->linkedTables) && array_key_exists($field, $this->linkedTables);
	}

	/**
	 * Get linked info with a table name
	 *
	 * @param string $tablename table name
	 * @return array|null Smae result than @getLinked
	 */
	public function getLinkedTableName($tablename) {
		if (is_array($this->linkedTableNames)) {
			$key = array_search($tablename, $this->linkedTableNames);
			if ($key)
				return $this->getLinked($key);
		}
		return null;
	}

	/**
	 * Get the linked information about a field
	 *
	 * @param string|null $field Field name. If null the whole table will be returned
	 * @return array|null
	 */
	public function getLinked($field = null) {
		if (is_null($field))
			return $this->linkedTables;

		if ($this->isLinked($field))
			return $this->linkedTables[$field];
		return null;
	}

	/**
	 * Get the table object for a link field
	 *
	 * @param string $field Field Name
	 * @return db_table|null
	 */
	public function getLinkedTable($field) {
		if ($link = $this->getLinked($field)) {
			if (!array_key_exists('tableObj', $link))
				$link['tableObject'] = $this->getDb()->getTable($link['table']);
			return $link['tableObject'];
		}
		return null;
	}

	/**
	 * Get a row for a linked table
	 *
	 * @param string $field Field Name
	 * @param array $data The data for overwrite the default value
	 * @return db_row
	 */
	public function getLinkedTableRow($field, array $data = array()) {
		if ($table = $this->getLinkedTable($field))
			return $table->getRow($data, false, array('needReload'=>true));
		return null;
	}

	/**
	 * Initialize the related tables, if needed
	 */
	protected function _initRelatedTables() {
		if (is_null($this->relatedTables)) {
			$this->relatedTables = array();
			$search = $this->rawName.'_';
			$tables = $this->getDb()->getTablesWith(array('start'=>$search));
			foreach($tables as $t) {
				$relatedTable = substr($t, strlen($search));
				$table = $this->getDb()->getTable($t);
				$fields = $table->getField();
				$fk1 = $fk2 = null;
				foreach($fields as $k=>$v) {
					if (is_null($fk1) && strpos($k, $this->rawName) !== false) {
						$fk1 = array_merge($v, array('link'=>$table->getLinked($k)));
						unset($fields[$k]);
					} else if (strpos($k, $relatedTable) !== false) {
						$fk2 = array_merge($v, array('link'=>$table->getLinked($k)));
						unset($fields[$k]);
					}
				}

				$this->relatedTables[$t] = array(
					'tableObj'=>$table,
					'tableLink'=>$t,
					'table'=>$relatedTable,
					'fk1'=>$fk1,
					'fk2'=>$fk2,
					'fields'=>$fields,
				);
			}
		}
		if ($this->cfg->check('related') && is_array($this->relatedTables) && !empty($this->relatedTables))
			factory::mergeCfg($this->relatedTables, $this->cfg->related);
	}

	/**
	 * Check if a table name is ralted to the table
	 *
	 * @param string $name Table name
	 * @return bool
	 */
	public function isRelated($name) {
		return array_key_exists($this->getRelatedTableName($name), $this->relatedTables);
	}

	/**
	 * Get the related table
	 *
	 * @param string $name If need only 1 related information
	 * @return array|null
	 */
	public function getRelated($name = null) {
		if (is_null($name))
			return $this->relatedTables;

		$name = $this->getRelatedTableName($name);
		return $this->isRelated($name)? $this->relatedTables[$name] : null;
	}

	/**
	 * Get a row for a related table
	 *
	 * @param string $name Table Name
	 * @param array $data The data for overwrite the default value
	 * @return db_row
	 */
	public function getRelatedTableRow($name, array $data = array()) {
		if ($table = $this->getRelatedTable($name))
			return $table->getRow($data);
		return null;
	}

	/**
	 * Get the related table object
	 *
	 * @param string $name Related table name
	 * @return db_table
	 */
	public function getRelatedTable($name) {
		if ($related = $this->getRelated($this->getRelatedTableName($name)))
			return $related['tableObj'];
		return null;
	}

	/**
	 * Get the name of the related table
	 *
	 * @param string $name
	 * @param bool $add if True, the prefix will be added if needed. If false, it will removed if needed
	 * @return string
	 */
	public function getRelatedTableName($name, $add = true) {
		$shouldStart = $this->getName().'_';
		$pos = strpos($name, $shouldStart);
		if ($pos !== 0 && $add)
			$name = $shouldStart.$name;
		else if ($pos === 0 && !$add)
			$name = substr($name, strlen($shouldStart));

		return $name;
	}

	/**
	 * Get Targeting table names
	 *
	 * @return array Targeting table names
	 */
	public function getTargetingTables() {
		if (is_null($this->targetingTables)) {
			$tblName = $this->getName();
			if ($this->cfg->cacheEnabled) {
				$cache = $this->getDb()->getCache();
				$cache->get($this->targetingTables, array('id'=>$tblName));
			}
			
			if (is_null($this->targetingTables)) {
				$this->targetingTables = array();
				foreach($this->getDb()->getTables() as $tbl) {
					if ($tbl != $tblName && $this->getDb()->getTable($tbl)->isTargeting($tblName)) {
						$this->targetingTables[] = $tbl;
						$tmpPos = strpos($tbl, $tblName.'_');
						if ($tmpPos === 0)
							$this->targetingTables[] = substr($tbl, strlen($tblName)+1);
					}
				}
				if ($this->cfg->cacheEnabled)
					$cache->save();
			}
		}
		return $this->targetingTables;
	}
	
	/**
	 * Indicates if the table is targeting an other table
	 *
	 * @param strong $tableName Table name to check against
	 * @return boolean 
	 */
	public function isTargeting($tableName) {
		if (strpos($this->getName(), '_') !== false) {
			$tmp = explode('_', $this->getName());
			if ($tmp[0] == $tableName || $tmp[1] == $tableName)
				return true;
		}
		
		if (is_array($this->getLinked())) {
			foreach($this->getLinked() as $linked) {
				if ($linked['table'] == $tableName)
					return true;
			}
		}
		
		if (is_array($this->getRelated())) {
			foreach($this->getRelated() as $related) {
				if ($related['tableObj']->isTargeting($tableName))
					return true;
			}
		}
		
		return false;
	}

	/**
	 * Initialize the labels
	 */
	protected function _initLabels() {
		$labels = array();
		$cfgLabel = $this->cfg->label;
		foreach($this->cols as $c) {
			if (array_key_exists($c, $cfgLabel) && $label = $cfgLabel[$c]) {
				$labels[$c] = utils::htmlOut($label);
				unset($cfgLabel[$c]);
			} else if($this->fields[$c]['type'] == 'file')
				$labels[$c] = ucwords(str_replace('_', ' ', strtolower(substr($c, 0, -5))));
			else {
				$use = $c;
				$pos = strpos($c, '_');
				if ($pos) {
					$start = 0;
					if ($pos == 1) {
						$start = 2;
						$pos = strpos($c, '_', $start) - $start;
					}
					$use = substr($c, $start, $pos);
				}
				$labels[$c] = ucwords(strtolower($use));
			}
			$this->fields[$c]['label'] = $labels[$c];
		}
		foreach($this->relatedTables as $r) {
			if (array_key_exists($r['table'], $cfgLabel) && $label = $cfgLabel[$r['table']]) {
				$labels[$r['table']] = utils::htmlOut($label);
				unset($cfgLabel[$r['table']]);
			} else
				$labels[$r['table']] = ucwords(str_replace('_', ' ', strtolower($r['table'])));
		}
		$this->cfg->label = array_merge($labels, $cfgLabel);
	}

	/**
	 * Get the label for the fields
	 *
	 * @param null|string $field Fieldname or null to retrieve an all of them as an array
	 * @return array|string
	 */
	public function getLabel($field = null) {
		if (db::isI18nName($field))
			return $this->getI18nLabel(db::unI18nName($field));

		if (is_null($field))
			return $this->cfg->label;

		$field = $this->isRelated($field)? $this->getRelatedTableName($field, false) : $field;
		return $this->cfg->getInArray('label', $field);
	}

	/**
	 * Get the label for the i18n fields
	 *
	 * @param null|string $field Fieldname or null to retrieve an all of them as an array
	 * @return array|string
	 */
	abstract public function getI18nLabel($field = null);

	/**
	 * Get the fields information
	 *
	 * @param string|null $field Field name. If null, the whole field array will be returned
	 * @param string|null $keyVal Value to retrieve directly
	 * @return array|null
	 */
	public function getField($field = null, $keyVal = null) {
		if (is_null($field))
			return $this->fields;
		
		if (db::isI18nName($field))
			$this->getI18nField($field, $keyVal);

		$ret = array_key_exists($field, $this->fields) ? $this->fields[$field] : null;
		if (!is_null($keyVal) && is_array($ret) && array_key_exists($keyVal, $ret))
			return $ret[$keyVal];
		return $ret ? $ret : $this->cfg->getInArray('label', $field);
	}

	/**
	 * Get the fields which are file
	 *
	 * @return array|null;
	 */
	public function getFieldFile() {
		$ret = null;
		foreach($this->fields as $f) {
			if ($f['type'] == 'file')
				$ret[] = $f['name'];
		}
		return $ret;
	}

	/**
	 * Get the columns
	 *
	 * @return array
	 */
	public function getCols() {
		return $this->cols;
	}

	/**
	 * Get the table name
	 *
	 * @return string
	 */
	public function getName() {
		return $this->cfg->name;
	}

	/**
	 * Get the raw table name
	 *
	 * @return string
	 */
	public function getRawName() {
		return $this->rawName;
	}

	/**
	 * Get the ident column name
	 *
	 * @return string
	 */
	public function getIdent() {
		return $this->cfg->ident;
	}

	/**
	 * Get the primary columns
	 *
	 * @return array
	 */
	public function getPrimary() {
		return $this->cfg->primary;
	}

	/**
	 * Insert into the table
	 *
	 * @param array $data The values to insert. The key are the identifier
	 * @return mixed the inserted id
	 * @see db_abstract::insert
	 */
	public function insert(array $data) {
		unset($data[$this->getIdent()]);
		$this->dateAutoData($data, 'inserted');
		$this->dateAutoData($data, 'updated');
		$ret = $this->getDb()->insert(array(
			'table'=>$this->rawName,
			'values'=>$data
		));
		if ($ret)
			$this->clearCache();
		return $ret;
	}

	/**
	 * Replace into the table
	 *
	 * @param array $data The values to insert. The key are the identifier
	 * @return mixed the inserted id
	 * @see db_abstract::insert
	 */
	public function replace(array $data) {
		$this->dateAutoData($data, 'inserted');
		$this->dateAutoData($data, 'updated');
		$ret = $this->getDb()->replace(array(
			'table'=>$this->rawName,
			'values'=>$data
		));
		if ($ret)
			$this->clearCache();
		return $ret;
	}

	/**
	 * update data in the table
	 *
	 * @param array $data The values to update. The key are the identifier
	 * @param db_where|array|string $where : The where clause. If array, they are used with AND. (default: none)
	 * @return int Affected rows
	 * @see db_abstract::update
	 */
	public function update(array $data, $where = null) {
		$this->dateAutoData($data, 'updated');
		$ret = $this->getDb()->update(array(
			'table'=>$this->rawName,
			'values'=>$data,
			'where'=>$where
		));
		if ($ret)
			$this->clearCache();
		return $ret;
	}

	/**
	 * Delete data in the table
	 *
	 * @param db_where|array|string $where : The where clause. If array, they are used with AND. (default: none)
	 * @return int Deleted rows
	 * @see db_abstract::delete
	 */
	public function delete($where = null) {
		$ret = 0;
		$data = array();
		$this->dateAutoData($data, 'deleted');
		if (empty($data)) {
			if ($files = $this->getFieldFile()) {
				$rows = $this->select(array('autoJoin'=>false, 'where'=>$where));
				if ($this->cfg->deleteCheckFile) {
					$fields = array();
					foreach($rows as $r) {
						foreach($files as $f) {
							if (!isset($fields[$f.'-'.$r->get($f)])) {
								$form = $r->getForm(array($f));
								$fields[$f.'-'.$r->get($f)] = array(
									'formValue'=>$form->get($f)->getRawValue(),
									'exist'=>$this->count(array('autoJoin'=>false, 'where'=>array($f=>$r->get($f)))),
									'delete'=>0
								);
							}
							$fields[$f.'-'.$r->get($f)]['delete']++;
						}
					}
					foreach($fields as $f) {
						if ($f['exist'] == $f['delete'])
							$f['formValue']->delete();
					}
				} else {
					foreach($rows as $r) {
						$form = $r->getForm($files);
						foreach($files as $f)
							$form->get($f)->getRawValue()->delete();
					}
				}
			}
			$ret = $this->getDb()->delete(array(
				'table'=>$this->rawName,
				'where'=>$where,
				'optim'=>$this->cfg->optimAfterDelete
			));
		} else {
			$ret = $this->getDb()->update(array(
				'table'=>$this->rawName,
				'values'=>$data,
				'where'=>$where,
				'optim'=>$this->cfg->optimAfterDelete
			));
		}
		if ($ret)
			$this->clearCache();
		return $ret;
	}

	/**
	 * Add an automatic field date
	 *
	 * @param array $data Record values; will be modified if needed
	 * @param string $type Type to insert date
	 * @return bool True if the date was added
	 */
	public function dateAutoData(array &$data, $type) {
		if ($fieldName = $this->cfg->get($type)) {
			if (array_key_exists($fieldName, $this->fields) && !array_key_exists($fieldName, $data)) {
				$data[$fieldName] = $this->dateValue($this->fields[$fieldName]['type']);
				return true;
			}
		}
		return false;
	}

	/**
	 * Get a date value regarding a SQL type. If not parsed, time() will be affected
	 *
	 * @param string $type SQL type (date and datetime parsed)
	 * @return mixed The date value
	 */
	protected function dateValue($type) {
		$ret = time();
		switch($type) {
			case 'date':
				$ret = date('Y-m-d');
				break;
			case 'datetime':
				$ret = date('Y-m-d H:i:s');
				break;
		}
		return $ret;
	}

	/**
	 * Search on the table
	 *
	 * @param array $prm Select query configuration. Same parameter than db::select, with more:
	 *  - db_where|string|null filter: Where clause to filter the result. If string, serach to be equal to the identity
	 *  - bool first: Return onlt the first result as a db_row
	 * @return db_rowset|db_row
	 * @see selectQuery, db_abstract::select
	 */
	abstract public function select(array $prm = array());

	/**
	 * Count the number of result
	 *
	 * @param array $prm The initial parameter
	 * @return int
	 */
	abstract public function count(array $prm);

	/**
	 * Find a row by Id or by different value if array
	 *
	 * @param mixed $val Id value or where query
	 * @return db_row
	 */
	public function find($where) {
		return $this->select(array(
			'where'=>$where,
			'first'=>true
		));
	}

	/**
	 * Search text in every text fileds present the table
	 *
	 * @param string $text The text to search
	 * @param db_where|array|string $where Same paramter than select
	 * @return db_rowset
	 * @see select
	 */
	abstract public function findText($text, $filter = null);

	/**
	 * Get the min and the max value for a field.
	 *
	 * @param string $field Fieldname. If null, id is used
	 * @return array With key min and max
	 */
	abstract public function getRange($field = null);

	/**
	 * Clear the cache of selected queries for this table
	 *
	 * @param bool|null $clearTargeting Indifcate if cache of targeting tables should also be cleared. If null, default settings will be used
	 * @return int|bool Number of cache deleted or false
	 */
	public function clearCache($clearTargeting = null) {
		if (is_null($clearTargeting))
			$clearTargeting = $this->cfg->cacheClearTargeting;
		if ($clearTargeting) {
			foreach($this->getTargetingTables() as $tbl) {
				$this->getDb()->getTable($tbl)->clearCache(false);
			}
		}
		if (!$this->cfg->cacheEnabled)
			return false;
		return $this->getDb()->getCache()->delete(array(
			'callFrom'=>'db_table-select',
			'type'=>'get',
			'id'=>$this->getName().'-*'
		));
	}

	/**
	 * Get a row
	 *
	 * @param array $data The data for overwrite the default value
	 * @param bool $withAuto Include auto field
	 * @param array $morePrm Array of configuration for the row
	 * @return db_row
	 */
	public function getRow(array $data = array(), $withAuto = false, array $morePrm = array()) {
		$cols = array_flip($this->cols);
		$data = array_intersect_key($data, $cols);

		foreach($this->fields as &$f) {
			if ((!$f['auto'] || $withAuto) && !array_key_exists($f['name'], $data))
				$data[$f['name']] = $f['default'];
		}

		$prm = array_merge($morePrm, array(
			'data'=>$data
		));
		if (array_key_exists($this->getIdent(), $data) && $data[$this->getIdent()])
			$prm['findId'] = $data[$this->getIdent()];
		return $this->getDb()->getRow($this, $prm);
	}

}