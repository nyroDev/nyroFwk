<?php
/**
 * @author Cédric Nirousset <cedric@nyrodev.com>
 * @version 0.2
 * @package nyroFwk
 */
/**
 * Abstract response class
 */
abstract class response_abstract extends nObject {

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
