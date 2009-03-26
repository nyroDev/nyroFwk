<?php

class form_captcha extends form_text {
	/**
	 * Set up the valid object
	 */
	protected function afterInit() {
		if (!$this->label && !is_bool($this->label))
			$this->label = ucfirst($this->name);
		if (!is_object($this->cfg->value))
			$this->cfg->value = utils::htmlOut($this->cfg->value);
		$this->id = str_replace(
			array('[]', '[', ']'),
			array('_', '_', ''),
			$this->name);
	}

	/**
	 * Get the valid object
	 *
	 * @return valid
	 */
	public function getValid() {
		return null;
	}

	/**
	 * Check if the element is valid by using the valid object
	 *
	 * @return bool True if valid
	 */
	public function isValid() {
		$ret = empty($this->cfg->value);
		if (!$ret && $this->cfg->errorFct && is_callable($this->cfg->errorFct))
			call_user_func($this->cfg->errorFct);
		return $ret;
	}

	/**
	 * Get all the errors for the last validation
	 *
	 * @return array
	 */
	public function getErrors() {
		return $this->isValid()? array() : array($this->cfg->error);
	}

	/**
	 * Add a rule to the validation
	 *
	 * @param string $type Validation type
	 * @param array $prm Parameter for this rule
	 */
	public function addRule($type, $prm=null) {}
}