<?php
/**
 * @author Cedric Nirousset <cedric@nyrodev.com>
 * @version 0.2
 * @package nyro
 */
/**
 * Form numeric element
 */
class form_range_numeric extends form_range_abstract {

	public function toHtml() {
		$ret = parent::toHtml();
		
		if ($this->cfg->useJs) {
			$id = $this->makeid($this->name.'-slider');
			
			$tmp = str_replace('"'.$this->name, '"'.$this->name.'JS', $ret);
			$ret = str_replace('<div class="range">', '<div class="range" style="display: none;">', $ret);
			$ret = '<div id="'.$id.'" class="range-slider"></div>'.$ret.$tmp;
			
			
			$min = $this->cfg->getInarray('allowedRange', 'min');
			$max = $this->cfg->getInarray('allowedRange', 'max');
			
			if (!$min) $min = 0;
			if (!$max) $max = 100;
			
			$minVal = $this->getValue('min');
			$maxVal = $this->getValue('max');
			if (!$minVal) $minVal = $min;
			if (!$maxVal) $maxVal = $max;
			
			$resp = response::getInstance();
			$resp->addJs('jqueryui');
			$resp->blockJquery('$("#'.$id.'").slider({
				range: true,
				min: '.$min.',
				max: '.$max.',
				values: ['.$minVal.','.$maxVal.'],
				slide: function(event, ui) {
					$("#'.$this->makeId($this->name.'[0]').'").val(ui.values[0]);
					$("#'.$this->makeId($this->name.'[1]').'").val(ui.values[1]);
					$("#'.$this->makeId($this->name.'JS[0]').'").val(ui.values[0]);
					$("#'.$this->makeId($this->name.'JS[1]').'").val(ui.values[1]);
				}
			})
			.next(".range").next(".range").find("input").attr("disabled", "disabled");
			;');
		}
		
		return $ret;
	}

	public function toXul() {
		return '<textbox type="number" id="'.$this->name.'" value="'.$this->value.'" min="'.$this->min.'" max="'.$this->max.'" increment="'.$this->step.'" '.utils::htmlAttribute($this->more).'/>';
	}
}
