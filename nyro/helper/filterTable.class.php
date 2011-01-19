<?php
/**
 * @author Cédric Nirousset <cedric@nyrodev.com>
 * @version 0.2
 * @package nyroFwk
 */
/**
 * Helper to create a db filter form
 */
class helper_filterTable extends object {

	/**
	 * Filter Form
	 *
	 * @var form_db
	 */
	protected $form;

	/**
	 * The table object
	 *
	 * @var db_table
	 */
	protected $table;

	/**
	 * Session object
	 *
	 * @var session_abstract
	 */
	protected $session;

	/**
	 * Indicates if the form has values
	 *
	 * @var bool
	 */
	protected $hasValues = false;

	protected function afterInit() {
		$this->table = $this->cfg->table;
		if (!$this->cfg->sessionName)
			$this->cfg->sessionName = $this->table->getName();

		$this->session = session::getInstance(array(
			'nameSpace'=>'filterTable_'.$this->cfg->sessionName
		));

		if (!$this->cfg->check('clearPrm'))
			$this->cfg->clearPrm = 'clearFilter'.ucfirst($this->table->getName());
		$this->cfg->setinArray('actionPrmClear', $this->cfg->clearPrm, true);

		$this->initForm();
	}

	/**
	 * Init the filter form
	 */
	protected function initForm() {
		$this->form = factory::get('form_db', array_merge($this->cfg->formOpts, array(
			'filter'=>true,
			'table'=>$this->table,
			'sectionName'=>$this->cfg->formName,
			'action'=>request::uriDef(array('paramA'=>array_merge(array_diff_key(request::get('paramA'), $this->cfg->actionPrmClear), $this->cfg->actionPrmForce)))
		)));

		$this->form->setSubmitText($this->cfg->submitText);
		$this->form->setSubmitplus('<a href="'.$this->clearLink().'">'.$this->cfg->clearText.'</a>');

		if (!empty($this->cfg->fields)) {
			foreach($this->cfg->fields as $field) {
				if ($f = $this->table->getField($field)) {
					$f['label'] = $this->getLabel($f['name']);
					$f['link'] = $this->table->getLinked($f['name']);
					$this->form->addFromFieldFilter($f);
				} else if ($r = $this->table->getRelated($field)) {
					$r['label'] = $this->getLabel($r['table']);
					$r['name'] = $r['tableLink'];
					$this->form->addFromRelatedFilter($r);
				} else if ($this->table->hasI18n() && db::isI18nName($field) && ($f = $this->table->getI18nTable()->getField(db::unI18nName($field)))) {
					$name = db::unI18nName($field);
					$f['name'] = $field;
					$f['label'] = $this->getLabel($field);
					$f['link'] = $this->table->getI18nTable()->getLinked($name);
					$this->form->addFromFieldFilter($f);
				}
			}
		} else {
			// All fields
			foreach($this->table->getField() as $f) {
				$f['label'] = $this->getLabel($f['name']);
				$f['link'] = $this->table->getLinked($f['name']);
				$this->form->addFromFieldFilter($f);
			}

			foreach($this->table->getRelated() as $t=>$r) {
				$r['name'] = $r['tableLink'];
				$r['label'] = $this->getLabel($r['table']);
				$this->form->addFromRelated($r);
			}
		}
		$defValues = $this->form->getValues();

		if (request::isPost() || request::getPrm($this->cfg->clearPrm)) {
			if (request::isPost())
				$this->form->refill();
			$this->session->clear(true);
		} else {
			foreach($this->form->getNames() as $name) {
				if ($this->session->check($name))
					$this->form->setValue($name, $this->session->get($name));
			}
			$this->form->setBound(false);
		}
		foreach($this->form->getValues() as $k=>$v) {
			if ($v != $defValues[$k])
				$this->hasValues = true;
		}
	}

	/**
	 * Get the filter form
	 *
	 * @return form
	 */
	public function getForm() {
		return $this->form;
	}

	/**
	 * Indicates if the form fomter has values or not
	 *
	 * @return bool
	 */
	public function hasValues() {
		return $this->hasValues;
	}

	/**
	 * Get the where clause
	 *
	 * @return db_where The where clause
	 */
	public function getWhere() {
		$where = $this->table->getWhere();
		foreach($this->form->getValues() as $name=>$val) {
			$field = strpos($name, '.') === false ? $this->table->getName().'.'.$name : $name;
			if (!is_null($val) && ($val || $val === '0')) {
				if (is_array($val)) {
					if (array_key_exists('min', $val) || array_key_exists('max', $val)) {
						$min = array_key_exists('min', $val) && !empty($val['min'])?$val['min'] : null;
						$max = array_key_exists('max', $val) && !empty($val['max'])?$val['max'] : null;
						if ($min)
							$where->add(array(
								'field'=>$field,
								'val'=>$min,
								'op'=>'>='
							));
						if ($max)
							$where->add(array(
								'field'=>$field,
								'val'=>$max,
								'op'=>'<='
							));
					} else {
						$fieldRel = $this->table->getRelated($name);
						$where->add(array(
							'field'=>$fieldRel ? $fieldRel['tableLink'].'.'.$fieldRel['fk2']['name'] : $field,
							'val'=>array_map(array($this->table->getDb(), 'quoteValue'), $val),
							'op'=>'IN'
						));
					}
				} else if(strpos($name, '_file')) {
					$where->add(array(
						'field'=>$field,
						'val'=>'',
						'op'=>'<>'
					));
				} else {
					$f = $this->table->getField($name);
					if (is_array($f) && (!array_key_exists('text', $f) || $f['text'])) {
						$tmp = explode(' ', $val);
						array_walk($tmp, create_function('&$v', '$v = trim($v);'));
						$tmp = array_filter($tmp);
						foreach($tmp as $t) {
							$where->add(array(
								'field'=>$field,
								'val'=>'%'.$t.'%',
								'op'=>'LIKE'
							));
						}
					} else if ($this->table->hasI18n() && db::isI18nName($name) && ($f = $this->table->getI18nTable()->getField(db::unI18nName($name)))) {
						$tblName = $this->table->getI18nTable()->getName();
						$prim = $this->table->getI18nTable()->getPrimary();
						$field = $tblName.'.'.db::unI18nName($name);
						$clause = '('.$this->table->getName().'.'.$this->table->getIdent().' IN (SELECT '.$tblName.'.'.$prim[0].' FROM '.$tblName.' WHERE ';

						$tmpWhere = $this->table->getI18nTable()->getWhere(array('op'=>'OR'));

						if (!array_key_exists('text', $f) || $f['text']) {
							$tmp = explode(' ', $val);
							array_walk($tmp, create_function('&$v', '$v = trim($v);'));
							$tmp = array_filter($tmp);
							foreach($tmp as $t) {
								$tmpWhere->add(array(
									'field'=>$field,
									'val'=>'%'.$t.'%',
									'op'=>'LIKE'
								));
							}
						} else {
							$tmpWhere->add(array(
								'field'=>$field,
								'val'=>$val
							));
						}
						$clause.= $tmpWhere.'))';
						$where->add($clause);
					} else {
						$where->add(array(
							'field'=>$field,
							'val'=>$val
						));
					}
				}
				$this->session->set(array(
					'name'=>$name,
					'val'=>$val
				));
			}
		}

		if (count($where))
			return $where;
		return null;
	}

	/**
	 * Get the label for a fieldname or a tablename
	 *
	 * @param string $name
	 * @return string The label
	 */
	public function getLabel($name) {
		return array_key_exists($name, $this->cfg->label) ?
				$this->cfg->getInArray('label', $name) :
				$this->table->getLabel($name);
	}

	/**
	 * Get the clear URL link
	 *
	 * @return string
	 */
	public function clearLink() {
		$prmA = array_merge(array_diff_key(request::get('paramA'), $this->cfg->actionPrmClear), $this->cfg->actionPrmForce);
		$prmA[$this->cfg->clearPrm] = 1;
		return request::uriDef(array('paramA'=>$prmA));
	}

	/**
	 * Create the data Table out
	 *
	 * @param string $type Out type
	 * @return string
	 */
	public function to($type) {
		return $this->form->to($type);
	}

	/**
	 * Create the data Table HTML out
	 *
	 * @return string
	 */
	public function toHtml() {
		return $this->to('html');
	}

	/**
	 * Create the data Table out regarding the request out
	 *
	 * @return string
	 */
	public function __toString() {
		return $this->to(request::get('out'));
	}

}