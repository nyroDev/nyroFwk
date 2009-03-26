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

	protected $dates = array();
	protected $set = array('min'=>false, 'max'=>false);
	
	protected function afterInit() {
		parent::afterInit();
		$this->dates = array(
			'min'=>factory::getHelper('date', array(
				'timestamp'=>$this->getValue('min') ? strtotime($this->getValue('min')) : $this->cfg->defaultDate,
				'defaultFormat'=>array(
					'type'=>'date',
					'len'=>'mysql'
				)
			)),
			'max'=>factory::getHelper('date', array(
				'timestamp'=>$this->getValue('max') ? strtotime($this->getValue('max')) : $this->cfg->defaultDate+$this->cfg->defaultRange,
				'defaultFormat'=>array(
					'type'=>'date',
					'len'=>'mysql'
				)
			))
		);
	}
	
	/**
	 * Set the form element value
	 *
	 * @param mixed $value The value
	 */
	public function setValue($value, $refill=false, $key=null) {
		$value = utils::htmlOut($value);
		if (is_array($value)) {
			$min = array_key_exists('min', $value)? $value['min'] : (array_key_exists(0, $value)? $value[0] : null);
			$max = array_key_exists('max', $value)? $value['max'] : (array_key_exists(1, $value)? $value[1] : null);
			$this->setValue($min, $refill, 'min');
			$this->setValue($max, $refill, 'max');
		} else if (!is_null($key)) {
			if ($refill)
				$this->dates[$key]->set($value, 'formatDate', 'short2');
			else
				$this->dates[$key]->set($value);
			$this->cfg->setInArray('rangeValue', $key, $this->dates[$key]->format());
			$this->cfg->setInArray('value', $key, $value);
			$this->set[$key] = true;
		}
	}

	/**
	 * Get the actual value
	 *
	 * @return mixed
	 */
	public function getValue($key=null, $mode='raw') {
		if (!is_null($key)) {
			if ($mode == 'input') {
				$ret = $this->set[$key]? $this->dates[$key]->format('date', 'short2') : null;
			} else
				$ret = $this->cfg->getInArray('rangeValue', $key);
		} else
			$ret = $this->rangeValue;
		return utils::htmlDeOut($ret);
	}
	
	public function toHtml() {
		if ($this->cfg->useJs) {
			$this->cfg->setInArray('html', 'class', $this->cfg->getInArray('html', 'class').' date');
			$resp = response::getInstance();
			$resp->addJs('jqueryui');
			if (($lang = request::get('lang')) != 'en')
				$resp->addJs('i18n_ui.datepicker-'.$lang);
			$dateImg = utils::getIcon(array(
				'name'=>'show_month',
				'type'=>'calendar',
				'imgTag'=>false
			));
			
			$minId = $this->makeId($this->name.'[0]');
			$maxId = $this->makeId($this->name.'[1]');
			$resp->blockJquery('$("#'.$minId.'").datepicker({
				buttonImage: "'.$dateImg.'", buttonImageOnly: true, showOn: "both",
				maxDate: '.$this->dates['max']->getJs().',
				onSelect: function(dateText) {$("#'.$maxId.'").datepicker("option", "minDate", $("#'.$minId.'").datepicker("getDate"));}
			});');
			$resp->blockJquery('$("#'.$maxId.'").datepicker({
				buttonImage: "'.$dateImg.'", buttonImageOnly: true, showOn: "both",
				minDate: '.$this->dates['min']->getJs().',
				onSelect: function(dateText) {$("#'.$minId.'").datepicker("option", "maxDate", $("#'.$maxId.'").datepicker("getDate"));}
			});');
		}

		return parent::toHtml();
	}
	
	public function toXul() {
		return '<textbox type="number" id="'.$this->name.'" value="'.$this->value.'" min="'.$this->min.'" max="'.$this->max.'" increment="'.$this->step.'" '.utils::htmlAttribute($this->more).'/>';
	}
}
