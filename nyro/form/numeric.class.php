<?php
/**
 * @author Cédric Nirousset <cedric@nyrodev.com>
 * @version 0.2
 * @package nyroFwk
 */
/**
 * Form numeric element
 */
class form_numeric extends form_abstract {

	public function setValue($value, $refill=false) {
		return parent::setValue(str_replace(',', '.', $value));
	}

	public function toHtml() {
		if ($this->cfg->mode == 'view')
			return $this->getValue();
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
				'min'=>$this->min,
				'min'=>$this->max,
				'increment'=>$this->step,
				'value'=>$this->getRawValue(),
			)));
	}

}
