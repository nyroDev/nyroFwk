<?php
/**
 * @author Cédric Nirousset <cedric@nyrodev.com>
 * @version 0.2
 * @package nyroFwk
 */
/**
 * SQL Table interface
 */
class db_table extends object {

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

	/**
	 * i18n table
	 *
	 * @var db_table
	 */
	protected $i18nTable;

	protected function afterInit() {
		if (get_class($this) == 'db_table')
			$this->cfg->overload('db_table_'.$this->cfg->name);
		$this->rawName = $this->cfg->db->prefixTable($this->cfg->name);
		$this->_initi18n();
		$this->_initFields();
		$this->_initIdent();
		$this->_initLinkedTables();
		$this->_initRelatedTables();
		$this->_initLabels();
	}

	/**
	 * Init the i18n table
	 */
	protected function _initI18n() {
		if ($i18ntable = $this->getDb()->getI18nTable($this->getRawName())) {
			$this->i18nTable = db::get('table', $i18ntable, array(
				'name'=>$i18ntable,
				'db'=>$this->getDb()
			));
		}
	}

	/**
	 * Indicate if the table is a i18n table
	 *
	 * @return bool
	 */
	public function isI18n() {
		return strpos($this->getName(), db::getCfg('i18n')) > 0;
	}

	/**
	 * Indicate if the thable has a i18n table
	 *
	 * @return bool
	 */
	public function hasI18n() {
		return !is_null($this->i18nTable);
	}

	/**
	 * Get the i18n table
	 *
	 * @return db_table
	 */
	public function getI18nTable() {
		return $this->i18nTable;
	}

	/**
	 * Get the i18nFields
	 *
	 * @return array
	 */
	public function getI18nFields() {
		$tmp = array();
		if ($this->i18nTable) {
			foreach($this->i18nTable->getField() as $f) {
				if (!$f['primary'])
					$tmp[] = $f;
			}
		}
		return $tmp;
	}

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
	protected function _initIdent() {
		if (empty($this->cfg->primary)) {
			$primary = array();
			foreach($this->fields as $n=>&$f)
				if ($f['primary']) {
					$primary[$f['primaryPos']] = $n;
					if ($f['identity']) {
						$this->cfg->ident = $n;
					}
				}
			$this->cfg->primary = $primary;
		} else if(is_string($this->cfg->primary))
			$this->cfg->primary = array($this->cfg->primary);
	}

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
				$link['tableObject'] = db::get('table', $link['table'], array(
					'db'=>$this->getDb()
				));
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
				$table = db::get('table', $t, array(
					'db'=>$this->getDb()
				));
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
					if ($tbl != $tblName && db::get('table', $tbl)->isTargeting($tblName))
						$this->targetingTables[] = $tbl;
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
	public function getI18nLabel($field = null) {
		return $this->i18nTable->getLabel($field);
	}

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
		unset($data[$this->getIdent()]);
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
	public function select(array $prm = array()) {
		$prm = $this->selectQuery($prm, $tmpTables);

		$ret = array();
		$cache = $this->getDb()->getCache();
		$canCache = $this->cfg->cacheEnabled && (!isset($prm['order']) || !(stripos($prm['order'], 'rand(') !== false));
		if (!$canCache || !$cache->get($ret, array('id'=>$this->getName().'-'.sha1(serialize($prm).'-'.serialize($tmpTables))))) {
			$ret = $this->getDb()->select($prm);

			if (!empty($ret) && !empty($this->cfg->forceValues)) {
				foreach($ret as $k=>$v)
					$ret[$k] = array_merge($v, $this->cfg->forceValues);
			}
			
			self::parseLinked($ret, $tmpTables);

			if ($canCache)
				$cache->save();
		}

		if (array_key_exists('first', $prm) && $prm['first']) {
			if (!empty($ret))
				return db::get('row', $this, array(
					'db'=>$this->getDb(),
					'data'=>$ret[0],
				));
			else
				return null;
		} else
			return db::get('rowset', $this, array(
				'db'=>$this->getDb(),
				'data'=>$ret,
			));
	}

	/**
	 * Count the number of result
	 *
	 * @param array $prm The initial parameter
	 * @return int
	 */
	public function count(array $prm) {
		$prm = $this->selectQuery($prm, $tmpTables);
		if ($this->getIdent())
			$prm['group'] = $prm['fields'] = $this->rawName.'.'.$this->getIdent();
		else
			$prm['group'] = $prm['fields'] = $this->rawName.'.'.implode(','.$this->rawName.'.', $this->getPrimary());

		$prm['where'] = $this->getDb()->makeWhere($prm['where'], $prm['whereOp'], false);
		$nb = array_key_exists('join', $prm) ? count($prm['join']) : 0;
		for($i=0; $i<$nb; $i++) {
			$table = array_key_exists('alias', $prm['join'][$i])
					? $prm['join'][$i]['alias']
					: $prm['join'][$i]['table'];
			if (array_key_exists('dir', $prm['join'][$i]) &&
					!is_null(strpos($prm['join'][$i]['dir'], 'outer')) &&
					!preg_match('/`'.$table.'`\./', $prm['where']))
				unset($prm['join'][$i]);
		}
		return $this->getDb()->count($prm);
	}

	/**
	 * Create the right array paremeter for select query
	 *
	 * @param array $prm The initial parameter
	 * @param array $tmpTables Array used with parseLinked
	 */
	public function selectQuery(array $prm, &$tmpTables) {
		config::initTab($prm, array(
			'where'=>'',
			'whereOp'=>'AND',
			'order'=>'',
			'autoJoin'=>$this->cfg->autoJoin,
		));

		if (is_array($prm['where'])) {
			foreach($prm['where'] as $k=>$v) {
				if (!is_numeric($k) && strpos($k, '.') === false) {
					$newK = $this->rawName.'.'.$k;
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
			$prm['where'] = $this->rawName.'.'.$this->cfg->ident.'='.$prm['where'];
		}

		$prm = array_merge(array(
			'fields'=>$this->getDb()->quoteIdentifier($this->rawName).'.*',
			'table'=>$this->rawName,
		), $prm);

		if (is_array($prm['fields'])) {
			array_walk($prm['fields'],
				create_function('&$v', '$v = strpos($v, ".") === false? "'.$this->rawName.'.".$v: $v;'));
			$prm['fields'] = implode(',', $prm['fields']);
		}

		$join = isset($prm['join']) ? $prm['join'] : array();
		$prm['join'] = array();
		$tmpTables = array();
		if (!empty($this->linkedTables) && $prm['autoJoin']) {
			foreach($this->linkedTables as $f=>$p) {
				$alias = $f;
				$prm['join'][] = array(
					'table'=>$p['table'],
					'alias'=>$alias,
					'dir'=>'left outer',
					'on'=>$this->rawName.'.'.$f.'='.$alias.'.'.$p['ident']
				);
				$fields = explode(',', $p['fields']);
				array_unshift($fields, $p['ident']);
				$fields = array_flip(array_flip(array_filter($fields)));
				$fieldsT = $fields;
				array_walk($fieldsT, create_function('&$v', '$v = "'.$alias.'_".$v;'));
				$tmpTables[$f] = $fieldsT;
				$tmpTables[$f]['sep'] = $p['sep'];
				$tmpTables[$f]['ident'] = $alias.'_'.$p['ident'];
				$tmp = array();
				$linkedTable = db::get('table', $p['table'], array(
					'db'=>$this->getDb()
				));
				foreach($fields as $t) {
					if ($linkedInfo = $linkedTable->getLinkedTableName($t)) {
						$aliasT = $alias.'_'.$linkedInfo['table'];
						$prm['join'][] = array(
							'table'=>$linkedInfo['table'],
							'alias'=>$aliasT,
							'dir'=>'left outer',
							'on'=>$alias.'.'.$linkedInfo['field'].'='.$aliasT.'.'.$linkedInfo['ident']
						);
						$ttmp = array();
						foreach(explode(',', $linkedInfo['fields']) as $tt) {
							$ttmp[] = $aliasT.'.'.$tt;
							$ttmp[] = '"'.$linkedInfo['sep'].'"';
						}
						array_pop($ttmp);
						$tmp[] = 'CONCAT('.implode(',', $ttmp).') AS '.$alias.'_'.$t;
					} else
						$tmp[] = $alias.'.'.$t.' AS '.$alias.'_'.$t;
				}
				$fields = $tmp;
				$prm['fields'].= ','.implode(',', $fields);

				if ($p['i18nFields']) {
					$fieldsI18n = array();
					$i18nTableName = $p['table'].db::getCfg('i18n');
					$i18nTable = db::get('table', $i18nTableName, array('db'=>$this->getDb()));
					$primary = $i18nTable->getPrimary();
					$i18nAlias = $alias.db::getCfg('i18n');
					$prm['join'][] = array(
						'table'=>$i18nTableName,
						'alias'=>$i18nAlias,
						'dir'=>'left outer',
						'on'=>$alias.'.'.$p['ident'].'='.$i18nAlias.'.'.$primary[0].
							' AND '.$i18nAlias.'.'.$primary[1].'="'.request::get('lang').'"'
					);
					$fields = explode(',', $p['i18nFields']);
					$fields = array_flip(array_flip(array_filter($fields)));
					$fieldsI18n = $fields;
					array_walk($fieldsI18n, create_function('&$v', '$v = "'.$alias.'_'.db::getCfg('i18n').'".$v;'));
					array_walk($fields, create_function('&$v', '$v = "'.$i18nAlias.'.".$v." AS '
									.$alias.'_'.db::getCfg('i18n').'".$v;'));
					$tmpTables[$f] = array_merge($tmpTables[$f], $fieldsI18n);
					if (!empty($fields))
						$prm['fields'].= ','.implode(',', $fields);
				}
			}
		}

		if ((!empty($this->relatedTables) && $prm['autoJoin']) || $this->i18nTable) {
			foreach($this->relatedTables as $f=>$p) {
				$prm['join'][] = array(
					'table'=>$f,
					'dir'=>'left outer',
					'on'=>$this->rawName.'.'.$p['fk1']['link']['ident'].'='.$f.'.'.$p['fk1']['name']
				);

				// related Table fields
				$fields = array_keys($p['fields']);
				$fieldsTableLink = $fields;
				array_walk($fieldsTableLink, create_function('&$v', '$v = "'.$f.'_".$v;'));
				array_walk($fields, create_function('&$v', '$v = "'.$f.'.".$v." AS '.$f.'_".$v;'));
				if (!empty($fields))
					$prm['fields'].= ','.implode(',', $fields);

				$prm['join'][] = array(
					'table'=>$p['table'],
					'dir'=>'left outer',
					'on'=>$f.'.'.$p['fk2']['name'].'='.$p['table'].'.'.$p['fk2']['link']['ident']
				);

				// related Table fields
				$fields = explode(',', $p['fk2']['link']['fields']);
				array_unshift($fields, $p['fk2']['link']['ident']);
				$fields = array_flip(array_flip(array_filter($fields)));
				$fieldsT = $fields;
				array_walk($fieldsT, create_function('&$v', '$v = "'.$p['table'].'_".$v;'));
				array_walk($fields, create_function('&$v', '$v = "'.$p['table'].'.".$v." AS '.$p['table'].'_".$v;'));
				if (!empty($fields))
					$prm['fields'].= ','.implode(',', $fields);

				// i18n related Table fields
				if ($p['fk2']['link']['i18nFields']) {
					$fieldsI18n = array();
					$i18nTableName = $p['table'].db::getCfg('i18n');
					$i18nTable = db::get('table', $i18nTableName, array('db'=>$this->getDb()));
					$primary = $i18nTable->getPrimary();
					$prm['join'][] = array(
						'table'=>$i18nTableName,
						'dir'=>'left outer',
						'on'=>$f.'.'.$p['fk2']['name'].'='.$i18nTableName.'.'.$primary[0].
							' AND '.$i18nTableName.'.'.$primary[1].'="'.request::get('lang').'"'
					);
					$fields = explode(',', $p['fk2']['link']['i18nFields']);
					$fields = array_flip(array_flip(array_filter($fields)));
					$fieldsI18n = $fields;
					array_walk($fieldsI18n, create_function('&$v', '$v = "'.$i18nTableName.'_".$v;'));
					array_walk($fields, create_function('&$v', '$v = "'.$i18nTableName.'.".$v." AS '
									.$p['table'].'_'.db::getCfg('i18n').'".$v;'));
					if (!empty($fields))
						$prm['fields'].= ','.implode(',', $fields);
				}

				$tmpTables['relatedTable'][$f] = array(
					'ident'=>$this->getIdent(),
					'tableName'=>$p['table'],
					'tableLink'=>$fieldsTableLink,
					'table'=>array_merge($fieldsT, array(
						'field'=>$p['fk2']['name'],
						'sep'=>$p['fk2']['link']['sep'],
						'ident'=>$p['table'].'_'.$p['fk2']['link']['ident'],
					))
				);
			}
			if ($this->i18nTable) {
				$i18nName = $this->i18nTable->getName();
				$primary = $this->i18nTable->getPrimary();
				$prm['join'][] = array(
					'table'=>$i18nName,
					'dir'=>'left outer',
					'on'=>$this->rawName.'.'.$this->getIdent().'='.$i18nName.'.'.$primary[0]
				);

				// related Table fields
				$fields = array($primary[1]);
				foreach($this->getI18nFields() as $f) {
					$fields[] = $f['name'];
				}
				$fieldsTableLink = $fields;
				array_walk($fieldsTableLink, create_function('&$v', '$v = "'.$i18nName.'_".$v;'));
				array_walk($fields, create_function('&$v', '$v = "'.$i18nName.'.".$v." AS '.$i18nName.'_".$v;'));

				if (!empty($fields))
					$prm['fields'].= ','.implode(',', $fields);

				// related Table fields
				$tmpTables['relatedTable'][$i18nName] = array(
					'ident'=>$this->getIdent(),
					'tableName'=>$i18nName,
					'tableLink'=>$fieldsTableLink,
					'table'=>array(
						'field'=>$i18nName.'_'.$primary[0],
						'sep'=>null,
						'ident'=>$i18nName.'_'.$primary[1],
					)
				);
			}
			if (array_key_exists('nb', $prm)) {
				$tmpTables['nb'] = $prm['nb'];
				$tmpTables['st'] = array_key_exists('start', $prm)? $prm['start'] : 0;
				unset($prm['nb']);
			}
		}

		$prm['join'] = array_merge($prm['join'], $join);

		return $prm;
	}

	/**
	 * Parse select result for the linked and related tables
	 *
	 * @param array $data (reference, will be updated) Data issued from the select
	 * @param array $linked Linked array issued from the select
	 */
	public static function parseLinked(array &$data, array $linked) {
		if (!empty($linked) && !empty($data)) {
			if (array_key_exists('relatedTable', $linked)) {
				$ident = 'id';

				$tmpRelated = array();
				$nb = count($data);

				$ids = array();
				$current = 0;
				$idRelated = array();

				for($i = 0; $i<$nb; $i++) {
					$id = $data[$i][$ident];

					$delete = true;
					if (!array_key_exists($id, $ids)) {
						// new id
						$current = $i;
						$ids[$id] = $current;
						$data[$current]['related'] = array();
						$delete = false;
					} else
						$current = $ids[$id];
					foreach($linked['relatedTable'] as $t=>$r) {
						if (!array_key_exists($r['tableName'], $data[$current]['related']))
							$data[$current]['related'][$r['tableName']] = array();
						if (!$data[$i][$r['table']['ident']]
							|| (array_key_exists($id, $idRelated)
								&& array_key_exists($r['tableName'], $idRelated[$id])
								&& in_array($data[$i][$r['table']['ident']], $idRelated[$id][$r['tableName']])))
							// The id was already affected to te current element, skip to the next table
							continue;

						$tmp = array();

						$label = array();
						foreach($r['table'] as $kk=>$fTab) {
							if ($kk != 'sep' && $kk != 'ident' && $kk != db::getCfg('i18n')) {
								if (!empty($data[$i][$fTab]))
									$label[] = $data[$i][$fTab];
								if ($fTab != $r['table']['ident'])
									unset($data[$i][$fTab]);
							}
						}
						if (!empty($label)) {
							$tmp[substr($r['table']['ident'], strlen($r['tableName'])+1)] = $data[$i][$r['table']['ident']];
							$tmp[$r['table']['field']] = implode($r['table']['sep'], $label);
						}
						$idRelated[$id][$r['tableName']][] = $data[$i][$r['table']['ident']];

						foreach($r['tableLink'] as $tl) {
							$tmp[substr($tl, strlen($t)+1)] = $data[$i][$tl];
							unset($data[$i][$tl]);
						}

						if (!empty($tmp)) {
							$data[$current]['related'][$r['tableName']][] = $tmp;
						}
					}
					// Delete the duplicate
					if ($delete)
						unset($data[$i]);
				}
				$data = array_merge($data);

				unset($linked['relatedTable']);

				if (array_key_exists('nb', $linked)) {
					$data = array_slice($data, $linked['st'], $linked['nb']);
					unset($linked['nb']);
					unset($linked['st']);
				}
			}

			$linkedKey = db::getCfg('linked');
			array_walk($data, create_function('&$v, $i, &$tl', '
				$v["'.$linkedKey.'"] = array();
				foreach($tl as $k=>$t) {
					$v["'.$linkedKey.'"][$k] = array();
					$label = array();
					$length = strlen($k)+1;
					foreach($t as $kk=>$f) {
						if ($kk != "sep" && $kk != "ident") {
							if (!empty($v[$f]))
								$label[] = $v[$f];
							$v["'.$linkedKey.'"][$k][substr($f, $length)] = $v[$f];
							if ($f != $t["ident"])
								unset($v[$f]);
						}
					}
					if (array_key_exists($t["ident"], $v) && $v[$t["ident"]]) {
						$ident = substr($t["ident"], $length);
						$v["'.$linkedKey.'"][$k][$ident] = $v[$t["ident"]];
						$v[$k] = $v["'.$linkedKey.'"][$k]["label"] = implode($t["sep"], $label);
					} else {
						$v[$k] = null;
						$v["'.$linkedKey.'"][$k] = array();
					}
				}
			'), $linked);
		}
	}

	/**
	 * Find a row by Id or by different value if array
	 *
	 * @param mixed $val Id value
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
	public function findText($text, $filter = null) {
		if (!$filter) {
			$filter = array();
			foreach($this->fields as &$f)
				if ($f['text'])
					$filter[] = $f['name'];
		} else if (!is_array($filter))
			$filter = explode(',', $filter);

		$where =  $this->getWhere(array('op'=>'OR'));
		foreach($filter as $f)
			$where->add(array(
				'field'=>$this->getRawName().'.'.$f,
				'val'=>'%'.$text.'%',
				'op'=>'LIKE'
			));

		$prm = array(
			'whereOp'=>'OR',
			'where'=>$where,
		);
		return $this->select($prm);
	}

	/**
	 * Get the min and the max value for a field.
	 *
	 * @param string $field Fieldname. If null, id is used
	 * @return array With key min and max
	 */
	public function getRange($field = null) {
		if (is_null($field))
			$field = $this->getIdent();
		$query = 'SELECT MIN('.$field.'),MAX('.$field.') FROM '.$this->getRawName()
					.' WHERE '.$this->cfg->whereRange;
		$tmp = $this->getDb()->query($query)->fetchAll(PDO::FETCH_NUM);
		$tmp = $tmp[0];
		$min = array_key_exists(0, $tmp) ? $tmp[0] : 0;
		return array(
			'min'=>$min,
			'max'=>array_key_exists(1, $tmp) ? $tmp[1] : $min,
		);
	}

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
				db::get('table', $tbl)->clearCache(false);
			}
		}
		if (!$this->cfg->cacheEnabled)
			return false;
		return $this->getDb()->getCache()->delete(array(
			'callFrom'=>get_class($this).'-select',
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
			'db'=>$this->getDb(),
			'data'=>$data
		));
		if (array_key_exists($this->getIdent(), $data) && $data[$this->getIdent()])
			$prm['findId'] = $data[$this->getIdent()];
		return db::get('row', $this, $prm);
	}

}
