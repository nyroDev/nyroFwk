<?php
/**
 * @author Cédric Nirousset <cedric@nyrodev.com>
 * @version 0.2
 * @package nyroFwk
 */
/**
 * Form captcha element
 */
class form_captcha extends form_text {

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

	public function getValid() {
		return null;
	}

	public function isValid() {
		$ret = empty($this->cfg->value);
		if (!$ret && $this->cfg->errorFct && is_callable($this->cfg->errorFct))
			call_user_func($this->cfg->errorFct);
		return $ret;
	}

	public function getErrors() {
		return $this->isValid()? array() : array($this->cfg->error);
	}

	public function addRule($type, $prm=null) {}

}