<?php
/**
 * @author Cedric Nirousset <cedric@nyrodev.com>
 * @version 0.2
 * @package nyro
 */
/**
 * Class using for validate a data
 */
class valid extends object {

	/**
	 * Errors for the last validation
	 *
	 * @var array
	 */
	protected $errors = array();

	/**
	 * Get the value
	 *
	 * @return mixed
	 */
	public function getValue() {
		return $this->cfg->value;
	}

	/**
	 * Set a new value reference
	 *
	 * @param mixed $value the new Value
	 */
	public function setValue(&$value) {
		$this->cfg->set('value', $value);
	}

	/**
	 * Add a rule to the validation
	 *
	 * @param string $type Validation type
	 * @param array $prm Parameter for this rule
	 */
	public function addRule($type, $prm=null) {
		if (!is_array($prm))
			$prm = array($prm);
		$this->cfg->setInArray('rules', $type, $prm);
	}

	/**
	 * delete a rule
	 *
	 * @param string $type Validation type
	 */
	public function delRule($type) {
		$this->cfg->delInArray('rules', $type);
	}

	/**
	 * Get the validation rules array
	 *
	 * @return array
	 */
	public function getRules() {
		return $this->cfg->rules;
	}

	/**
	 * Process to the validation
	 *
	 * @return bool True if valid
	 */
	public function isValid() {
		$this->errors = array();
		$valid = true;
		foreach($this->cfg->rules as $rule=>$prm) {
			$valid = $this->{'is'.ucfirst($rule)}($prm) && $valid;
		}
		return $valid;
	}

	public function isRequired($prm=null) {
		if (empty($this->cfg->value)) {
			$this->errors[] = $this->cfg->label.' is required.';
			return false;
		}
		return true;
	}

	public function isNumeric($prm=null) {
		if (!is_numeric($this->cfg->value)) {
			$this->errors[] = $this->cfg->label.' should be numeric.';
			return false;
		}
		return true;
	}

	public function isInt($prm=null) {
		if (!is_numeric($this->cfg->value) || round($this->cfg->value) != $this->cfg->value) {
			$this->errors[] = $this->cfg->label.' should be an integer.';
			return false;
		}
		return true;
	}

	public function isDifferent($prm=null) {
		if ($this->cfg->value == $prm[0]) {
			$this->errors[] = $this->cfg->label.' should be different from '.$prm[0].'.';
			return false;
		}
		return true;
	}

	public function isIn($prm=null) {
		$ret = true;
		$val = is_array($this->cfg->value)? $this->cfg->value : array($this->cfg->value);
		foreach($val as $v) {
			if (!in_array($v, $prm)) {
				$this->errors[] = 'The value '.$v.' is not allowed for '.$this->cfg->label.'.';
				$ret = false;
			}
		}
		return $ret;
	}

	public function isEqual($prm=null) {
		$ret = true;
		if ($prm[0] instanceof form_abstract) {
			if ($this->cfg->value != $prm[0]->getRawValue()) {
				$this->errors[] = $this->cfg->label.' should be equal to '.$prm[0]->label.'.';
				$ret = false;
			}
		} else {
			if ($this->cfg->value != $prm[0]) {
				$this->errors[] = 'The value '.$v.' for '.$this->cfg->label.' is not the right one.';
				$ret = false;
			}
		}
		return $ret;
	}

	/**
	 * Get all the errors for the last validation
	 *
	 * @return array The errors, empty if no errors
	 */
	public function getErrors() {
		return $this->errors;
	}
}
