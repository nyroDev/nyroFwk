<?php
/**
 * @author Cedric Nirousset <cedric@nyrodev.com>
 * @version 0.2
 * @package nyro
 */
/**
 * Form file element
 */
class form_file extends form_abstract {

	/**
	 * Indicate if the file was deleted
	 *
	 * @var bool
	 */
	protected $deleted = false;
	
	protected function beforeInit() {
		$prm = array(
			'name'=>$this->cfg->name,
			'current'=>$this->cfg->value,
			'helper'=>$this->cfg->helper,
			'helperPrm'=>$this->cfg->helperPrm
		);

		if ($this->cfg->subdir)
			$prm['subdir'] = $this->cfg->subdir;

		$this->cfg->value = factory::get('form_fileUploaded', $prm);
		
		if (!$this->cfg->value->isSaved() && http_vars::getInstance()->getVar($this->name.'NyroDel')) {
			$this->cfg->value->delete();
			$this->deleted = true;
		}
	
		$this->cfg->valid = array_merge($this->cfg->valid, array(
			'callback'=>array(
				array($this->cfg->value, 'isValid')
			)
		));
	}
	
	protected function afterInit() {
		parent::afterInit();
		if (!$this->isValid())
			$this->cfg->classLine.= ' fileError';
	}

	public function getValue() {
		return $this->cfg->value->getCurrent();
	}

	public function setValue($value, $refill=false) {
		if (!$this->deleted && !$refill)
			$this->cfg->value->setCurrent($value, $refill);
	}

	public function toHtml() {
		if ($this->cfg->mode == 'view')
			return $this->cfg->value->getView();

		$delLink = null;
		if ($this->cfg->value->getCurrent()) {
			$delLink = '<span><br /><br />
				<a href="#" class="deleteFile" id="'.$this->id.'NyroDel">'.$this->cfg->deleteLabel.'</a><br />'
					.$this->cfg->value->getView().'</span></p>';
			response::getInstance()->blockJquery('
			$("#'.$this->id.'NyroDel").click(function(e) {
				var me = $(this);
				e.preventDefault();
				me.parent("span").replaceWith("<input type=\"hidden\" name=\"'.$this->name.'NyroDel\" value=\"1\" />");
			});');
		} else
			$delLink = '</p>';
		return '<p>'.utils::htmlTag($this->htmlTagName,
			array_merge($this->html, array(
				'name'=>$this->name,
				'id'=>$this->id,
			))).$delLink;
	}

	public function toXul() {
		return utils::htmlTag($this->xulTagName,
			array_merge($this->xul, array(
				'id'=>$this->id,
			)));
	}
}
