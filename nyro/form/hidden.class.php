<?php
/**
 * @author Cédric Nirousset <cedric@nyrodev.com>
 * @version 0.2
 * @package nyroFwk
 */
/**
 * Form hidden element
 */
class form_hidden extends form_abstract {

	public function isHidden() {
		return true;
	}

	public function toHtml() {
		return utils::htmlTag($this->htmlTagName,
			array_merge($this->html, array(
				'name'=>$this->name,
				'id'=>$this->id,
				'value'=>$this->getRawValue(),
			)));
	}

	public function toXul() {
		return utils::htmlTag($this->xulTagName,
			array_merge($this->xul, array(
				'id'=>$this->id,
				'value'=>$this->getRawValue(),
			)));
	}

}
