<?php
/**
 * @author CÃ©dric Nirousset <cedric@nyrodev.com>
 * @version 0.2
 * @package nyroFwk
 */
/**
 * SQL Table interface
 */
class db_pdo_table extends db_table {

	/**
	 * i18n table
	 *
	 * @var db_table
	 */
	protected $i18nTable;

	protected function afterInit() {
		parent::afterInit();
		$this->_initi18n();
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
		if (!$this->isI18n())
			return parent::_initLinkedTables();
	}

	/**
	 * Init the i18n table
	 */
	protected function _initI18n() {
		if ($i18ntable = $this->getDb()->getI18nTable($this->getRawName())) {
			$this->i18nTable = $this->getDb()->getTable($i18ntable);
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
	 * Get the i18n table
	 *
	 * @return db_table
	 */
	public function getI18nTable() {
		return $this->i18nTable;
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
	 * Get the i18n fields information
	 *
	 * @param string|null $field Field name. If null, the whole field array will be returned
	 * @param string|null $keyVal Value to retrieve directly
	 * @return array|null
	 */
	public function geti18nField($field = null, $keyVal = null) {
		return $this->hasI18n() ? $this->getI18nTable()->getField(db::unI18nName($field), $keyVal) : null;
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
	 * Get linked information about a i18n field
	 * 
	 * @param string $field Field name
	 * @return array|null
	 */
	public function getI18nLinked($field) {
		return $this->hasI18n() ? $this->getI18nTable()->getLinked($field) : null;
	}
	
	/**
	 * Get a where clause for a i18n field
	 *
	 * @param string $field
	 * @param mixed $val
	 * @return string
	 */
	public function getI18nWhereClause($field, $val) {
		$ret = null;
		if ($this->hasI18n()) {
			$f = $this->geti18nField(db::unI18nName($field));
			$tblName = $this->getI18nTable()->getName();
			$prim = $this->getI18nTable()->getPrimary();
			$field = $tblName.'.'.db::unI18nName($field);
			
			$ret = '('.$this->getName().'.'.$this->getIdent().' IN (SELECT '.$tblName.'.'.$prim[0].' FROM '.$tblName.' WHERE ';

			$tmpWhere = $this->getI18nTable()->getWhere(array('op'=>db_where::OPLINK_OR));
			if (isset($f['text']) && $f['text']) {
				$tmp = array_filter(array_map('trim', explode(' ', $val)));
				foreach($tmp as $t) {
					$tmpWhere->add(array(
						'field'=>$field,
						'val'=>$t,
						'op'=>db_where::OP_LIKEALMOST
					));
				}
			} else {
				$tmpWhere->add(array(
					'field'=>$field,
					'val'=>$val
				));
			}
			$ret.= $tmpWhere.'))';
		}
		return $ret;
	}
	
	/**
	 * Retrieve the fields for this table
	 *
	 * @return array
	 */
	protected function getConfiguredFields() {
		return $this->getDb()->fields($this->cfg->name);
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
	 * Get a field name to be used in query (like order)
	 *
	 * @param string $field
	 * @return string
	 */
	public function getFieldQuery($field) {
		return db::isI18nName($field) ? $this->getI18nTable()->getName().'_'.db::unI18nName($field) : $field;
	}
	
	/**
	 * prepare a query for a specific sortBy field
	 *
	 * @param string $sortBy
	 * @param array $query
	 * @return array
	 */
	public function getSortBy($sortBy, $query) {
		$sortByRet = $sortBy;
		if ($this->isRelated($sortBy)) {
			$related = $this->getRelated($sortBy);
			$tmp = array();

			$fields = array_filter(explode(',', $related['fk2']['link']['fields']));
			$tableName = $related['fk2']['link']['table'];
			foreach($fields as $f)
				$tmp[] = $tableName.'.'.$f;

			$fields = array_filter(explode(',', $related['fk2']['link']['i18nFields']));
			$tableName.= db::getCfg('i18n');
			foreach($fields as $f)
				$tmp[] = $tableName.'.'.$f;
			$sortByRet = implode(', ', $tmp);
			if (!$this->getCfg()->autoJoin) {
				$f = $related['tableObj']->getRawName();
				if (!isset($query['join']))
					$query['join'] = array();
				$query['join'][] = array(
					'table'=>$f,
					'dir'=>'left outer',
					'on'=>$this->getRawName().'.'.$related['fk1']['link']['ident'].'='.$f.'.'.$related['fk1']['name']
				);
				$query['join'][] = array(
					'table'=>$related['table'],
					'dir'=>'left outer',
					'on'=>$f.'.'.$related['fk2']['name'].'='.$related['table'].'.'.$related['fk2']['link']['ident']
				);

				if ($related['fk2']['link']['i18nFields']) {
					$i18nTableName = $related['table'].db::getCfg('i18n');
					$i18nTable = $this->getDb()->getTable($i18nTableName);
					$primary = $i18nTable->getPrimary();
					$query['join'][] = array(
						'table'=>$i18nTableName,
						'dir'=>'left outer',
						'on'=>$f.'.'.$related['fk2']['name'].'='.$i18nTableName.'.'.$primary[0].
							' AND '.$i18nTableName.'.'.$primary[1].'="'.request::get('lang').'"'
					);
				}

				$query['group'] = $this->getRawName().'.'.$this->getIdent();
			}
		} else if ($this->isLinked($sortBy)) {
			$linked = $this->getLinked($sortBy);
			$tmpSort = array();
			foreach(explode(',', trim($linked['fields'].','.$linked['i18nFields'], ',')) as $tmp)
				$tmpSort[] = $linked['field'].'.'.$tmp;
			$sortByRet = implode(', ', $tmpSort);
			if (!$this->getCfg()->autoJoin) {
				if (!isset($query['join']))
					$query['join'] = array();
				$alias = $linked['field'];
				if ($linked['i18nFields']) {
					$alias1 = $alias.'1';
					$query['join'][] = array(
						'table'=>$linked['table'],
						'alias'=>$alias1,
						'dir'=>'left outer',
						'on'=>$this->getRawName().'.'.$linked['field'].'='.$alias1.'.'.$linked['ident']
					);
					$i18nTableName = $linked['table'].db::getCfg('i18n');
					$i18nTable = $this->getDb()->getTable($i18nTableName);
					$primary = $i18nTable->getPrimary();
					$query['join'][] = array(
						'table'=>$i18nTableName,
						'alias'=>$alias,
						'dir'=>'left outer',
						'on'=>$alias1.'.'.$linked['ident'].'='.$alias.'.'.$primary[0].
							' AND '.$alias.'.'.$primary[1].'="'.request::get('lang').'"'
					);
				} else {
					$query['join'][] = array(
						'table'=>$linked['table'],
						'alias'=>$alias,
						'dir'=>'left outer',
						'on'=>$this->getRawName().'.'.$linked['field'].'='.$alias.'.'.$linked['ident']
					);
				}
			}
		} else if ($sortBy) {
			if (strpos($sortBy, $this->getName()) !== false || strpos($sortBy, '.') !== false)
				$sortByRet = $sortBy;
			else
				$sortByRet = $this->getName().'.'.$sortBy;
		}
		return array(
			'sortBy'=>$sortByRet,
			'query'=>$query
		);
	}

	/**
	 * Search on the table
	 *
	 * @param array $prm Select query configuration. Same parameter than db_abstract::select, with more:
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
				return  $this->getDb()->getRow($this, array(
					'db'=>$this->getDb(),
					'data'=>$ret[0],
				));
			else
				return null;
		} else
			return $this->getDb()->getRowset($this, array(
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
				$linkedTable = $this->getDb()->getTable($p['table']);
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
					$i18nTable = $this->getDb()->getTable($i18nTableName);
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
					$i18nTable = $this->getDb()->getTable($i18nTableName);
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

}
