<?php
/**
 * @author Cedric Nirousset <cedric@nyrodev.com>
 * @version 0.2
 * @package nyro
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