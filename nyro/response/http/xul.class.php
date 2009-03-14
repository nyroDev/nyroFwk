<?php
/**
 * @author Cedric Nirousset <cedric@nyrodev.com>
 * @version 0.2
 * @package nyro
 */
/**
 * HTML Response
 * Provide functions to manage the head parts
 */
class response_http_xul extends response_http_html {

	protected function afterInit() {
		parent::afterInit();
		$this->setContentType('xul');
	}
}