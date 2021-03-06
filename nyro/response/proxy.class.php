<?php
/**
 * @author Cédric Nirousset <cedric@nyrodev.com>
 * @version 0.2
 * @package nyroFwk
 */
/**
 * To replace the response inside a tpl, to save all call in case of cache
 */
class response_proxy extends nObject {

	/**
	 * Call saved
	 *
	 * @var array
	 */
	protected $call = array();

	/**
	 * Response
	 *
	 * @var response_abstract
	 */
	private $response;
	
	/**
	 * Indicates if the response is also a proxy
	 *
	 * @var boolean
	 */
	protected $isResponseProxy;

	protected function afterInit() {
		$this->response = response::getInstance();
		$this->isResponseProxy = ($this->response instanceof response_proxy);
	}

	/**
	 * Check if call was made
	 *
	 * @return bool
	 */
	public function hasCall() {
		return !empty($this->call);
	}

	/**
	 * Get the call array
	 *
	 * @return array
	 */
	public function getCall() {
		return $this->call;
	}

	/**
	 * Init the call array
	 */
	public function initCall() {
		$this->call = array();
	}

	/**
	 * Do the call given in parameter
	 *
	 * @param array $calls Call to made
	 */
	public function doCalls(array $calls) {
		foreach($calls as $c) {
			call_user_func_array(array($this->response, $c[0]), $c[1]);
		}
	}

	public function getAttr($name) {
		return $this->response->getAttr($name);
	}

	public function setAttr($name, $value) {
		$this->call[] = array('setAttr', array($name, $value));
		$this->response->setAttr($name, $value);
		parent::setAttr($name, $value);
	}

	/**
	 * Save the call if not a get method and make the call
	 *
	 * @param string $name Function name
	 * @param array $prm Parameter
	 * @return mixed The reponse function return
	 */
	public function __call($name, $prm) {
		if (!$this->isResponseProxy && substr($name, 0, 3) != 'get')
			$this->call[] = array($name, $prm);
		return call_user_func_array(array($this->response, $name), $prm);
	}

	public function __toString() {
		return '';
	}

}