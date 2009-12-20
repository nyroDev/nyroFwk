<?php
/**
 * @author Cédric Nirousset <cedric@nyrodev.com>
 * @version 0.2
 * @package nyroFwk
 */
/**
 * Form text element
 */
class form_text extends form_abstract {

	public function toHtml() {
		if ($this->cfg->mode == 'view')
			return $this->cfg->value;
		return utils::htmlTag($this->htmlTagName,
			array_merge($this->html, array(
				'name'=>$this->name,
				'id'=>$this->id,
				'value'=>$this->getRawValue(),
			)));
	}

	public function toXul() {
		if ($this->cfg->mode == 'view')
			return $this->getValue();
		return utils::htmlTag($this->xulTagName,
			array_merge($this->xul, array(
				'id'=>$this->id,
				'value'=>$this->getRawValue(),
			)));
	}

}
