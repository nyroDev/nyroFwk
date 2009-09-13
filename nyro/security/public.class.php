<?php
/**
 * @author Cedric Nirousset <cedric@nyrodev.com>
 * @version 0.2
 * @package nyro
 */
/**
 * Security class to check user rights, which allow everything to anybody by default
 */
class security_public extends security_abstract {

	public function isLogged() {
		return false;
	}

	public function login($prm = null, $page = null) {
		$this->hook('login');
		return false;
	}

	public function logout($prm = null) {
		$this->hook('logout');
		return true;
	}

	public function addRole($role) {
		return false;
	}

	public function hasRole($role=null) {
		return false;
	}

	public function delRole($role=null) {
		return true;
	}

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

	public function getLoginForm() {
		return null;
	}
}