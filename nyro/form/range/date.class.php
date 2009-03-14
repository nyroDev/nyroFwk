<?php
/**
 * @author Cedric Nirousset <cedric@nyrodev.com>
 * @version 0.2
 * @package nyro
 */
/**
 * Form numeric element
 */
class form_range_date extends form_range_abstract {

	public function toHtml() {
		return '<input type="text" name="'.$this->name.'[0]" value="'.$this->getValue('min').'" '.utils::htmlAttribute($this->html).' />'.
			   '<input type="text" name="'.$this->name.'[1]" value="'.$this->getValue('max').'" '.utils::htmlAttribute($this->html).' />';
	}

	public function toXul() {
		return '<textbox type="number" id="'.$this->name.'" value="'.$this->value.'" min="'.$this->min.'" max="'.$this->max.'" increment="'.$this->step.'" '.utils::htmlAttribute($this->more).'/>';
	}
}
