<?php
/**
 * @author Cédric Nirousset <cedric@nyrodev.com>
 * @version 0.2
 * @package nyroFwk
 */
/**
 * Interface for cache classes
 */
abstract class cache_abstract extends object {

	/**
	 * Set the default config
	 */
	public function setCfg(array $cfg) {
		$this->cfg->setA($cfg);
	}

	/**
	 * Check if the cache is enabled
	 *
	 * @return bool
	 */
	public function isEnabled() {
		return $this->cfg->enabled;
	}

	/**
	 * Try to get a variable cached. If not found, information will be stored
	 * and used with the next call from save.
	 * The cache id is made with 5 elements :
	 *  - the $id passed to the function
	 *  - the get, post, session or cookie variable if set
	 *  - the class and function name where the cache is call
	 *  - the tags if set
	 *  - 'get' is added (to differenciate from output caching)
	 *
	 * @param mixed $value the variable where the content must be placed
	 * @param array $prm Parameter for the cached variable:
	 *  - int ttl: Time to live, in minutes, 0 for eternal
	 *  - string id: Cache id (required)
	 *  - array tags: Optionnal tags for the id
	 *  - array request: Array for build the request ID (@see cache::idRequest)
	 *  - bool serialize: True if need to serialize the content
	 * @return bool True if cache found and content in $value
	 * @see save
	 */
	abstract public function get(&$value, array $prm);

	/**
	 * Save the variable with the setting passed by the last call from get.
	 *
	 * @return bool True if success
	 * @see get
	 */
	abstract public function save();

	/**
	 * Try to get an output cached. If not found, information will be stored
	 * and used with the next call from end.
	 * The cache id is made with 5 elements :
	 *  - the $id passed to the function
	 *  - the get, post, session or cookie variable if set
	 *  - the class and function name where the cache is call
	 *  - the tags if set
	 *  - 'cache' is added (to differenciate from variable caching)
	 *
	 * @param array $prm Parameter for the cached variable:
	 *  - int ttl: Time to live, in minutes, 0 for eternal
	 *  - string id: Cache id (required)
	 *  - array tags: Optionnal tags for the id
	 *  - array request: Array for build the request ID (@see cache::idRequest)
	 * @return bool True if cache found and content printed
	 * @see end
	 */
	abstract public function start(array $prm);

	/**
	 * Save the output with the setting passed by the last call from start
	 *
	 * @return string The content cached
	 * @see start
	 */
	abstract public function end();

	/**
	 * Delete cached value. You can define what you want.
	 * If you define nothing, all the cache will be deleted.
	 *
	 * @param array $prm Parameter for the cached variable to deleted:
	 *  - string class: ClassName which created the cache (optionnal)
	 *  - string function: function which created the cache (optionnal)
	 *  - string type: Cache type, could be 'get' or 'start' (optionnal)
	 *  - string id: Cache id (optionnal)
	 *  - array tags: Tags for the id (optionnal)
	 * @return int|bool Number of cache deleted or false
	 * @see get, start
	 */
	abstract public function delete(array $prm = array());

	/**
	 * Indicate if a cache exists
	 *
	 * @param array $prm Parameter for the cached variable:
	 *  - string type: Cache type, must be 'get' or 'start' (required)
	 *  - string id: Cache id (required)
	 *  - array tags: Optionnal tags for the id
	 *  - array request: Array for build the request ID (@see cache::idRequest)
	 *  - bool serialize: True if need to serialize the content (default: true)
	 * @return bool True if it exists
	 * @see get, start
	 */
	abstract public function exists(array $prm);

}
