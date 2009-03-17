<?php
class security_public extends security_abstract {
	/**
	 * Check if the user is logged
	 *
	 * @return bool
	 */
	public function isLogged() {
		return false;
	}

	/**
	 * Login the current user
	 *
	 * @param mixed $prm
	 * @return bool True if successful
	 */
	public function login($prm = null) {
		return false;
	}

	/**
	 * Logout the current user
	 *
	 * @param mixed $prm
	 * @return bool True if successful
	 */
	public function logout($prm = null) {
		return true;
	}

	/**
	 * Add a role to the current user
	 *
	 * @param mixed $role
	 * @return bool True if successful
	 */
	public function addRole($role) {
		return false;
	}

	/**
	 * Check if the current user has a specific role or retrun the whole roles
	 *
	 * @param null|mixed $role null to get all roles
	 * @return array|bool Array of roles or bool
	 */
	public function hasRole($role=null) {
		return false;
	}

	/**
	 * Delete a role or all roles
	 *
	 * @param null|mixed $role null to delete all roles
	 * @return bool True if successful
	 */
	public function delRole($role=null) {
		return true;
	}

	/**
	 * Check if the user can access to the url given in the array (request style)
	 * or the current URL if null is given
	 *
	 * @param null|array $url
	 * @param bool $redirect Indicate if the user should be directly redirected and exit the program
	 * @return bool True if authorized access
	 */
	public function check(array $url = null, $redirect=true) {
		if (is_null($url))
			$url = request::get();

		$hasRight = !$this->isContained($url, $this->cfg->spec);

		if (!$hasRight && $redirect) {
			$request = request::removeLangOutUrl('/'.request::get('request'));
			if ($request != $this->getPage('forbidden') && $request != $this->getPage('login')) {
				session::setFlash('nyroError', 'Don\'t have the permission to access to this page.');
				response::getInstance()->redirect($this->getPage('forbidden', true), 403);
			}
		}

		return $hasRight;
	}

	/**
	 * Get the login Form Object
	 *
	 * @return form
	 */
	public function getLoginForm() {
		return null;
	}
}