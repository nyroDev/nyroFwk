<?php
/**
 * @author Cédric Nirousset <cedric@nyrodev.com>
 * @version 0.2
 * @package nyroFwk
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
	 * Add a rule from the valdiation
	 *
	 * @param string $type Validation type
	 * @param array $prm Parameter for this rule
	 * @param string $msg The error message
	 */
	public function addRule($type, $prm=null, $msg=null) {
		if (!is_array($prm) && !is_callable($prm))
			$prm = array($prm);
		$this->cfg->setInArray('rules', $type, $prm);
		if (!is_null($msg))
			$this->setMessage($type, $msg);
	}

	/**
	 * Delete a rule from the valdiation
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
		$noNeedRequired = $this->cfg->noNeedRequired;
		$val = $this->cfg->validEltArray && is_array($this->cfg->value) ? $this->cfg->value : array($this->cfg->value);
		foreach($this->cfg->rules as $rule=>$prm) {
			if (!is_numeric($rule)) {
				foreach($val as $v) {
					if ((in_array($rule, $noNeedRequired)) || !empty($v))
						$valid = $this->{'is'.ucfirst($rule)}($v, $prm) && $valid;
				}
			}
		}
		return $valid;
	}

	/**
	 * Check if the value is here
	 *
	 * @param mixed $val The value to test against
	 * @param null $prm The parameter for the test (not used here)
	 * @return bool True if valid
	 */
	public function isRequired($val, $prm=null) {
		if (empty($val)) {
			$this->errors[] = sprintf($this->getMessage('required'), $this->cfg->label);
			return false;
		}
		return true;
	}

	/**
	 * Check if the value is numeric
	 *
	 * @param mixed $val The value to test against
	 * @param null $prm The parameter for the test (not used here)
	 * @return bool True if valid
	 */
	public function isNumeric($val, $prm=null) {
		if (!is_numeric($val)) {
			$this->errors[] = sprintf($this->getMessage('numeric'), $this->cfg->label);
			return false;
		}
		return true;
	}

	/**
	 * Check if the value is integer
	 *
	 * @param mixed $val The value to test against
	 * @param null $prm The parameter for the test (not used here)
	 * @return bool True if valid
	 */
	public function isInt($val, $prm=null) {
		if (!is_numeric($val) || round($val) != $val || strlen(round($val).'') != strlen($val)) {
			$this->errors[] = sprintf($this->getMessage('int'), $this->cfg->label);
			return false;
		}
		return true;
	}

	/**
	 * Check if the value is different. Strandard PHP test is done
	 *
	 * @param mixed $val The value to test against
	 * @param mixed $prm The parameter for the test. Key 0 should be here and is the value to test against
	 * @return bool True if valid
	 */
	public function isDifferent($val, $prm=null) {
		if ($val == $prm[0]) {
			$this->errors[] = sprintf($this->getMessage('different'), $this->cfg->label, $prm[0]);
			return false;
		}
		return true;
	}

	/**
	 * Check if the value is contained in an array
	 *
	 * @param mixed $val The value to test against
	 * @param array|string $prm The parameter for the test Array to search in or string to test directly against
	 * @return bool True if valid
	 */
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

	/**
	 * Check if the value is equal to another
	 *
	 * @param mixed $val The value to test against
	 * @param form_abstract|string $prm The parameter for the test. If form_abstract is provided, the rawValue will be used for the test
	 * @return bool True if valid
	 */
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

	/**
	 * Check if all parametred fields of a form are all eiher empty or all present
	 *
	 * @param mixed $val The value to test against (not used)
	 * @param array $prm An array with the keys:
	 * - form form: The form where to retrieve the values
	 * - array fields: The grouped fields names
	 * @return bool True if valid
	 */
	public function isGroupedFields($val, $prm=null) {
		$nbFilled = 0;
		foreach($prm['fields'] as $f) {
			$val = $prm['form']->getValue($f);
			if (!empty($val))
				$nbFilled++;
		}
		$ret = $nbFilled == 0 || $nbFilled == count($prm['fields']);
		if (!$ret) {
			// not valid
			$this->errors[] = sprintf($this->getMessage('groupedFields'), $this->cfg->label);
			return false;
		}
		return true;
	}

	/**
	 * Check if all at least one of the paramtred fields is present
	 *
	 * @param mixed $val The value to test against (not used)
	 * @param array $prm An array with the keys:
	 * - form form: The form where to retrieve the values
	 * - array fields: The fields names
	 * @return bool True if valid
	 */
	public function isAtLeastOneField($val, $prm=null) {
		$nbFilled = 0;
		foreach($prm['fields'] as $f) {
			$val = $prm['form']->getValue($f);
			if (!empty($val))
				$nbFilled++;
		}
		if ($nbFilled == 0) {
			// not valid
			$this->errors[] = sprintf($this->getMessage('atLeastOneField'), $this->cfg->label);
			return false;
		}
		return true;
	}

	/**
	 * Check if the value using a callback
	 *
	 * @param mixed $val The value to test against
	 * @param mixed $prm A valid PHP callback. This callback should true if valid or false if not or a string if a specific message should be used
	 * @return bool True if valid
	 */
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

	/**
	 * Check if the value is an URL (starting with http)
	 *
	 * @param mixed $val The value to test against
	 * @param null $prm The parameter for the test (not used here)
	 * @return bool True if valid
	 */
	public function isUrl($val, $prm=null) {
		if (!filter_var($val, FILTER_VALIDATE_URL, FILTER_FLAG_SCHEME_REQUIRED)) {
			$this->errors[] = sprintf($this->getMessage('url'), $this->cfg->label);
			return false;
		}
		return true;
	}

	/**
	 * Check if the value is an email address
	 *
	 * @param mixed $val The value to test against
	 * @param null $prm The parameter for the test (not used here)
	 * @return bool True if valid
	 */
	public function isEmail($val, $prm=null) {
		if (!filter_var($val, FILTER_VALIDATE_EMAIL)) {
			$this->errors[] = sprintf($this->getMessage('email'), $this->cfg->label);
			return false;
		}
		return true;
	}

	/**
	 * Check if the value is not present in a database table
	 *
	 * @param mixed $val The value to test against
	 * @param array $prm The parameter for the test with keys:
	 *  - mixed value: a value to ignore the test
	 *  - db_table|string: Table object or tablename (required)
	 *  - string field: Fieldname to test against
	 * @return bool True if valid
	 */
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

	/**
	 * Check if the value exists in a database table
	 *
	 * @param mixed $val The value to test against
	 * @param array $prm The parameter for the test with keys:
	 *  - db_table|string: Table object or tablename (required)
	 *  - string field: Fieldname to test against
	 * @return bool True if valid
	 */
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

	/**
	 * Get a message for an error
	 *
	 * @param string $name Error name
	 * @return string The message
	 */
	protected function getMessage($name) {
		return utils::htmlOut($this->cfg->getInArray('messages', $name));
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
