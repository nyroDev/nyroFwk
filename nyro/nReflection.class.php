<?php
/**
 * @author Cédric Nirousset <cedric@nyrodev.com>
 * @version 0.2
 * @package nyroFwk
 */
/**
 * used to create new classes dynamicly.
 */
class nReflection extends ReflectionClass {

	/**
	 * Overload the ReflectionClass to accept no parameter
	 *
	 * @param object|string $elt
	 */
	public function __construct($elt='nObject') {
		if (is_object($elt))
			$this->rebuild(get_class($elt));
		else if ($elt)
			$this->rebuild($elt);
	}

	/**
	 * Change the present class to be able to use reflection on it.
	 *
	 * @param string $name
	 * @return bool True if success
	 */
	public function rebuild($name) {
		try {
			parent::__construct($name);
			return true;
		} catch(Exception $e) {
			return false;
		}
	}

	/**
	 * Create a new object with one argument, a config passed in parameter.
	 *
	 * @param config $cfg The config parameter
	 * @return stdclass The new instance
	 */
	public function newInstanceCfg(config $cfg) {
		return parent::newInstance($cfg);
	}

	/**
	 * Test if the className is a parent fo the actual class
	 *
	 * @param string $className The className to test
	 * @return bool
	 */
	public function isSubclassOf($className) {
		return parent::isSubclassOf(new nReflection($className));
	}

	/**
	 * Retrieve the public properties
	 *
	 * return array
	 */
	public function getPublicProperties() {
		$publicProps = array();

		$props = $this->getProperties();
		foreach($props as $p) {
			if ($p->isPublic())
				$publicProps[] = $p->getName();
		}

		return $publicProps;
	}

	/**
	 * Return all the parents.
	 * The first element is the oldest
	 * This function doesn't change the nReflection state
	 *
	 * @param string $className
	 * @return array
	 */
	public static function getParentsClass($className) {
		return class_parents($className);
	}

	/**
	 * Call a method from an object. Used when the function name is dynamic
	 *
	 * @param object $object The object where call the function
	 * @param string $function Function name
	 * @param array $args Argumens for the call
	 * @return mixed Function result
	 * @throws nExecption If method is not callable
	 */
	public static function callMethod($object, $function, $args=array()) {
		$ref = new nReflection($object);
		if ($ref->hasMethod($function)
			&& ($meth = $ref->getMethod($function))
			&& $meth->isPublic()) {
				if (!is_array($args)) {
					if (!empty($args))
						$args = array($args);
					else
						$args = array();
				}
				return $meth->invokeArgs($object, $args);
			}
		throw new nException('Method not callable');
	}

}
