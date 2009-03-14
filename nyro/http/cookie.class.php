<?php
/**
 * @author Cedric Nirousset <cedric@nyrodev.com>
 * @version 0.2
 * @package nyro
 */
/**
 * To manipulate cookie
 */
class http_cookie extends object {

	/**
	 * Indicate if the cookie is saved or not
	 *
	 * @var bool
	 */
	protected $saved = false;

	/**
	 * Indicate if the cookie will not be saved
	 *
	 * @var bool
	 */
	protected $doNotSave = false;

	protected function afterInit() {
		if (empty($this->cfg->value)) {
			$this->cfg->value = $this->get(true);
			$this->saved = true;
		}

		if ($this->cfg->autoSave)
			response::getInstance()->addBeforeOut(array($this, 'save'));
	}

	/**
	 * Alter the configuration with an array
	 *
	 * @param array $prm
	 */
	public function changeCfg(array $prm) {
		$this->cfg->setA($prm);
		$this->saved = false;
	}

	/**
	 * get or set the doNotSave attribute
	 *
	 * @param nul|bool $doNotSave null to get or booleand to set
	 * @return void|bool
	 */
	public function doNotSave($doNotSave=null) {
		if (is_null($doNotSave))
			return $this->doNotSave;
		else
			$this->doNotSave = (boolean) $doNotSave;
	}

	/**
	 * Check if the cookie is already set
	 *
	 * @return bool
	 */
	public function check() {
		return array_key_exists($this->getRawName(), $_COOKIE);
	}

	/**
	 * Get the current cookie value
	 *
	 * @param bool $fromBrowser True if force to get the value from the browser
	 * @return mixed|null The value or null if not set
	 */
	public function get($fromBrowser=false) {
		if (!$this->saved && !$fromBrowser)
			return $this->cfg->value;
		else if ($this->check())
			return $_COOKIE[$this->getRawName()];

		return null;
	}

	/**
	 * Set the cookie value
	 *
	 * @param mixed $value The cookie value
	 */
	public function set($value) {
		if ($value != $this->cfg->value)
			$this->saved = false;

		$this->cfg->value = $value;
	}

	/**
	 * Delete the cookie
	 */
	public function del() {
		$this->set(null);
		$this->cfg->expire = -1;
	}

	/**
	 * Save the cookie. Should be call by the response only
	 *
	 * @return bool True if already saved or successful saved, False if not saved
	 */
	public function save() {
		if ($this->saved)
			return true;

		if ($this->doNotSave)
			return false;

		$this->saved = setcookie(
			$this->getRawName(),
			$this->cfg->value,
			$this->cfg->expire+time(),
			$this->cfg->path,
			$this->cfg->domain,
			$this->cfg->secure);

		return $this->saved;
	}

	/**
	 * Get the raw cookie name (with the prefix)
	 *
	 * @return string
	 */
	public function getRawName() {
		return $this->cfg->prefix.$this->cfg->name;
	}
}
