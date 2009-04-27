<?php
abstract class security_abstract extends object {

	/**
	 * Check if the user is logged
	 *
	 * @return bool
	 */
	abstract public function isLogged();

	/**
	 * Login the current user
	 *
	 * @param mixed $prm
	 * @return bool True if successful
	 */
	abstract public function login($prm = null);

	/**
	 * Logout the current user
	 *
	 * @param mixed $prm
	 * @return bool True if successful
	 */
	abstract public function logout($prm = null);

	/**
	 * Add a role to the current user
	 *
	 * @param mixed $role
	 * @return bool True if successful
	 */
	abstract public function addRole($role);

	/**
	 * Check if the current user has a specific role or retrun the whole roles
	 *
	 * @param null|mixed $role null to get all roles
	 * @return array|bool Array of roles or bool
	 */
	abstract public function hasRole($role=null);

	/**
	 * Delete a role or all roles
	 *
	 * @param null|mixed $role null to delete all roles
	 * @return bool True if successful
	 */
	abstract public function delRole($role=null);

	/**
	 * Check if the user can access to the url given in the array (request style)
	 * or the current URL if null is given
	 *
	 * @param null|array $url
	 * @param bool $redirect Indicate if the user should be directly redirected and exit the program
	 * @return bool True if authorized access
	 */
	abstract public function check(array $url = null, $redirect=true);

	/**
	 * Get the login Form Object
	 *
	 * @return form
	 */
	abstract public function getLoginForm();

	/**
	 * Redirect the user if not logged
	 *
	 * @param null|string $page Page to redirect or configured page forbidden if not provided
	 * @return true|void True if allowed, will be redirected if not
	 */
	public function protect($page = null) {
		if (!$this->isLogged())
			response::getInstance()->redirect(request::uri($page? $page : $this->getPage('forbidden')));
		return true;
	}

	/**
	 * Get a configured page
	 *
	 * @param string $type Pagename (login, logged, logout, forbidden)
	 * @param bool $uri Indiciate if the url should be parsed with request::uri to be used directly
	 * @return string The page url
	 */
	public function getPage($type='login', $uri=false) {
		$page = $this->cfg->getInArray('pages', $type);
		return $uri? request::uri($page) : $page;
	}

	/**
	 * Indicate if an configuration array is contained in the url
	 *
	 * @param array $url
	 * @param array $checks
	 * @return bool True if a line in $checks matches the $url
	 */
	protected function isContained(array $url, array $checks) {
		foreach($checks as $c) {
			$tmp = array_intersect_key($url, $c);
			$nbM = 0;
			foreach($tmp as $k=>$v)
				if (!is_array($v))
					$nbM += (preg_match('/'.$c[$k].'/', $v)? 1 : 0);

			if ($nbM == count($tmp))
				return true;
		}
		return false;
	}
}