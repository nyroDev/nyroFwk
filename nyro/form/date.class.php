<?php
/**
 * @author Cédric Nirousset <cedric@nyrodev.com>
 * @version 0.2
 * @package nyroFwk
 */
/**
 * Form date element
 */
class form_date extends form_abstract {

	/**
	 * Date Helper
	 *
	 * @var helper_date
	 */
	protected $date;

	protected function afterInit() {
		parent::afterInit();
		$this->date = factory::getHelper('date', array(
			'timestamp'=>$this->cfg->value ? strtotime($this->cfg->value) : time(),
			'defaultFormat'=>array(
				'type'=>'date',
				'len'=>'mysql'
			)
		));
	}

	public function getValue() {
		return $this->date->format();
	}

	public function setValue($value, $refill=false) {
		parent::setValue($value);
		if ($refill)
			$this->date->set($value, 'formatDate', 'short2');
		else
			$this->date->set($value);
	}

	public function toHtml() {
		if ($this->cfg->mode == 'view')
			return $this->date->format('date', 'short2');

		$resp = response::getInstance();
		$resp->addJs('jqueryui');
		if (($lang = request::get('lang')) != 'en')
			$resp->addJs('i18n_ui.datepicker-'.$lang);

		$resp->blockJquery('$("#'.$this->id.'").datepicker('.json_encode($this->jsPrm).');');

		return utils::htmlTag($this->htmlTagName,
			array_merge($this->html, array(
				'name'=>$this->name,
				'id'=>$this->id,
				'value'=>$this->date->format('date', 'short2'),
			)));
	}

	public function toXul() {
		return utils::htmlTag($this->xulTagName,
			array_merge($this->xul, array(
				'id'=>$this->id,
				'value'=>$this->date->format('date', 'short2'),
			)));
	}

}
