<?php
/**
 * @author Cedric Nirousset <cedric@nyrodev.com>
 * @version 0.2
 * @package nyro
 */
/**
 * default module for the db table
 */
class module extends module_abstract {

	/**
	 * Default action
	 *
	 * @param null|string $prm Actopn Parameters
	 * @throws nException if wrong parameter or other errors
	 */
	public function indexAction(array $prm = array()) {

	}

	/**
	 * Publish the module to shown
	 *
	 * @return string Element published
	 */
	public function publish() {
		return 'published';
	}
}
