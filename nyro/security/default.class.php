<?php
class security_default extends security_abstract {

	/**
	 * Session Object
	 *
	 * @var session_abstract
	 */
	protected $session;

	/**
	 * Table object
	 *
	 * @var db_table
	 */
	protected $table;

	/**
	 * User object (row of $table)
	 *
	 * @var db_row
	 */
	protected $user;

	/**
	 * Login form
	 *
	 * @var form
	 */
	protected $form;

	/**
	 * Indicate if the user is logged
	 *
	 * @var bool
	 */
	protected $logged = false;

	/**
	 * Roles attribuats to the user logged
	 *
	 * @var array
	 */
	protected $roles = array();

	protected function afterInit() {
		$this->session = session::getInstance(array(
			'nameSpace'=>'security_default'
		));
		$this->table = db::get('table', $this->cfg->table);
		$this->autoLogin();
	}

	/**
	 * Autologin the user with session vars or an eventual cookie
	 */
	protected function autoLogin() {
		if (!$cryptic = $this->session->cryptic) {
			// Try to check the cookie
			$cook = factory::get('http_cookie', $this->cfg->cookie);
			$cryptic = $cook->get(true);
		}

		if ($cryptic) {
			$this->user = $this->table->find(array_merge(array(
				$this->cfg->getInArray('fields', 'cryptic')=>$cryptic
			), $this->cfg->where));
			if ($this->user) {
				$this->logged = true;
				$this->session->cryptic = $cryptic;
			} else if (isset($cook))
				$cook->del();
		}
	}

	/**
	 * Check if the user is logged
	 *
	 * @return bool
	 */
	public function isLogged() {
		return $this->logged;
	}

	/**
	 * Get the user object
	 *
	 * @return null|mixed
	 */
	public function getUser() {
		if ($this->isLogged() && $this->user)
			return $this->user;
		return null;
	}

	/**
	 * Login the current user
	 *
	 * @param mixed $prm
	 * @return bool True if successful
	 */
	public function login($prm = null, $page=null) {
		$loginField = $this->cfg->getInArray('fields', 'login');
		$passField = $this->cfg->getInArray('fields', 'pass');

		if (is_null($prm)) {
			$form = $this->getLoginForm();
			if ($form->refillIfSent())
				$prm = $form->getValues(true);
		}

		if (is_array($prm)
			&& array_key_exists($loginField, $prm)
			&& array_key_exists($passField, $prm)) {
				$this->user = $this->table->find(array_merge(array(
					$loginField=>$prm[$loginField],
					$passField=>$this->cryptPass($prm[$passField])
				), $this->cfg->where));
				if ($this->user) {
					$crypticKey = $this->cfg->getInArray('fields', 'cryptic');
					if (!$cryptic = $this->user->get($crypticKey)) {
						$cryptic = $this->cryptPass(uniqid(), 'Cryptic');
						$this->user->set($crypticKey, $cryptic);
						$this->user->save();
					}
					$this->session->cryptic = $cryptic;
					if (array_key_exists('stayConnected', $prm) && $prm['stayConnected']) {
						$cook = factory::get('http_cookie', $this->cfg->cookie);
						$cook->set($cryptic);
						$cook->save();
					}
					$this->logged = true;
				} else
					$form->addCustomError($loginField, $this->cfg->errorMsg);
				if ($this->logged) {
					if (is_null($page)) {
						if ($this->session->pageFrom) {
							$page = $this->session->pageFrom;
							unset($this->session->pageFrom);
						} else
							$page = request::uri($this->getPage('logged'));
					} else
						$page = request::uri($page);
					response::getInstance()->redirect($page);
				}
		}
		return $this->logged;
	}

	/**
	 * Crypt a string with the function configured
	 *
	 * @param string $str The string to crypt
	 * @param null|string $plus If need to used the second crypt function (or other configured)
	 * @return string The crypted string
	 */
	public function cryptPass($str, $plus='Password') {
		$crypt = $this->cfg->get('crypt'.$plus);
		if ($crypt && function_exists($crypt))
			$str = $crypt($str);
		return $str;
	}

	/**
	 * Logout the current user
	 *
	 * @param mixed $prm
	 * @return bool True if successful
	 */
	public function logout($prm = null) {
		if ($this->isLogged()) {
			$this->session->del('cryptic');
			$this->logged = false;
			// Clear the cookie
			$cook = factory::get('http_cookie', $this->cfg->cookie);
			$cook->del();
		}
		return $this->logged == false;
	}

	/**
	 * Add a role to the current user
	 *
	 * @param string $role Role name
	 * @return bool True if successful
	 */
	public function addRole($role) {
		$this->roles[$role] = true;
		return true;
	}

	/**
	 * Check if the current user has a specific role or retrun the whole roles
	 *
	 * @param null|mixed $role null to get all roles
	 * @return array|bool Array of roles or bool
	 */
	public function hasRole($role=null) {
		if (is_null($role))
			return $this->roles;

		return array_key_exists($role, $this->roles);
	}

	/**
	 * Delete a role or all roles
	 *
	 * @param null|mixed $role null to delete all roles
	 * @return bool True if successful
	 */
	public function delRole($role=null) {
		if (is_null($role)) {
			$this->roles = array();
			return true;
		}
		unset($this->roles[$rol]);
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

		if ($this->isContained($url, $this->cfg->noSecurity))
			return true;

		$hasRight = $this->cfg->default;
		if ($this->isContained($url, $this->cfg->spec)) {
			if ($hasRight) {
				$hasRight = $this->isLogged();
			} else {
				$hasRight = true;
			}
		} else if ($this->isLogged()) {
			if (!empty($this->cfg->rightRoles)) {
				$checks = array();
				foreach($this->hasRole() as $r=>$t) {
					$tmp = $this->cfg->getInArray('rightRoles', $r);
					if (is_array($tmp)) {
						foreach($tmp as $c)
							$checks[] = $c;
					}
				}
				$hasRight = $this->isContained($url, $checks);
			} else
				$hasRight = true;
		}

		if (!$hasRight && $redirect) {
			$request = request::removeLangOutUrl('/'.request::get('request'));
			if ($request != $this->getPage('forbidden') && $request != $this->getPage('login')) {
				$this->session->pageFrom = request::uri(request::get());
				session::setFlash('nyroError', $this->cfg->errorText);
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
		if (!$this->form) {
			$this->form = $this->table->getRow()->getForm(array(
				$this->cfg->getInArray('fields', 'login'),
				$this->cfg->getInArray('fields', 'pass')
			), array(
				'action'=>$this->getPage('login')
			), false);
			$this->form->add('checkbox', array(
				'name'=>'stayConnected',
				'label'=>false,
				'uniqValue'=>true,
				'valid'=>array('required'=>false),
				'list'=>array(
					1=>utils::htmlOut($this->cfg->labelStayConnected)
				)
			));
		}

		return $this->form;
	}

}