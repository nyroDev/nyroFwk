<?php
/**
 * @author Cedric Nirousset <cedric@nyrodev.com>
 * @version 0.2
 * @package nyro
 */
/**
 * Form numeric element
 */
abstract class form_range_abstract extends form_abstract {

	/**
	 * Set the form element value
	 *
	 * @param mixed $value The value
	 */
	public function setValue($value, $key=null) {
		$value = utils::htmlOut($value);
		if (is_array($value)) {
			$min = array_key_exists('min', $value)? $value['min'] : (array_key_exists(0, $value)? $value[0] : null);
			$max = array_key_exists('max', $value)? $value['max'] : (array_key_exists(1, $value)? $value[1] : null);
			$this->cfg->setInArray('rangeValue', 'min', $min);
			$this->cfg->setInArray('rangeValue', 'max', $max);
		} else if (is_null($key)) {
			$this->cfg->setInArray('rangeValue', $key, $value);
		}
	}


	/**
	 * Get the actual value
	 *
	 * @return mixed
	 */
	public function getValue($key=null) {
		if (!is_null($key))
			$ret = $this->cfg->getInArray('rangeValue', $key);
		else
			$ret = $this->rangeValue;
		return utils::htmlDeOut($ret);
	}

}
