<?php
/**
 * @author Cédric Nirousset <cedric@nyrodev.com>
 * @version 0.2
 * @package nyroFwk
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
		$required = array_key_exists('required', $this->cfg->valid) && $this->cfg->getInArray('valid', 'required');
		$prm = array_merge($this->cfg->fileUploadedPrm, array(
			'name'=>$this->cfg->name,
			'current'=>$this->cfg->value,
			'helper'=>$this->cfg->helper,
			'helperPrm'=>$this->cfg->helperPrm,
			'required'=>$required
		));

		if ($this->cfg->dir)
			$prm['dir'] = $this->cfg->dir;
		if ($this->cfg->subdir)
			$prm['subdir'] = $this->cfg->subdir;

		$this->cfg->value = factory::get('form_fileUploaded', $prm);

		if (!$this->cfg->value->isSaved() && http_vars::getInstance()->getVar($this->name.'NyroDel')) {
			$this->cfg->value->delete();
			$this->deleted = true;
		}

		$this->cfg->valid = array_merge($this->cfg->valid, array(
			'callback'=>array($this->cfg->value, 'isValid')
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

	/**
	 * Make the field uploadify.
	 * You will probably have to set the script options at least.
	 *
	 * @param array $opt Uploadify options
	 * @param boolean $hideSubmit Indicate if the submit button should be hide by JavaScript
	 */
	public function uploadify(array $opt = array(), $hideSubmit = true) {
		$resp = response::getInstance();
		$resp->addJs('jquery');
		$resp->addJs('swfobject');
		$resp->addJs('uploadify');
		$resp->addCss('uploadify');

		$uploadifyOpt = array_merge(array(
			'fileDataName'=>$this->name
		), $this->cfg->uploadify, $opt);

		if (request::isLocal())
			$uploadifyOpt['scriptAccess'] = 'always';

		$resp->blockjQuery('$("#'.$this->id.'").uploadify('.utils::jsEncode($uploadifyOpt).');');
		if ($hideSubmit)
			$resp->blockjQuery('$("#'.$this->id.'").closest("form").find("fieldset.submit").hide();');
	}

	public function toHtml() {
		if ($this->cfg->mode == 'view')
			return $this->cfg->value->getView();

		$start = $delLink = null;
		if ($this->cfg->showPreviewDelete) {
			$start = '<p>';
			if ($this->cfg->value->getCurrent()) {
				$delLink = '<span>
					<a href="#" class="deleteFile" id="'.$this->id.'NyroDel">'.$this->cfg->deleteLabel.'</a>'
						.$this->cfg->value->getView().'</span></p>';
				response::getInstance()->blockJquery('
				$("#'.$this->id.'NyroDel").click(function(e) {
					e.preventDefault();
					$(this).parent("span").replaceWith("<input type=\"hidden\" name=\"'.$this->name.'NyroDel\" value=\"1\" />");
				});');
			} else
				$delLink = '</p>';
		}
		return $start.utils::htmlTag($this->htmlTagName,
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
