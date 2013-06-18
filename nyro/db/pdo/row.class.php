<?php
/**
 * @author Cédric Nirousset <cedric@nyrodev.com>
 * @version 0.2
 * @package nyroFwk
 */
/**
 * Interface for db classes
 */
class db_pdo_row extends db_row {

	/**
	 * i18n rows parsed
	 *
	 * @var array
	 */
	protected $i18nRows = array();

	/**
	 * Create a new row with the current values
	 *
	 * @return mixed The last inserted id
	 */
	public function insert() {
		$id = parent::insert();
		$this->saveI18n();
		return $id;
	}

	/**
	 * Save the row in the database
	 *
	 * @return bool|mixed True if successful or no changes was done|mixed if new and inserted, will be last insert id
	 */
	public function save() {
		$ret = parent::save();
		if ($ret)
			$this->saveI18n();
		return $ret;
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
			list($fkId, $lang) = ($this->getTable()->getI18nTable()->getPrimary());
			foreach($this->i18nRows as $r) {
				if ($r->isNew())
					$r->set($fkId, $this->getId());
				$r->save();
			}
		}
	}

	/**
	 * Get a i18n value
	 *
	 * @param string $key Fieldname
	 * @param string $mode Mode to retrieve the value, only used for related (flat or flatReal)
	 * @return mixed The value
	 */
	public function getI18n($key, $mode = db_row::VALUESMODE_FLAT, $lang = null) {
		return $this->getI18nRow($lang)->get($key, $mode);
	}

	/**
	 * Get a i18nRow
	 *
	 * @param string $lang Lang needed (if null, the current will be used or a new row will be created)
	 * @return db_row
	 */
	public function getI18nRow($lang = null) {
		if (is_null($lang) || !$lang)
			$lang = request::get('lang');
		if (!array_key_exists($lang, $this->i18nRows)) {
			if ($this->getTable()->getCfg()->i18nGetDefaultLangIfNotExist && $lang != request::getDefaultLang())
				return $this->getI18nRow(request::getDefaultLang());
			$primary = $this->getTable()->getI18nTable()->getPrimary();
			$this->i18nRows[$lang] = $this->getTable()->getI18nTable()->getRow();
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
	 * Get the i18n values, indexed by lang
	 * 
	 * @return array
	 */
	public function getI18nValues() {
		$ret = array();
		if ($this->getTable()->hasI18n()) {
			$primary = $this->getTable()->getI18nTable()->getPrimary();
			foreach ($this->getI18nRows() as $r) {
				$lang = $r->get($primary[1]);
				$ret[$lang] = array();
				foreach($r->getValues() as $k=>$v)
					$ret[$lang][$k] = $v;
			}
		}
		return $ret;
	}

	/**
	 * Set i18n values
	 *
	 * @param array $values Values
	 * @param bool $force Indicates if the value should be replaced even if it's the same
	 * @param string|null $lg Lang
	 */
	public function setI18n(array $values, $force = false, $lg = null) {
		if (!is_null($lg) && $lg) {
			if (count($values))
				$this->getI18nRow($lg)->setValues($values, $force);
		} else {
			foreach($values as $lg=>$val) {
				if (is_array($val))
					$this->setI18n($val, $force, $lg);
			}
		}
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
	 * Get the related rows
	 *
	 * @param string Related Name
	 * @return db_rowset
	 */
	public function getRelated($name) {
		$related = $this->getTable()->getRelated($this->getTable()->getRelatedTableName($name));
		$id = $this->getId();
		if (!$id)
			return array();
		return $related['tableObj']->getLinkedTable($related['fk2']['name'])->select(array(
			'where'=>$this->getWhere(array(
				'clauses'=>$this->getDb()->getWhereClause(array(
					'name'=>$related['fk2']['link']['table'].'.'.$related['fk2']['link']['ident'],
					'in'=>$this->getDb()->selectQuery(array(
						'fields'=>$related['fk2']['name'],
						'table'=>$related['tableLink'],
						'where'=>$related['fk1']['name'].'='.$id
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
	public function setRelated($related, $name = null) {
		if (!is_null($name)) {
			if ($this->getTable()->getI18nTable() && $name == $this->getTable()->getI18nTable()->getName()) {
				$primary = $this->getTable()->getI18nTable()->getPrimary();
				foreach($related as $r) {
					$this->i18nRows[$r[$primary[1]]] = $this->getTable()->getI18nTable()->getRow(
						array_merge($r, array($primary[0]=>$this->getId())));
				}
			} else {
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
	public function whereClause() {
		$primary = $this->getTable()->getPrimary();
		$values = $this->getValues(db_row::VALUESMODE_FLAT);
		$where = array();
		foreach($primary as $p) {
			$where[] = $this->getTable()->getRawName().'.'.$p.='="'.$values[$p].'"';
		}
		return implode(' AND ', $where);
	}

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
	public function getAround(array $prm = array()) {
		config::initTab($prm, array(
			'field'=>null,
			'returnId'=>false,
			'where'=>null,
			'asRow'=>false
		));
		
		if ($prm['asRow'])
			$prm['returnId'] = true;

		$field = $prm['field'];
		$where = $this->getDb()->makeWhere($prm['where']);
		if (empty($where))
			$where = 'WHERE 1';

		if (!is_null($where))
			$where.= ' AND ';
		if (is_null($field))
			$field = $this->getTable()->getIdent();
		$val = $this->get($field);
		
		$query = '(SELECT '.$field.','.$this->getTable()->getIdent().' FROM '.$this->getTable()->getRawName().' '.$where.$field.' < ? ORDER BY '.$field.' DESC LIMIT 1)
					UNION
				  (SELECT '.$field.','.$this->getTable()->getIdent().' FROM '.$this->getTable()->getRawName().' '.$where.$field.' > ? ORDER BY '.$field.' ASC LIMIT 1)';
		$vals = $this->getDb()->query($query, utils::htmlDeOut(array($val, $val)))->fetchAll(PDO::FETCH_NUM);
		$ret = array(null, null);
		$useIndex = $prm['returnId'] ? 1 : 0;
		if (array_key_exists(1, $vals)) {
			$ret[0] = $vals[0][$useIndex];
			$ret[1] = $vals[1][$useIndex];
		} else if (array_key_exists(0, $vals)) {
			$tmp = $vals[0][0];
			if ($tmp > $val)
				$ret[1] = $vals[0][$useIndex];
			else
				$ret[0] = $vals[0][$useIndex];
		}
		if ($prm['asRow']) {
			if ($ret[0])
				$ret[0] = $this->getTable()->find($ret[0]);
			if ($ret[1])
				$ret[1] = $this->getTable()->find($ret[1]);
		}
		return $ret;
	}

}
