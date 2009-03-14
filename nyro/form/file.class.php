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

	protected function beforeInit() {
		$this->cfg->value = factory::get('form_fileUploaded', array(
			'name'=>$this->cfg->name,
			'current'=>$this->cfg->value,
			'helper'=>$this->cfg->helper,
			'helperPrm'=>$this->cfg->helperPrm
		));
		if (!$this->cfg->value->isSaved() && http_vars::getInstance()->getVar($this->name.'NyroDel')) {
			$this->cfg->value->delete();
		}
	}

	/**
	 * Get the actual value
	 *
	 * @return mixed
	 */
	public function getValue() {
		return $this->cfg->value->getCurrent();
	}

	/**
	 * Set the form element value
	 *
	 * @param mixed $value The value
	 */
	public function setValue($value) {
		$this->cfg->value->setCurrent($value);
	}

	public function toHtml() {
		if ($this->cfg->mode == 'view')
			return $this->cfg->value->getView();

		$delLink = null;
		if ($this->cfg->value->getCurrent()) {
			$delLink = '<span><br /><br />
				<a href="#" class="deleteFile" id="'.$this->id.'NyroDel">delete</a><br />'
					.$this->cfg->value->getView().'</span></p>';
			response::getInstance()->blockJquery('
			$("#'.$this->id.'NyroDel").click(function(e) {
				var me = $(this);
				e.preventDefault();
				me.parent("span").replaceWith("<input type=\"hidden\" name=\"'.$this->name.'NyroDel\" value=\"1\" />");
				/*
				$.ajax({
					url: "'.request::uri(array(
						'module'=>'utils',
						'action'=>'deleteUploadFile',
						'paramA'=>$prm
						)).'",
					success: function() {
						me.parent("span").replaceWith("<input type=\"hidden\" name=\"'.$this->name.'NyroDel\" value=\"1\" />");
					}
				});
				*/
			});');
		}
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
