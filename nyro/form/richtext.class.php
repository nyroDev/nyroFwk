<?php
/**
 * @author Cedric Nirousset <cedric@nyrodev.com>
 * @version 0.2
 * @package nyro
 */
/**
 * Form richtext element
 */
class form_richtext extends form_multiline {

	public function getValue() {
		return $this->cfg->value;
	}

	public function setValue($value, $refill=false) {
		$this->cfg->set('value', $value);
	}

	public function toHtml() {
		if ($this->cfg->mode == 'view')
			return $this->getValue();
		$options = array_merge($this->tinyMce, array(
			'mode'=>'exact',
			'elements'=>$this->id
		));
		array_filter($options);

		$resp = response::getInstance()->getProxy();

		if (array_key_exists('plugins', $options))
			$resp->tinyMceGzip('plugins', $options['plugins']);
		if (array_key_exists('theme', $options))
			$resp->tinyMceGzip('themes', $options['theme']);
		if (array_key_exists('language', $options))
			$resp->tinyMceGzip('languages', $options['language']);

		$resp->addJs(array(
			'file'=>'tiny_mce/tiny_mce_gzip',
			'dir'=>'web',
			'verifExists'=>false
		));
		$resp->blockJs('tinyMCE.init('.json_encode($options).');');

		return utils::htmlTag($this->htmlTagName,
			array_merge($this->html, array(
				'name'=>$this->name,
				'id'=>$this->id,
			)), utils::htmlOut($this->getValue()));
	}
	
}
