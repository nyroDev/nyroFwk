<?php
/**
 * @author Cédric Nirousset <cedric@nyrodev.com>
 * @version 0.2
 * @package nyroFwk
 */
/**
 * Form list element
 */
class form_list extends form_mulValue {

	protected function afterInit() {
		parent::afterInit();
		$this->cfg->inline = false;
	}

}
