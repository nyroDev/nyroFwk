<?php
/**
 * @author Cédric Nirousset <cedric@nyrodev.com>
 * @version 0.2
 * @package nyroFwk
 */
/**
 * Template
 */
class tpl extends nObject {

	/**
	 * Variables for the template
	 *
	 * @var array
	 */
	protected $vars = array();

	/**
	 * Response proxy used in the templates
	 *
	 * @var response_proxy
	 */
	protected $responseProxy;

	protected function afterInit() {
		$this->responseProxy = response::getInstance()->getProxy();
	}

	/**
	 * Get the response proxy used in this tpl
	 *
	 * @return response_proxy
	 */
	public function getResponseProxy() {
		return $this->responseProxy;
	}

	/**
	 * Get a variable value for the fetch
	 *
	 * @param string $name Variable name
	 * @return mixed|null The value or null if not existing
	 */
	public function get($name) {
		return isset($this->vars[$name]) ? $this->vars[$name] : null;
	}

	/**
	 * Set a variable for the fecth
	 *
	 * @param string $name Variable name
	 * @param mixed $value The value
	 */
	public function set($name, $value) {
		$this->vars[$name] = $value;
	}

	/**
	 * Set multiple variables using an array
	 *
	 * @param array $values The values
	 * @see set
	 */
	public function setA(array $values) {
		$this->vars = array_merge($this->vars, $values);
	}

	/**
	 * Clear all the variables
	 */
	public function reset() {
		$this->vars = array();
	}

	/**
	 * Fetch the template
	 *
	 * @param array $prm Array parameter for retrieve the tpl file (exemple: used to force the tpl extension via tplExt)
	 * return string The result fetched
	 * @see file::nyroExists
	 */
	public function fetch(array $prm=array()) {
		$content = null;
		$cachedContent = false;
		$cachedLayout = false;

		$oldProxy = response::getProxy();
		response::setProxy($this->responseProxy);

		$cacheResp = null;

		if ($this->cfg->cache['auto']) {
			$cache = cache::getInstance(array_merge(array('serialize'=>false), $this->cfg->cache));
			$cache->get($content, array(
				'id'=>$this->cfg->module.'-'.$this->cfg->action.'-'.str_replace(':', '..', $this->cfg->param)
			));
			$cacheResp = cache::getInstance($this->cfg->cache);
			$cacheResp->get($callResp, array(
				'id'=>$this->cfg->module.'-'.$this->cfg->action.'-'.str_replace(':', '..', $this->cfg->param).'-callResp'
			));
			if (!empty($content)) {
				$cachedContent = true;
				$cachedLayout = $this->cfg->cache['layout'];
				if (!empty($callResp)) {
					$this->responseProxy->doCalls($callResp);
					$this->responseProxy->initCall();
				}
			}
		}

		if (!$cachedContent) {
			// Nothing was cached
			$action = $this->cfg->action;
			if (array_key_exists('callback', $prm))
				$action = call_user_func($prm['callback'], $prm['callbackPrm']);
			$file = $this->findTpl($prm, array(
				'module_'.$this->cfg->module.'_view_'.$action,
				'module_'.$this->cfg->defaultModule.'_view_'.$this->cfg->default
			));

			if (file::exists($file))
				$content = $this->_fetch($file);
		}

		if ($this->cfg->layout && !$cachedLayout) {
			// Action layout
			$file = $this->findTpl($prm, array(
				'module_'.$this->cfg->module.'_view_'.$this->cfg->action.'Layout',
				'module_'.$this->cfg->module.'_view_layout'
			));
			if (file::exists($file)) {
				$this->content = $content;
				$content = $this->_fetch($file);
			}
			if ($this->cfg->cache['auto'] && $this->cfg->cache['layout'])
				$cache->save();
		}

		if ($cacheResp && $this->responseProxy->hasCall()) {
			$callResp = $this->responseProxy->getCall();
			$cacheResp->save();
		}

		response::setProxy($oldProxy);
		return $content;
	}

	/**
	 * Make really the fetch
	 *
	 * @param string $file Template file path
	 * @return string Content fetched
	 */
	protected function _fetch($file) {
		$this->set('response', $this->responseProxy);
		extract($this->vars, EXTR_REFS OR EXTR_OVERWRITE);
		ob_start();
		include($file);
		$contents = ob_get_contents();
		ob_end_clean();
		return $contents;
	}

	/**
	 * Find the template file
	 *
	 * @param array $prm Parameter used in file::nyroExists
	 * @param array $name Template name
	 * @return string|null The first template file path found or null
	 */
	protected function findTpl(array $prm, array $name) {
		foreach($name as $n) {
			if ($file = file::nyroExists(array_merge($prm, array('name'=>$n,'type'=>'tpl'))))
				return $file;
		}
		return null;
	}

	/**
	 * Render an other tpl. To be used inside the tpl
	 *
	 * @param array $prm Possibile keys:
	 *  - string module Module to used (default: module used for the current tpl)
	 *  - string action Action to call
	 *  - other parameters must be used for the call to the module_abstract::exec method or module_abstract::publish
	 */
	public function render($prm) {
		if (!is_array($prm)){
			$tmp = explode('/', $prm);
			$prm = array();
			$prm['module'] = isset($tmp[0]) ? $tmp[0] : null;
			$prm['action'] = isset($tmp[1]) ? $tmp[1] : null;
			$prm['param'] = isset($tmp[2]) ? $tmp[2] : null;
		}
		$prm = array_merge(array('module'=>$this->cfg->module), $prm);
		$module = factory::getModule($prm['module'], array('render'=>true));
		$module->exec($prm);
		return $module->publish($prm);
	}

	/**
	 * Fetch the tpl
	 *
	 * @return string
	 * @see fetch
	 */
	function __toString() {
		return $this->fetch();
	}

	/**
	 * Get a variable, with a convenient way $tpl->name
	 *
	 * @param string $name Name to set
	 * @return mixed|null The value or null if not existing
	 * @see get
	 */
	public function __get($name) {
		return $this->get($name);
	}
	
	/**
	 * Set a variable, with a convenient way $tpl->name = $value
	 *
	 * @param string $name Name to set
	 * @param mixed $value Value to set
	 * @see set
	 */
	public function __set($name, $val) {
		$this->set($name, $val);
	}

}
