<?php
/**
 * @author Cédric Nirousset <cedric@nyrodev.com>
 * @version 0.2
 * @package nyroFwk
 */
/**
 * Form numeric element
 */
abstract class form_range_abstract extends form_abstract {

	public function toHtml() {
		return str_replace('[name]', $this->name, sprintf($this->cfg->template,
			utils::htmlTag($this->htmlTagName,
				array_merge($this->html, array(
					'name'=>$this->name.'[0]',
					'id'=>$this->makeId($this->name.'[0]'),
					'value'=>$this->getValue('min', 'input'),
				))),
			utils::htmlTag($this->htmlTagName,
				array_merge($this->html, array(
					'name'=>$this->name.'[1]',
					'id'=>$this->makeId($this->name.'[1]'),
					'value'=>$this->getValue('max', 'input'),
				)))
			));
	}

	/**
	 * Set the form element value
	 *
	 * @param mixed $value The value
	 * @param boolean $refill Indicate if the value is a refill one
	 * @param null|string $key Null if set both values or string to set only one value
	 */
	public function setValue($value, $refill=false, $key=null) {
		$value = utils::htmlOut($value);
		if (is_array($value)) {
			$min = array_key_exists('min', $value)? $value['min'] : (array_key_exists(0, $value)? $value[0] : null);
			$max = array_key_exists('max', $value)? $value['max'] : (array_key_exists(1, $value)? $value[1] : null);
			$this->cfg->setInArray('rangeValue', 'min', $min);
			$this->cfg->setInArray('rangeValue', 'max', $max);
			$this->cfg->value = $value;
		} else if (!is_null($key)) {
			$this->cfg->setInArray('rangeValue', $key, $value);
			$this->cfg->setInArray('value', $key, $value);
		}
	}

	/**
	 * Get the actual value
	 *
	 * @param null|string $key Null to get both values or string to get only one
	 * @param string $mode How get the value in case of retriving only one value
	 * @return mixed
	 */
	public function getValue($key=null, $mode='raw') {
		if (!is_null($key))
			$ret = $this->cfg->getInArray('rangeValue', $key);
		else
			$ret = $this->rangeValue;
		return utils::htmlDeOut($ret);
	}

}
