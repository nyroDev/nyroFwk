<?php
class form_subs extends form_abstract {

	protected function afterInit() {
		parent::afterInit();
		$this->initFields();
	}
	
	protected $fields;
	protected $hasFile = 0;
	
	public function initFields() {
		if (is_null($this->fields)) {
			$this->fields = array();
			foreach($this->cfg->subs as $name=>$val) {
				$this->fields[$name] = factory::get('form_'.$val['type'], array_merge($val['options'], array(
					'name'=>$this->name.'['.$name.']',
					'value'=>$this->getSubValue($name)
				)));
				if ($this->fields[$name]->hasFile())
					$this->hasFile++;
			}
		}
	}
	
	public function getFields() {
		$this->initFields();
		return $this->fields;
	}
	
	public function getField($name) {
		$this->getFields();
		return isset($this->fields[$name]) ? $this->fields[$name] : null;
	}
	
	public function getValue() {
		$ret = array();
		foreach ($this->getFields() as $name=>$field)
			$ret[$name] = $field->getValue();
		return $ret;
	}
	
	public function setValue($value, $refill = false) {
		foreach ($value as $name=>$val) {
			$this->setSubValue($name, $val, $refill);
		}
	}
	
	public function getSubValue($name) {
		$value = $this->getValue();
		$valueData = $this->cfg->value;
		return isset($value[$name]) ? $value[$name] : (isset($valueData[$name]) ? $valueData[$name] : null);
	}
	
	public function setSubValue($name, $value, $refill = false) {
		$field = $this->getField($name);
		if ($field)
			$field->setValue($value, $refill);
	}
	
	public function hasFile() {
		return $this->hasFile > 0;
	}
	
	/**
	 * Get all the errors for the last validation
	 *
	 * @return array
	 */
	public function getErrors() {
		$errors = array();
		foreach ($this->getFields() as $field) {
			$errors = array_merge_recursive($errors, $field->getErrors());
		}
		return array_merge_recursive($errors, $this->customErrors);
	}
	
	public function isValid() {
		$valid = empty($this->customErrors);
		if ($valid) {
			foreach ($this->getFields() as $field) {
				$valid = $valid && $field->isValid();
			}
		}
		return $valid;
	}
	
	public function toHtml() {
		$htmlContent = null;
		foreach($this->getFields() as $name=>$f) {
			$classes = 'formSubs_'.$name.' formSubs_'.$f->id;
			$htmlContent.= str_replace(
					array('[class]', '[name]', '[label]', '[field]'),
					array($classes, $f->id, $f->label, $f.''),
					$this->htmlLine);
		}
		
		return utils::htmlTag($this->htmlTagName,
			array_merge($this->html, array(
				'id'=>$this->id,
			)), $htmlContent);
	}

}