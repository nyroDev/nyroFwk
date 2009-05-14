<?php
/**
 * @author Cedric Nirousset <cedric@nyrodev.com>
 * @version 0.2
 * @package nyro
 */
/**
 * Use this interface to implements a session
 */
abstract class session_abstract extends object {

	/**
	 * Get the current name space
	 *
	 * @return string
	 */
	public function getNameSpace() {
		return $this->cfg->nameSpace;
	}

	/**
	 * Set the name space to use
	 *
	 * @param string $nameSpace
	 */
	public function setNameSpace($nameSpace) {
		$this->cfg->nameSpace = $nameSpace;
	}

	/**
	 * Set the name space if set in the array
	 *
	 * @param array $prm
	 */
	protected function setNameSpaceInArray(array $prm) {
		if (array_key_exists('nameSpace', $prm))
			$this->cfg->nameSpace = $prm['nameSpace'];
	}

	/**
	 * Get a session variable
	 *
	 * @param string|array $prm Array parameter:
	 *  - string name: variable name
	 *  - string nameSpace: variable name space
	 *  - bool serialize: If true, the variable will be unserialize
	 * @return mixed The variable or null if it doesn't exists
	 */
	abstract public function get($prm);

	/**
	 * Get all session variables
	 *
	 * @return array
	 */
	abstract public function getAll();

	/**
	 * Set a session variable
	 *
	 * @param array $prm Array parameter:
	 *  - string name: variable name
	 *  - mixed val: variable value
	 *  - string nameSpace: variable name space
	 *  - bool serialize: If true, the variable will be unserialize
	 */
	abstract public function set(array $prm);

	/**
	 * check if a variable is set
	 *
	 * @param string|array $prm Array parameter:
	 *  - string name: variable name
	 *  - string nameSpace: variable name space
	 * @return bool
	 */
	abstract public function check($prm);

	/**
	 * Delete a session variable
	 *
	 * @param string|array $prm Array parameter:
	 *  - string name: variable name
	 *  - string nameSpace: variable name space
	 */
	abstract public function del($prm);

	/**
	 * Clear all the session variables and start a new session
	 *
	 * @param string|true|null $nameSpace If string will clear only the name space, if true will clear the current name space
	 */
	abstract public function clear($nameSpace=true);

	/**
	 * Get a session variable, with a convenient way $session->name
	 *
	 * @param string $name The variable name
	 * @return mixed The variable or null if it doesn't exists
	 * @see get
	 */
	public function __get($name) {
		return $this->get($name);
	}

	/**
	 * Set a variable, with a convenient way $session->name = $value
	 *
	 * @param string $name The variable name
	 * @param mixed $val The variable value
	 * @see set
	 */
	public function __set($name, $val) {
		$this->set(array('name'=>$name, 'val'=>$val));
	}

	/**
	 * Check if a session variable is set, with a convenient way isset($session->name)
	 *
	 * @param string $name variable name to check
	 * @return bool
	 * @see check
	 */
	public function __isset($name) {
		return $this->check($name);
	}

	/**
	 * Delete a variable, with a convenient way unset($session->name)
	 *
	 * @param string $name Variable name to delete
	 * @see del
	 */
	public function __unset($name) {
		$this->del($name);
	}
}
