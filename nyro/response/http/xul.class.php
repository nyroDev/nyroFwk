<?php
/**
 * @author Cédric Nirousset <cedric@nyrodev.com>
 * @version 0.2
 * @package nyroFwk
 */
/**
 * XUL Response
 */
class response_http_xul extends response_http_html {

	protected function afterInit() {
		parent::afterInit();
		$this->setContentType('xul');
	}

}