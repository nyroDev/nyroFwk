<?php
/**
 * @author Cédric Nirousset <cedric@nyrodev.com>
 * @version 0.2
 * @package nyroFwk
 */
/**
 * Security class to check user rights.
 * By default, allowing everything to the logged users
 */
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
		parent::afterInit();
		$this->session = session::getInstance(array(
			'nameSpace'=>$this->cfg->sessionNameSpace
		));
		$this->table = db::get('table', $this->cfg->table);
		$this->autoLogin();
	}

	/**
	 * Autologin the user with session vars or an eventual cookie
	 */
	protected function autoLogin() {
		$fromSession = true;
		if (!$cryptic = $this->session->cryptic) {
			// Try to check the cookie
			$cook = factory::get('http_cookie', $this->cfg->cookie);
			$cryptic = $cook->get(true);
			$fromSession = false;
		}

		if ($cryptic) {
			$this->user = $this->getUserFromCryptic($cryptic);
			if ($this->user) {
				$this->logged = true;
				$this->hook('autoLogin'.($fromSession ? 'Session' : null));
				$this->session->cryptic = $cryptic;
			} else if (isset($cook))
				$cook->del();
		}
	}
	
	/**
	 * Get a DB user from it's cryptic
	 *
	 * @param string $cryptic Cryptic
	 * @return db_row|null
	 */
	public function getUserFromCryptic($cryptic) {
		 return $this->table->find(array_merge(array(
				$this->table->getRawName().'.'.$this->cfg->getInArray('fields', 'cryptic')=>$cryptic
			), $this->cfg->where));
	}

	public function isLogged() {
		return $this->logged;
	}

	/**
	 * Get the user object
	 *
	 * @return null|db_row
	 */
	public function getUser() {
		if ($this->isLogged() && $this->user)
			return $this->user;
		return null;
	}

	/**
	 * Set the logged user
	 *
	 * @param db_row $user
	 * @param boolean $saveLogin Indicates if the session should be saved
	 * @param boolean $cookieStayConnected Indicats if the stay connected cookie should be set
	 */
	public function setUser(db_row $user, $saveLogin = true, $cookieStayConnected = false) {
		$this->user = $user;
		if ($saveLogin)
			$this->saveLogin($cookieStayConnected);
	}

	/**
	 * Save the login.
	 * Set a new cryptic, save the DB user and save it in session.
	 *
	 * @param boolean $cookieStayConnected Indicats if the stay connected cookie should be set
	 */
	protected function saveLogin($cookieStayConnected = false) {
		$crypticKey = $this->cfg->getInArray('fields', 'cryptic');
		$cryptic = $this->cryptPass(uniqid(), 'Cryptic');
		$this->user->set($crypticKey, $cryptic);
		$this->user->save();
		$this->logFromCryptic($cryptic, $cookieStayConnected);
	}
	
	/**
	 * Return the SQL Clause against login and password
	 *
	 * @param string $login Login
	 * @param string $pass Clear password
	 * @return string
	 */
	protected function getWhereLogin($login, $pass) {
		$tableName = $this->table->getRawName();
		$loginField = $this->cfg->getInArray('fields', 'login');
		$passField = $this->cfg->getInArray('fields', 'pass');
		
		return array(
			$tableName.'.'.$loginField=>$login,
			$tableName.'.'.$passField=>$this->cryptPass($pass),
		);
	}

	/**
	 * Login the current user
	 *
	 * @param mixed $prm
	 * @param null|string $page The page where to be redirected. If null, config will be used
	 * @param boolean $redirectIfLogged Enable the redirect when login is successful
	 * @return bool True if successful
	 */
	public function login($prm = null, $page = null, $redirectIfLogged = true) {
		$loginField = $this->cfg->getInArray('fields', 'login');
		$passField = $this->cfg->getInArray('fields', 'pass');

		$form = $this->getLoginForm();
		if (is_null($prm)) {
			if (request::isPost()) {
				$form->refill();
				$form->isValid();
				$prm = $form->getValues(true);
			}
		}

		if (is_array($prm)
			&& array_key_exists($loginField, $prm)
			&& array_key_exists($passField, $prm)) {
				$this->user = $this->table->find(array_merge(
					$this->cfg->where,
					$this->getWhereLogin($prm[$loginField], $prm[$passField])
				));
				
				if ($this->user) {
					$this->saveLogin(array_key_exists('stayConnected', $prm) && $prm['stayConnected']);
					$this->hook('login');
				} else
					$form->addCustomError($loginField, $this->cfg->errorMsg);
				if ($this->logged && $redirectIfLogged) {
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
	 * Log a session user using his cryptic
	 *
	 * @param string $cryptic
	 * @param boolean $cookieStayConnected Indicats if the stay connected cookie should be set
	 */
	public function logFromCryptic($cryptic, $cookieStayConnected = false) {
		$this->session->cryptic = $cryptic;
		$this->logged = true;
		if ($cookieStayConnected)
			$this->saveCookieStayConnected();
	}
	
	/**
	 * Save the connection on the parametred cookie
	 */
	public function saveCookieStayConnected() {
		$cook = factory::get('http_cookie', $this->cfg->cookie);
		$cook->set($this->user->get($this->cfg->getInArray('fields', 'cryptic')));
		$cook->save();
	}

	/**
	 * Crypt a string with the function configured
	 *
	 * @param string $str The string to crypt
	 * @param null|string $plus If need to used the second crypt function (or other configured)
	 * @return string The crypted string
	 */
	public function cryptPass($str, $plus = 'Password') {
		$crypt = $this->cfg->get('crypt'.$plus);
		if ($crypt && function_exists($crypt))
			$str = $crypt($str);
		return $str;
	}

	public function logout($prm = null) {
		if ($this->isLogged()) {
			$this->session->del('cryptic');
			$this->logged = false;
			// Clear the cookie
			$cook = factory::get('http_cookie', $this->cfg->cookie);
			$cook->del();
		}
		$this->hook('logout');
		return $this->logged == false;
	}

	public function addRole($role) {
		$this->roles[$role] = true;
		return true;
	}

	public function hasRole($role = null) {
		if (is_null($role))
			return $this->roles;

		return array_key_exists($role, $this->roles);
	}

	public function delRole($role = null) {
		if (is_null($role)) {
			$this->roles = array();
			return true;
		}
		unset($this->roles[$rol]);
		return true;
	}

	public function check(array $url = null, $redirect = true) {
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
				$this->session->pageFrom = request::get('localUri');
				session::setFlash('nyroError', $this->cfg->errorText);
				$this->hook('redirectError');
				response::getInstance()->redirect($this->getPage('forbidden', true), 403);
			}
		}

		return $hasRight;
	}

	public function getLoginForm(array $prm = array()) {
		if (!$this->form) {
			$this->form = $this->table->getRow()->getForm(array(
				$this->cfg->getInArray('fields', 'login'),
				$this->cfg->getInArray('fields', 'pass')
			), array_merge($this->cfg->formOptions, $prm, array(
				'action'=>request::uri($this->getPage('login'))
			)), false);
			$this->form->get($this->cfg->getInArray('fields', 'login'))->getValid()->delRule('dbUnique');
			if ($this->cfg->stayConnected) {
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
		}

		return $this->form;
	}

	/**
	 * Return the session object used for security
	 *
	 * @return session_abstract
	 */
	public function getSession() {
		return $this->session;
	}

}