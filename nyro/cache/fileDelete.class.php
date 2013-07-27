<?php
/**
 * @author Cédric Nirousset <cedric@nyrodev.com>
 * @version 0.2
 * @package nyroFwk
 */
/**
 * Cache disabled, but delete file if existing when requested
 */
class cache_fileDelete extends cache_file {

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

}
