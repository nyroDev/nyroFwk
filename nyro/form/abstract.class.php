<?php
/**
 * @author Cédric Nirousset <cedric@nyrodev.com>
 * @version 0.2
 * @package nyroFwk
 */
/**
 * abstract class for form element
 */
abstract class form_abstract extends object {

	/**
	 * Validation object
	 *
	 * @var valid
	 */
	protected $valid;

	/**
	 * Errors manually added
	 *
	 * @var array
	 */
	protected $customErrors = array();

	/**
	 * Set up the valid object
	 */
	protected function afterInit() {
		if (!$this->label && !is_bool($this->label))
			$this->label = ucfirst($this->name);
		if (!is_object($this->cfg->value))
			$this->cfg->value = utils::htmlOut($this->cfg->value);
		$this->id = $this->makeId($this->name);
		$val = &$this->cfg->getRef('value');
		$this->valid = factory::get($this->cfg->validType, array(
			'value'=>&$val,
			'label'=>$this->label,
			'validEltArray'=>$this->cfg->getInArray('valid', 'validEltArray'),
		));
		$this->cfg->delInArray('valid', 'validEltArray');
		$this->initValid();
	}

	public function renew() {
		$this->id = $this->makeId($this->name);
	}

	/**
	 * Make a valid id from a name
	 *
	 * @param string $name
	 * @return string
	 */
	protected function makeId($name) {
		return str_replace(
			array('[]', '[', ']'),
			array('_', '_', ''),
			$name);
	}
	/**
	 * Get the field name
	 *
	 * @return string
	 */
	public function getName() {
		return $this->cfg->name;
	}

	/**
	 * Get the actual value
	 *
	 * @return mixed
	 */
	public function getValue() {
		return utils::htmlDeOut($this->cfg->value);
	}

	/**
	 * Get the raw value
	 *
	 * @return mixed
	 */
	public function &getRawValue() {
		return $this->cfg->getRef('value');
	}

	/**
	 * Set the form element value
	 *
	 * @param mixed $value The value
	 * @param boolean $refill Indicate if the value is a refill one
	 */
	public function setValue($value, $refill=false) {
		if ($this->cfg->disabled)
			return;
		$this->cfg->set('value', utils::htmlOut($value));
	}

	/**
	 * Get the description field
	 *
	 * @return string
	 */
	public function getDescription() {
		$ret = $this->cfg->description;
		if ($this->cfg->outDescription)
			$ret = utils::htmlOut($ret);
		return $ret;
	}

	/**
	 * Set the validation rules
	 */
	protected function initValid() {
		$valid = $this->cfg->valid;
		if (is_array($valid)) {
			foreach($valid as $type=>$prm)
				if ($prm)
					$this->addRule($type, $prm);
		} else
			$this->addRule($valid);
	}

	/**
	 * Get the valid object
	 *
	 * @return valid
	 */
	public function getValid() {
		return $this->valid;
	}

	/**
	 * Check if the element is valid by using the valid object
	 *
	 * @return bool True if valid
	 */
	public function isValid() {
		return $this->valid->isValid() && empty($this->customErrors);
	}

	/**
	 * Get a valide rule config
	 *
	 * @param string $name Rule name
	 * @return null|mixed Null if not set or configuration if existing
	 */
	public function getValidRule($name) {
		if (!$this->valid)
			return null;
		$tmp = $this->valid->getRules();
		return array_key_exists($name, $tmp) ? $tmp[$name] : null;
	}

	/**
	 * Set the disabled state
	 *
	 * @param boolean $disabled
	 */
	public function setDisabled($disabled) {
		$this->cfg->disabled = $disabled;
		if ($disabled)
			$this->cfg->setInArray('html', 'disabled', 'disabled');
		else
			$this->cfg->delInArray('html', 'disabled');
	}

	/**
	 * Get all the errors for the last validation
	 *
	 * @return array
	 */
	public function getErrors() {
		return array_merge_recursive($this->valid->getErrors(), $this->customErrors);
	}

	/**
	 * Add a custom error
	 *
	 * @param string $error The error text
	 */
	public function addCustomError($error) {
		$this->customErrors[] = $error;
	}

	/**
	 * Add a rule to the validation
	 *
	 * @param string $type Validation type
	 * @param array $prm Parameter for this rule
	 */
	public function addRule($type, $prm=null) {
		$this->valid->addRule($type, $prm);
	}

	/**
	 * Check if the element is hidden
	 *
	 * @return bool
	 */
	public function isHidden() {
		return false;
	}

	/**
	 * Get the form element type
	 *
	 * @return string
	 */
	public function getType() {
		return substr(get_class($this), strlen('form_'));
	}

	/**
	 * Transform the element to a string to be shown
	 *
	 * @param string $type The output type
	 * @return string
	 */
	public function to($type) {
		return $this->{'to'.ucfirst($type)}();
	}

	/**
	 * Transform the element in a HTML string
	 */
	abstract public function toHtml();

	/**
	 * Transform the element in a XUL string
	 */
	public function toXul() {
		return '';
	}

	/**
	 * Transform the element to a string to be shown, with the courant output
	 *
	 * @return string
	 */
	public function __toString() {
		return $this->to(request::get('out'));
	}

	/**
	 * Get a variable directly from the configuration, with a convenient way $config->name
	 *
	 * @param string $name Value requested
	 * @return mixed The value requested, null if it doesn't exists
	 */
	public function __get($name) {
		return $this->cfg->get($name);
	}

	/**
	 * Set a variable directly from the configuration, with a convenient way $config->name = $value
	 *
	 * @param string $name Value requested
	 * @param mixed $value Value to set
	 */
	public function __set($name, $val) {
		return $this->cfg->set($name, $val);
	}

}
