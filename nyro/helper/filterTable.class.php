<?php

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

	protected function afterInit() {
		$this->table = $this->cfg->table;

		$this->session = session::getInstance(array(
			'nameSpace'=>'filterTable_'.$this->table->getName()
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
		$this->form = factory::get('form_db', array(
			'filter'=>true,
			'table'=>$this->table,
			'sectionName'=>$this->cfg->formName,
			'action'=>array('paramA'=>array_merge(array_diff_key(request::get('paramA'), $this->cfg->actionPrmClear), $this->cfg->actionPrmForce))
		));
		
		
		$this->form->setSubmitText($this->cfg->submitText);
		$this->form->setSubmitplus('<a href="'.$this->clearLink().'">'.$this->cfg->clearText.'</a>');

		if (!empty($this->cfg->fields)) {
			foreach($this->cfg->fields as $field) {
				if ($f = $this->table->getField($field)) {
					$f['label'] = $this->table->getLabel($f['name']);
					$f['link'] = $this->table->getLinked($f['name']);
					$this->form->addFromFieldFilter($f);
				} else if ($r = $this->table->getRelated($field)) {
					$r['name'] = $r['tableLink'];
					$r['label'] = $this->table->getLabel($r['table']);
					$this->form->addFromRelated($r);
				}
			}
		} else {
			// All fields
			foreach($this->table->getField() as $f) {
				$f['label'] = $this->table->getLabel($f['name']);
				$f['link'] = $this->table->getLinked($f['name']);
				$this->form->addFromFieldFilter($f);
			}

			foreach($this->table->getRelated() as $t=>$r) {
				$r['name'] = $r['tableLink'];
				$r['label'] = $this->table->getLabel($r['table']);
				$this->form->addFromRelated($r);
			}
		}

		if ($this->form->refillIfSent() || request::getPrm($this->cfg->clearPrm)) {
			$this->session->clear(true);
		} else {
			foreach($this->form->getNames() as $name) {
				if ($this->session->check($name))
					$this->form->setValue($name, $this->session->get($name));
			}
		}
	}

	/**
	 * Get the where clause
	 *
	 * @return db_where The where clause
	 */
	public function getWhere() {
		$where = $this->table->getWhere();
		foreach($this->form->getValues(true) as $name=>$val) {
			if (is_array($val)) {
				if (array_key_exists('min', $val) || array_key_exists('max', $val)) {
					$min = array_key_exists('min', $val) && !empty($val['min'])?$val['min'] : null;
					$max = array_key_exists('max', $val) && !empty($val['max'])?$val['max'] : null;
					if ($min)
						$where->add(array(
							'field'=>$this->table->getName().'.'.$name,
							'val'=>$min,
							'op'=>'>='
						));
					if ($max)
						$where->add(array(
							'field'=>$this->table->getName().'.'.$name,
							'val'=>$max,
							'op'=>'<='
						));
				} else {
					$field = $this->table->getRelated($name);
					$where->add(array(
						'field'=>$field['tableLink'].'.'.$field['fk2']['name'],
						'val'=>$val,
						'op'=>'IN'
					));
				}
			} else if(strpos($name, '_file')) {
				$where->add(array(
					'field'=>$this->table->getName().'.'.$name,
					'val'=>'',
					'op'=>'<>'
				));
			} else {
				$tmp = explode(' ', $val);
				array_walk($tmp, create_function('&$v', '$v = trim($v);'));
				$tmp = array_filter($tmp);
				foreach($tmp as $t) {
					$where->add(array(
						'field'=>$this->table->getName().'.'.$name,
						'val'=>'%'.$t.'%',
						'op'=>'LIKE'
					));
				}
			}
			$this->session->set(array(
				'name'=>$name,
				'val'=>$val
			));
		}

		if (count($where)) {
			return $where;
		}
		return null;
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