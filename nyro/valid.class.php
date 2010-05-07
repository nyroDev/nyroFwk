<?php
/**
 * @author Cédric Nirousset <cedric@nyrodev.com>
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
	 * @param string $msg The error message
	 */
	public function addRule($type, $prm=null, $msg=null) {
		if (!is_array($prm))
			$prm = array($prm);
		$this->cfg->setInArray('rules', $type, $prm);
		if (!is_null($msg))
			$this->setMessage($type, $msg);
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
		$val = $this->cfg->validEltArray && is_array($this->cfg->value) ? $this->cfg->value : array($this->cfg->value);
		foreach($this->cfg->rules as $rule=>$prm) {
			if (!is_numeric($rule)) {
				foreach($val as $v) {
					if ($rule == 'required' || !empty($v))
						$valid = $this->{'is'.ucfirst($rule)}($v, $prm) && $valid;
				}
			}
		}
		return $valid;
	}
	
	public function isRequired($val, $prm=null) {
		if (empty($val)) {
			$this->errors[] = sprintf($this->getMessage('required'), $this->cfg->label);
			return false;
		}
		return true;
	}

	public function isNumeric($val, $prm=null) {
		if (!is_numeric($val)) {
			$this->errors[] = sprintf($this->getMessage('numeric'), $this->cfg->label);
			return false;
		}
		return true;
	}

	public function isInt($val, $prm=null) {
		if (!is_numeric($val) || round($val) != $val) {
			$this->errors[] = sprintf($this->getMessage('int'), $this->cfg->label);
			return false;
		}
		return true;
	}

	public function isDifferent($val, $prm=null) {
		if ($val == $prm[0]) {
			$this->errors[] = sprintf($this->getMessage('different'), $this->cfg->label, $prm[0]);
			return false;
		}
		return true;
	}

	public function isIn($val, $prm=null) {
		$ret = true;
		$val = is_array($val)? $val : array($val);
		$val = array_filter($val);
		if (!empty($val))
			foreach($val as $v) {
				if (!in_array($v, $prm)) {
					$this->errors[] = sprintf($this->getMessage('in'), $v, $this->cfg->label);
					$ret = false;
				}
			}
		return $ret;
	}

	public function isEqual($val, $prm=null) {
		$ret = true;
		if ($prm[0] instanceof form_abstract) {
			if ($val != $prm[0]->getRawValue()) {
				$this->errors[] = sprintf($this->getMessage('equalInput'), $this->cfg->label, $prm[0]->label);
				$ret = false;
			}
		} else {
			if ($val != $prm[0]) {
				$this->errors[] = sprintf($this->getMessage('equal'), $v, $this->cfg->label);
				$ret = false;
			}
		}
		return $ret;
	}
	
	public function isCallback($val, $prm=null) {
		$tmp = call_user_func($prm, $val);
		if ($tmp !== true) {
			$tmp = is_string($tmp) ? $tmp : 'callback';
			$msg = $this->getMessage($tmp);
			$this->errors[] = $msg ? sprintf($msg, $this->cfg->label) : $tmp;
			return false;
		}
		return true;
	}
	
	public function isUrl($val, $prm=null) {
		if (!ereg('^http(s)?://[a-zA-Z0-9\._]+\.[a-zA-Z]{2,4}', $val)) {
			$this->errors[] = sprintf($this->getMessage('url'), $this->cfg->label);
			return false;
		}
		return true;
	}
	
	public function isEmail($val, $prm=null) {
		if (!filter_var($val, FILTER_VALIDATE_EMAIL)) {
			$this->errors[] = sprintf($this->getMessage('email'), $this->cfg->label);
			return false;
		}
		return true;
	}
	
	public function isDbUnique($val, array $prm) {
		if (array_key_exists('value', $prm) && $val == $prm['value'])
			return true;

		$table = $prm['table'] instanceof db_table? $prm['table'] : db::get('table', $prm['table']);
		$nb = $table->count(array(
			'where'=>array($prm['field']=>$val),
			'whereOp'=>'LIKE'
		));
		if ($nb > 0) {
			$this->errors[] = sprintf($this->getMessage('dbUnique'), $val, $this->cfg->label);
			return false;
		}
		return true;
	}
	
	public function isDbExists($val, array $prm) {
		$table = $prm['table'] instanceof db_table? $prm['table'] : db::get('table', $prm['table']);
		$nb = $table->count(array(
			'where'=>array($prm['field']=>$val),
			'whereOp'=>'LIKE'
		));
		if ($nb == 0) {
			$this->errors[] = sprintf($this->getMessage('dbExists'), $val, $this->cfg->label);
			return false;
		}
		return true;
	}

	protected function getMessage($name) {
		return utils::htmlOut($this->cfg->getInarray('messages', $name));
	}

	/**
	 * Set an error message
	 *
	 * @param string $name Message keyname
	 * @param string $msg The message
	 */
	public function setMessage($name, $msg) {
		$this->cfg->setInArray('messages', $name, $msg);
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
