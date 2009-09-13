<?php
/**
 * @author Cedric Nirousset <cedric@nyrodev.com>
 * @version 0.2
 * @package nyro
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
			$this->user = $this->table->find(array_merge(array(
				$this->cfg->getInArray('fields', 'cryptic')=>$cryptic
			), $this->cfg->where));
			if ($this->user) {
				$this->logged = true;
				if (!$fromSession)
					$this->hook('autologin');
				$this->session->cryptic = $cryptic;
			} else if (isset($cook))
				$cook->del();
		}
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
	 */
	public function setUser(db_row $user, $saveLogin=true) {
		$this->user = $user;
		if ($saveLogin)
			$this->saveLogin();
	}

	public function login($prm = null, $page=null) {
		$loginField = $this->cfg->getInArray('fields', 'login');
		$passField = $this->cfg->getInArray('fields', 'pass');

		if (is_null($prm)) {
			$form = $this->getLoginForm();
			if (request::isPost()) {
				$form->refill();
				$prm = $form->getValues(true);
			}
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
					$cryptic = $this->cryptPass(uniqid(), 'Cryptic');
					$this->user->set($crypticKey, $cryptic);
					$this->user->save();
					$this->session->cryptic = $cryptic;
					if (array_key_exists('stayConnected', $prm) && $prm['stayConnected']) {
						$cook = factory::get('http_cookie', $this->cfg->cookie);
						$cook->set($cryptic);
						$cook->save();
					}
					$this->logged = true;
					$this->hook('login');
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

	public function hasRole($role=null) {
		if (is_null($role))
			return $this->roles;

		return array_key_exists($role, $this->roles);
	}

	public function delRole($role=null) {
		if (is_null($role)) {
			$this->roles = array();
			return true;
		}
		unset($this->roles[$rol]);
		return true;
	}

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

	public function getLoginForm() {
		if (!$this->form) {
			$this->form = $this->table->getRow()->getForm(array(
				$this->cfg->getInArray('fields', 'login'),
				$this->cfg->getInArray('fields', 'pass')
			), array(
				'action'=>$this->getPage('login')
			), false);
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

}