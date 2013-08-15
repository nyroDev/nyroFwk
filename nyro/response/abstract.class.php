<?php
/**
 * @author Cédric Nirousset <cedric@nyrodev.com>
 * @version 0.2
 * @package nyroFwk
 */
/**
 * Abstract response class
 */
abstract class response_abstract extends object {

	/**
	 * The response content
	 *
	 * @var mixed
	 */
	protected $content;

	/**
	 * The response proxy
	 *
	 * @var response_proxy
	 */
	protected $proxy = null;

	/**
	 * Get the response proxy (used in the templates)
	 *
	 * @return response_proxy
	 */
	public function getProxy() {
		return factory::get('response_proxy');
	}

	/**
	 * Get the response content
	 *
	 * @return mixed
	 */
	public function getContent() {
		return $this->content;
	}

	/**
	 * set the response content
	 *
	 * @param mixed $content
	 */
	public function setContent($content) {
		$this->content = $content;
	}

	/**
	 * Return the content commented regarding the response type
	 *
	 * @param string $comment
	 * @return string
	 */
	public function comment($comment) {
		return '';
	}
	
	/**
	 * Indicates if the global Cache is possible for the response
	 *
	 * @return boolean True if enabled
	 */
	public function canGlobalCache() {
		return true;
	}
	
	/**
	 * Get vars needed for globalCache this response
	 *
	 * @return mixed
	 */
	public function getVarsForGlobalCache() {
		return null;
	}
	
	/**
	 * Set a variable to the config
	 *
	 * @param string $name Variable name
	 * @param mixed $val Value
	 */
	public function cfgSet($name, $val) {
		$this->cfg->set($name, $val);
	}
	
	/**
	 * Set an array of variable to the config
	 *
	 * @param array $vars Variables to set
	 */
	public function cfgSetA(array $vars) {
		$this->cfg->setA($vars);
	}
	
	/**
	 * Set a variable to the config in an array
	 *
	 * @param string $name Variable name
	 * @param string $key Key on the array
	 * @param mixed $val Value
	 */
	public function cfgSetInArray($name, $key, $val) {
		$this->cfg->setInArray($name, $key, $val);
	}
	
	
	/**
	 * Set variables to the config in an array
	 *
	 * @param string $name Variable name
	 * @param array $values the Values
	 */
	public function cfgSetInArrayA($name, array $values) {
		$this->cfg->setInArrayA($name, $values);
	}
	
	/**
	 * Set vars saved from a globalCache response.
	 * This function should also apply the vars
	 *
	 * @param mixed $vars
	 */
	public function setVarsFromGlobalCache($vars) {}

	/**
	 * Send The response
	 */
	abstract public function send();

	/**
	 * Send a text response (exit the programm)
	 *
	 * @param string $text
	 */
	abstract public function sendText($text);

	/**
	 * Here to avoid wrong call to the response object
	 */
	public function __call($func, $prm) {}

	public function __toString() {
		return '';
	}

}
