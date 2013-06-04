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
	
	/**
	 * Value keeped from a previous upload
	 *
	 * @var string
	 */
	protected $keep;
	
	/**
	 * Name template used for field information
	 *
	 * @var string
	 */
	protected $nameAct;

	/**
	 * Get a field name to be used for field information
	 *
	 * @param string $name information name
	 * @return string
	 */
	protected function getNameAct($name) {
		if (is_null($this->nameAct))
			$this->nameAct = strpos($this->name, ']') !== false ? $this->name.'[[NAME]]' : $this->name.'[NAME]';
		return str_replace('[NAME]', $name, $this->nameAct);
	}
	
	protected function beforeInit() {
		$required = array_key_exists('required', $this->cfg->valid) && $this->cfg->getInArray('valid', 'required');
		
		$htVars = http_vars::getInstance();
		
		$this->keep = $htVars->getVar($this->getNameAct('NyroKeep'));
		if ($this->keep)
			$this->cfg->value = $this->keep;
		
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

		if ($this->cfg->autoDeleteOnGet && !$this->cfg->value->isSaved(true) && $htVars->getVar($this->getNameAct('NyroDel'))) {
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

	public function setValue($value, $refill = false) {
		if (!$this->deleted && !$refill) {
			if ($value || !$this->keep)
				$this->cfg->value->setCurrent($value, $refill);
		}
	}
	
	public function hasFile() {
		return true;
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
	

	/**
	 * Make the field plupload for asynchronous upload.
	 *
	 * @param array $opts Plupload options
	 * @param boolean $hideSubmit Indicate if the submit button should be hide by JavaScript
	 */
	public function plupload(array $opts = array(), $hideSubmit = true) {
		$resp = response::getInstance();
		$resp->addJs('jquery');
		$resp->addJs('plupload');
		$resp->addJs('nyroPlupload');
		$resp->addCss('plupload');

		$pluploadOpts = $this->cfg->plupload;
		if (count($opts))
			factory::mergeCfg($pluploadOpts, $opts);
		$pluploadOpts['file_data_name'] = $this->name;

		$resp->blockjQuery('$("#'.$this->id.'").nyroPlupload('.utils::jsEncode($pluploadOpts).');');
		if ($hideSubmit)
			$resp->blockjQuery('$("#'.$this->id.'").closest("form").find("fieldset.submit").hide();');
	}

	public function toHtml() {
		if ($this->cfg->mode == 'view')
			return $this->cfg->value->getView();

		$start = $end = null;
		if ($this->cfg->showPreview || $this->cfg->showDelete) {
			$start = '<'.$this->cfg->htmlWrap.'>';
			if ($this->cfg->value->getCurrent()) {
				$end.= '<input type="hidden" name="'.$this->getNameAct('NyroKeep').'" value="'.$this->cfg->value->getCurrent().'" />';
				$end.= '<span class="filePreview">';
				if ($this->cfg->showDelete) {
					$end.= '<a href="#" class="deleteFile" id="'.$this->id.'NyroDel">'.$this->cfg->deleteLabel.'</a>';
					response::getInstance()->blockJquery('
					$("#'.$this->id.'NyroDel").click(function(e) {
						e.preventDefault();
						$(this).parent("span").replaceWith("<input type=\"hidden\" name=\"'.$this->getNameAct('NyroDel').'\" value=\"1\" />");
					});');
				} else
					$end.= '<br />';
				if ($this->cfg->showPreview)
					$end.= $this->cfg->value->getView();
				$end.= '</span>';
			}
			$end.= '</'.$this->cfg->htmlWrap.'>';
		}
		return $start.utils::htmlTag($this->htmlTagName,
			array_merge($this->html, array(
				'name'=>$this->name,
				'id'=>$this->id,
			))).$end;
	}

	public function toXul() {
		return utils::htmlTag($this->xulTagName,
			array_merge($this->xul, array(
				'id'=>$this->id,
			)));
	}

}
