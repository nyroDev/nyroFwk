<?php
/**
 * @author Cedric Nirousset <cedric@nyrodev.com>
 * @version 0.2
 * @package nyro
 */
/**
 * Cache disabled
 */
class cache_none extends cache_abstract {

	public function get(&$value, array $prm) {
		return false;
	}

	public function save() {
		return true;
	}

	public function start(array $prm) {
		return false;
	}

	public function end() {
		return true;

	}

	public function delete(array $prm = array()) {
		return 1;
	}

	public function exists(array $prm) {
		return false;
	}

}
