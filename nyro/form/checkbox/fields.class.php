<?php
/**
 * @author Cédric Nirousset <cedric@nyrodev.com>
 * @version 0.2
 * @package nyroFwk
 */
/**
 * Form checkbox fields element
 */
class form_checkbox_fields extends form_checkbox {

	/**
	 * The form used to create subFields
	 *
	 * @var form_db
	 */
	protected $form;

	/**
	 * The Name used to replace in the form
	 *
	 * @var string
	 */
	protected $replaceName;

	/**
	 * The values used for the valid object
	 *
	 * @var array
	 */
	protected $valuesForValid = array();

	protected function afterInit() {
		parent::afterInit();

		$this->form = factory::get('form_db', array_merge($this->cfg->formOpts, array(
			'table'=>$this->cfg->table,
		)));
		$this->replaceName = str_replace('[]', '', $this->name).'_fields['.$this->cfg->replaceKey.'][[name]]';
		foreach($this->cfg->fields as $f) {
			$f['name'] = str_replace('[name]', $f['name'], $this->replaceName);
			$this->form->addFromField($f);
		}

		$this->prepareValuesForValid();
		$this->valid->getCfg()->setRef('value', $this->valuesForValid);
	}

	/**
	 * Prepare the values for the valid object
	 */
	protected function prepareValuesForValid() {
		$this->valuesForValid = array();
		$value = $this->getValue();
		if (is_array($value)) {
			foreach($value as $v) {
				$this->valuesForValid[] = $v[db::getCfg('relatedValue')];
			}
		}
	}

	public function setValue($value, $refill=false) {
		if ($refill) {
			$vals = http_vars::getInstance()->post($this->name.'_fields');
			$tmpVal = $value;
			$value = array();
			if (is_array($tmpVal)) {
				foreach($tmpVal as $v) {
					$curVal = array(
						db::getCfg('relatedValue')=>$v
					);
					foreach($this->cfg->fields as $f) {
						$curVal[$f['name']] = isset($vals[$v]) && isset($vals[$v][$f['name']]) ? $vals[$v][$f['name']] : null;
					}
					$value[] = $curVal;
				}
			}
		}
		parent::setValue($value);
		$this->prepareValuesForValid();
	}
	
	public function to($type) {
		if ($type == 'html' && $this->cfg->mode == 'edit') {
			response::getInstance()->addJs('checkboxFields');
		}
		return parent::to($type);
	}

	protected function updateLine($type, $val, $line) {
		$form = clone $this->form;

		if (is_array($this->cfg->value))
			foreach($this->cfg->value as $v) {
				if ($v[db::getCfg('relatedValue')] == $val) {
					foreach($v as $k=>$vv) {
						if ($k != db::getCfg('relatedValue')) {
							$form->setValue(str_replace('[name]', $k, $this->replaceName), $vv);
						}
					}
				}
			}
		foreach($this->cfg->fields as $f) {
			$name = str_replace('[name]', $f['name'], $this->replaceName);
			$form->get($name)->getCfg()->name = str_replace($this->cfg->replaceKey, $val, $name);
			$form->get($name)->renew();
		}

		return str_replace('[fields]', $form->__toString(), $line);
	}
	
	public function isInValue($val) {
		if (is_array($this->cfg->value))
			foreach($this->cfg->value as $v) {
				if ($v[db::getCfg('relatedValue')] == $val)
					return true;
			}
		return false;
	}
}
