<?php
/**
 * @author Cedric Nirousset <cedric@nyrodev.com>
 * @version 0.2
 * @package nyro
 */
/**
 * Manage session variable
 * Singleton
 */
class session_php extends session_abstract {

	/**
	 * Start the session
	 */
	protected function afterInit() {
		if (!session_id()) {
			if (isset($_GET['phpsessidForce']))
				session_id($_GET['phpsessidForce']);
			session_start();
			if ($this->cfg->regenerateId)
				session_regenerate_id(true);
		}
	}

	public function get($prm) {
		if ($this->check($prm)) {
			if (is_array($prm)) {
				$this->setNameSpaceInArray($prm);
				config::initTab($prm, array(
					'name'=>null,
					'serialize'=>false
				));
				$name = $this->prefixNameSpace($prm['name']);
				$serialize = $prm['serialize'];
			} else {
				$name = $this->prefixNameSpace($prm);
				$serialize = false;
			}
			return $serialize? unserialize($_SESSION[$name]) : $_SESSION[$name];
		}
		return null;
	}

	public function getAll() {
		$ret = array();
		$prefix = $this->prefixNameSpace('');
		$prefixLn = strlen($prefix);
		foreach($_SESSION as $k=>$v) {
			if (strpos($k, $prefix) === 0)
				$ret[substr($k, $prefixLn)] = $v;
		}
		return $ret;
	}

	public function set(array $prm) {
		$this->setNameSpaceInArray($prm);
		config::initTab($prm, array(
			'name'=>null,
			'val'=>null,
			'serialize'=>false
		));
		$_SESSION[$this->prefixNameSpace($prm['name'])] = $prm['serialize']? serialize($prm['val']) : $prm['val'];
	}

	public function check($prm) {
		if (is_array($prm)) {
			$this->setNameSpaceInArray($prm);
			config::initTab($prm, array(
				'name'=>null,
			));
			$name = $prm['name'];
		} else {
			$name = $prm;
		}
		return isset($_SESSION[$this->prefixNameSpace($name)]);
	}

	/**
	 * Delete a session variable
	 *
	 * @param string|array $prm Array parameter:
	 *  - string name: variable name
	 *  - string nameSpace: variable name space
	 * @param bool $autoPrefix
	 */
	public function del($prm, $autoPrefix=true) {
		if (is_array($prm)) {
			$this->setNameSpaceInArray($prm);
			$name = $prm['name'];
		} else {
			$name = $prm;
		}
		if ($autoPrefix)
			$name = $this->prefixNameSpace($name);
		unset($_SESSION[$name]);
	}

	public function clear($nameSpace=true) {
		$tmp = array_keys($_SESSION);

		if ($nameSpace === true)
			$nameSpace = $this->getNameSpace();

		if (is_string($nameSpace)) {
			$this->setNameSpace($nameSpace);
			$tmp = array_filter($tmp, create_function('$v', 'return (strpos($v, "'.$this->prefixNameSpace('').'") === 0);'));
		}

		foreach($tmp as $v)
			$this->del($v, false);
	}

	/**
	 * Prefix the session variable name and add the namespace
	 */
	private function prefixNameSpace($name) {
		$nameSpace = $this->cfg->nameSpace? $this->cfg->nameSpace.'_' : null;
		return $this->cfg->prefix.$nameSpace.$name;
	}
}
