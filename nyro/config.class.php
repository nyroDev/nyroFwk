<?php
/**
 * @author Cédric Nirousset <cedric@nyrodev.com>
 * @version 0.2
 * @package nyroFwk
 */
/**
 * Configuration for all the class
 */
class config {

	const REQUIRED = 'REQUIRED';

	/**
	 * Configuration variables
	 *
	 * @var array
	 */
	private $vars;

	/**
	 * Initialize the configuration variables
	 *
	 * @param array $prm Initial variables
	 */
	public function __construct(array $prm=array()) {
		$this->vars = $prm;
	}

	/**
	 * @return string Configuration in string to debug
	 */
	public function __toString() {
		return print_r($this->vars, true);
	}

	/**
	 * Check if the configuration has all the required field
	 *
	 * @param bool $trow True to throw exception instead of only return false
	 * @return bool True if good
	 * @throws nException If argument missing and parameter $throw set to true
	 */
	public function checkCfg($throw=true) {
		foreach($this->vars as $k=>&$v) {
			$err = null;
			if (is_array($v)) {
				foreach($v as $kk=>&$vv)
					if ($vv === config::REQUIRED)
						$err = 'Config: Need '.$k.'->'.$kk.' Parameter.';
			} else if ($v === config::REQUIRED)
				$err = 'Config: Need '.$k.' Parameter.';
			if ($err) {
				if ($throw)
					throw new nException($err);
				else
					return false;
			}
		}
		return true;
	}

	/**
	 * Create and verify all the variables needed for this config.
	 * A required variable is indicate by a value null
	 *
	 * @param array $prm All the variables needed for this config
	 * @throws nExecption If one required parameter isn't present
	 */
	public function init(array $prm) {
		foreach($prm as $k=>$v) {
			if (is_null($v) && (!array_key_exists($k, $this->vars) || is_null($this->vars[$k])))
				throw new nException('Config: Need '.$k.' Parameter.');

			if (array_key_exists($k, $this->vars)) {
				if (is_null($this->vars[$k]))
					$this->vars[$k] = $v;
				else if (is_array($this->vars[$k]) && is_array($v))
					config::initTab($this->vars[$k], $v);
			} else
				$this->vars[$k] = $v;
		}
	}

	/**
	 * Add to the actual config by merge (array) or create an array
	 * with the initial variable followed by the adding elements
	 *
	 * @param array $prm All the variables to add
	 */
	public function overInit(array $prm) {
		foreach($prm as $k=>$v) {
			if (!array_key_exists($k, $this->vars))
				$this->vars[$k] = array();

			if (is_array($this->vars[$k]))
				if (is_array($v))
					foreach($v as $kk=>$vv) {
						if (is_int($kk))
							$this->vars[$k][] = $vv;
						else if (!array_key_exists($kk))
							$this->vars[$k][$kk] = $vv;
					}
				else
					$this->vars[$k][] = $v;
		}
	}

	/**
	 * Overload a config from an other class.
	 * The class could not exists
	 *
	 * @param string $className
	 * @param bool $parent Indicate if the search should load the parent config (if virtual, should be false!)
	 */
	public function overload($className, $parent=false) {
		factory::mergeCfg($this->vars, factory::loadCfg($className, $parent));
	}

	/**
	 * Get all the actual configuration
	 *
	 * @return array The configuration
	 */
	public function &getVars() {
		return $this->vars;
	}

	/**
	 * Get a variable
	 *
	 * @param string $name Variable name
	 * @return mixed|null The value requested if exists or null
	 * @see getRef
	 */
	public function get($name) {
		if ($this->check($name))
			return $this->vars[$name];
		else
			return null;
	}

	/**
	 * Get a variable inside an array
	 *
	 * @param string $name Variable name
	 * @param string $key Key requested
	 * @return mixed|null The value requested if exists or null
	 */
	public function getInArray($name, $key) {
		if ($this->check($name) && is_array($this->vars[$name]) && array_key_exists($key, $this->vars[$name]))
			return $this->vars[$name][$key];
		return null;
	}

	/**
	 * Get a configuration variable, with a convenient way $config->name
	 *
	 * @param string $name Value requested
	 * @return mixed The value requested, null if it doesn't exists
	 * @see get
	 */
	public function __get($name) {
		return $this->get($name);
	}

	/**
	 * Get a variable by reference
	 *
	 * @param string $name Variable name
	 * @return mixed The value requested if exists or null
	 * @see get
	 */
	public function &getRef($name) {
		if ($this->check($name))
			return $this->vars[$name];
		else {
			$null = null;
			return $null;
		}
	}

	/**
	 * Get the whole configuration array
	 *
	 * @return array
	 */
	public function getAll() {
		return $this->vars;
	}

	/**
	 * Set an array of variable to the config
	 *
	 * @param array $vars Variables to set
	 * @see set
	 */
	public function setA(array $vars) {
		foreach($vars as $k=>$v)
			$this->set($k, $v);
	}

	/**
	 * Set an array of variable to the config by reference
	 *
	 * @param array $vars Variables to set
	 * @see setRef
	 */
	public function setARef(array $vars) {
		foreach($vars as $k=>&$v)
			$this->setRef($k, $v);
	}

	/**
	 * Set a variable to the config
	 *
	 * @param string $name Variable name
	 * @param mixed $val Value
	 * @see setA,setRef,setARef
	 */
	public function set($name, $val) {
		$this->vars[$name] = $val;
	}

	/**
	 * Set a variable to the config in an array
	 *
	 * @param string $name Variable name
	 * @param string $key Key on the array
	 * @param mixed $val Value
	 * @see setA,setRef,setARef
	 */
	public function setInArray($name, $key, $val) {
		$this->vars[$name][$key] = $val;
	}

	/**
	 * Set variables to the config in an array
	 *
	 * @param string $name Variable name
	 * @param array $values the Values
	 * @see setInArray
	 */
	public function setInArrayA($name, array $values) {
		$this->vars[$name] = array_merge($this->vars[$name], $values);
	}

	/**
	 * Set a variable to the config by reference
	 *
	 * @param string $name Variable name
	 * @param mixed $val Value
	 * @see setARef,set,setA
	 */
	public function setRef($name, &$val) {
		$this->vars[$name] = &$val;
	}

	/**
	 * Set a variable, with a convenient way $config->name = $value
	 *
	 * @param string $name Name to set
	 * @param mixed $value Value to set
	 * @see set
	 */
	public function __set($name, $val) {
		$this->set($name, $val);
	}

	/**
	 * check if a variable is set
	 *
	 * @param string $name variable name to check
	 * @return bool
	 */
	public function check($name) {
		return array_key_exists($name, $this->vars);
	}

	/**
	 * check if a variable is set in an array
	 *
	 * @param string $name variable name to check
	 * @param string $key Key on the array
	 * @return bool
	 */
	public function checkInArray($name, $key) {
		return array_key_exists($name, $this->vars) && is_array($this->vars[$name]) && array_key_exists($key, $this->vars[$name]);
	}

	/**
	 * Check if a variable is set, with a convenient way isset($config->name)
	 *
	 * @param string $name variable name to check
	 * @return bool
	 * @see check
	 */
	public function __isset($name) {
		return $this->check($name);
	}

	/**
	 * Delete a variable
	 *
	 * @param string $name Variable name to delete
	 */
	public function del($name) {
		if ($this->check($name))
			unset($this->vars[$name]);
	}

	/**
	 * Delete a variable to the config in an array
	 *
	 * @param string $name Variable name
	 * @param string $key Key on the array
	 */
	public function delInArray($name, $key) {
		if ($this->check($name) && is_array($this->vars[$name]) && array_key_exists($key, $this->vars[$name]))
			unset($this->vars[$name][$key]);
	}

	/**
	 * Delete a variable, with a convenient way unset($config->name)
	 *
	 * @param string $name Variable name to delete
	 * @see del
	 */
	public function __unset($name) {
		$this->del($name);
	}

	/**
	 * Initialize an array with an init array.
	 * Return a boolean to indicate if all required variable are present.
	 * A required variable is indicate by a value null
	 *
	 * @param array $vars Variable array to initialize
	 * @param array $init Init array
	 * @return bool True if all required variables are present
	 */
	public static function initTab(array &$vars, array $init) {
		$ret = true;
		foreach($init as $k=>&$v) {
			if (!array_key_exists($k,$vars)) {
				if (is_null($v))
					$ret = false;
				$vars[$k] = $v;
			}
		}
		return $ret;
	}

	/**
	 * Transform a numeric array to a string key array
	 *
	 * @param array $vars Variable array to transfer
	 * @param array $varsNum Numeric Array to indicate the key
	 * @param string $nameOther Name for value non indicate in the $varsNum array, if needed
	 * @param bool $rtl True to init Right to Left or false to init Left to Right
	 */
	public static function initTabNum(array &$vars, array $varsNum, $nameOther='other', $rtl=true) {
		if (!empty($vars) && !empty($varsNum)) {
			$nbvars = count($vars);
			$nbvarsNum = count($varsNum);
			if ($nbvars < $nbvarsNum)
				array_splice($varsNum, $nbvars);
			else if ($nbvars > $nbvarsNum) {
				$tmp = array();
				for($i=$nbvarsNum; $i<$nbvars; $i++)
					$tmp[] = $nameOther.round($i-$nbvarsNum+1);
				if ($rtl)
					$varsNum = array_merge($varsNum, $tmp);
				else
					$varsNum = array_merge($tmp, $varsNum);
			}
			$vars = array_combine($varsNum, $vars);
		}
	}

}
