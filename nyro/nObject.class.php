<?php
/**
 * @author Cédric Nirousset <cedric@nyrodev.com>
 * @version 0.2
 * @package nyroFwk
 */
/**
 * Every class extends of this class
 */
abstract class nObject {

	/**
	 * Class configuration
	 *
	 * @var config
	 */
	protected $cfg;

	/**
	 * Will call beforeInit, initilize the configuration with initCfg and call afterInit.
	 * You can overload these 3 functions on your subclasses.
	 *
	 * @param config $cfg The initial configuration
	 */
	final public function __construct(config $cfg) {
		$this->cfg = $cfg;
		$this->beforeInit();
		$this->cfg->checkCfg();
		$this->afterInit();
	}

	/**
	 * Call just before the configuration initialisation
	 */
	protected function beforeInit() {}

	/**
	 * Call just after the configuration initialisation
	 */
	protected function afterInit() {}

	/**
	 * Get an attribute
	 *
	 * @param string $name Attribute name
	 * @return mixed|null The attribute or null if not set
	 */
	public function getAttr($name) {
		return $this->cfg->getInArray('attributes', $name);
	}

	/**
	 * Set an attribute
	 *
	 * @param string $name Attribute name
	 * @param mixed $value Attribute value
	 */
	public function setAttr($name, $value) {
		$this->cfg->setInArray('attributes', $name, $value);
	}

	/**
	 * Get the configuration object
	 *
	 * @return config
	 */
	public function getCfg() {
		return $this->cfg;
	}

}
