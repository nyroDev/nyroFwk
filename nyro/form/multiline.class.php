<?php
/**
 * @author Cedric Nirousset <cedric@nyrodev.com>
 * @version 0.2
 * @package nyro
 */
/**
 * Form multiline element
 */
class form_multiline extends form_text {

	public function toHtml() {
		if ($this->cfg->mode == 'view')
			return '<p>'.nl2br($this->cfg->value).'</p>';
		return utils::htmlTag($this->htmlTagName,
			array_merge($this->html, array(
				'name'=>$this->name,
				'id'=>$this->id,
			)), $this->cfg->value);
	}

	public function toXul() {
		return utils::htmlTag($this->xulTagName,
			array_merge($this->xul, array(
				'id'=>$this->id,
				'value'=>$this->getRawValue(),
			)));
	}
}
