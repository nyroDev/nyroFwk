<?php
/**
 * @author Cedric Nirousset <cedric@nyrodev.com>
 * @version 0.2
 * @package nyro
 */
/**
 * Abstract class for module classes
 */
abstract class module_abstract extends object {

	/**
	 * Save the last execute parameter call
	 *
	 * @var array
	 */
	protected $prmExec;

	/**
	 * Template object for the current action
	 *
	 * @var tpl
	 */
	protected $tpl;

	protected function afterInit() {
		if ($this->cfg->forceSecure)
			request::forceSecure();

		if (!$this->cfg->enabled)
			throw new module_exception('Module '.$this->getName().' disabled.');
	}

	/**
	 * Every called action must pass by this function
	 * @todo check rights with ACL
	 *
	 * @param null|string $prm Actopn Parameters
	 * @throws nException if wrong parameter or other errors
	 */
	final public function exec(array $prm=array()) {
		$this->prmExec = array_merge(array(
			'module'=>$this->getName(),
			'action'=>'index',
			'param'=>'',
			'paramA'=>null,
			'prefix'=>null),
			$prm);

		$this->prmExec['prefix'] = null;

		if (array_key_exists(NYROENV, $this->cfg->basicPrefixExec) &&
				in_array($this->prmExec['action'], $this->cfg->getInArray('basicPrefixExec', NYROENV)))
			$this->prmExec['prefix'] = ucfirst(NYROENV);
		else if ($this->cfg->prefixExec && !in_array($this->prmExec['action'], $this->cfg->noPrefixExec))
			$this->prmExec['prefix'] = $this->cfg->prefixExec;

		$this->beforeExec($prm);

		if (!$this->cfg->render)
			security::getInstance()->check($this->prmExec);

		$fctName = ($this->cfg->render? 'render' : 'exec').$this->prmExec['prefix'].ucfirst($this->prmExec['action']);
		if (!method_exists($this, $fctName))
			response::getInstance()->error();

		$this->setViewAction($this->prmExec['action']);

		$param = is_array($this->prmExec['paramA'])? $this->prmExec['paramA'] : request::parseParam($this->prmExec['param']);
		
		$tags = $this->cfg->cacheTags;
		$search = array('/', '<', '>');
		$replace = array('', '', '');
		if (is_array($this->prmExec['paramA']))
			foreach($this->prmExec['paramA'] as $k=>$v) {
				if (!is_numeric($k))
					$tags[] = $k.'='.str_replace($search, $replace, $v);
				else
					$tags[] = str_replace($search, $replace, $v);
			}

		$paramTpl = array();
		foreach($param as $k=>$v) {
			$paramTpl[] = $k.':'.$v;
		}

		$this->tpl = factory::get('tpl', array_merge(array(
			'layout'=>$this->cfg->layout,
			'module'=>$this->getName(),
			'action'=>$this->cfg->viewAction,
			'param'=>implode(',', $paramTpl),
			'cache'=>array_merge(array(
				'enabled'=>$this->isCacheEnabled(),
				'serialize'=>false,
				'tags'=>$tags,
				'request'=>array('uri'=>false, 'meth'=>array())
			), $this->cfg->cache)
		), $this->cfg->tplPrm));

		$this->prmExec['callbackPrm'] = array(
			'fctName'=>$fctName,
			'fctNameParam'=>$param,
			'prm'=>$prm
		);
	}

	public function callbackTpl(array $prm) {
		$this->$prm['fctName']($prm['fctNameParam']);
		$this->afterExec($prm['prm']);
		return $this->getViewAction();
	}

	public function getReponse() {
		return $this->tpl->getResponseProxy();
	}

	/**
	 *
	 */
	protected function beforeExec($realExec) {}

	/**
	 *
	 */
	protected function afterExec($realExec) {}

	/**
	 * Get the current view action
	 *
	 * @return string
	 */
	protected function getViewAction() {
		return $this->cfg->viewAction;
	}
	
	/**
	 * Set the action which will use for the view
	 *
	 * @param string $action
	 */
	protected function setViewAction($action) {
		$this->cfg->viewAction = $action;
	}

	/**
	 * Add a variable to the view
	 *
	 * @param string $name Variable name
	 * @param mixed $value
	 */
	protected function setViewVar($name, $value) {
		//$this->cfg->setInArray('viewVars', $name, $value);
		$this->tpl->set($name, $value);
	}

	/**
	 * Add variables to the view with an array
	 *
	 * @param array $values
	 */
	protected function setViewVars(array $values) {
		//$this->cfg->setInArrayA('viewVars', $values);
		$this->tpl->setA($values);
	}

	/**
	 * Publish the module to shown
	 *
	 * @return string The fetched view
	 */
	public function publish(array $prm = array()) {
		if (!$this->cfg->viewAction)
			return null;
		
		return $this->tpl->fetch(array_merge(array(
			'callback'=>array($this, 'callbackTpl'),
			'callbackPrm'=>$this->prmExec['callbackPrm'],
		), $prm));
	}

	/**
	 * Check against the configuration if the current request should be cached
	 *
	 * @return bool True if enabled
	 */
	protected function isCacheEnabled() {
		foreach($this->cfg->noCache as $val) {
			$prm = array_intersect_key($this->prmExec, $val);
			$match = 0;
			foreach($val as $k=>$v) {
				if ($prm[$k] == $v)
					$match++;
			}
			if ($match == count($prm))
				return false;
		}
		return true;
	}

	/**
	 * Add a cache tag
	 *
	 * @param mixed $val The tag to add
	 */
	protected function addCacheTag($val) {
		$this->cfg->setInArrayA('cacheTags', array($val));
	}

	/**
	 * Get the module name
	 *
	 * @return string
	 */
	public function getName() {
		return utils::getModuleName(get_class($this));
	}
}
