<?php
/**
 * @author Cédric Nirousset <cedric@nyrodev.com>
 * @version 0.2
 * @package nyroFwk
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

		if (is_array($this->cfg->tinyBrowser)) {
			$tinyBrowser = $this->cfg->tinyBrowser;
			$options['file_browser_callback'] = 'function(field_name, url, type, win) {
				tinyMCE.activeEditor.windowManager.open({
					file : "'.$tinyBrowser['url'].',type:"+type+"/",
					file : "'.$tinyBrowser['url'].'?'.session::getInstance()->getSessIdForce().'='.urlencode(session_id()).'&type=" + type,
					title : "'.$tinyBrowser['title'].'",
					width : '.$tinyBrowser['width'].',
					height : '.$tinyBrowser['height'].',
					resizable : "yes",
					scrollbars : "yes",
					inline : "yes",  // This parameter only has an effect if you use the inlinepopups plugin!
					close_previous : "no"
				}, {
					window : win,
					input : field_name
				});
				return false;
			}';
		}

		if (array_key_exists('content_css', $options)) {
			$contentCss = $options['content_css'];
			unset($options['content_css']);
			$options['setup'] = 'function(ed) {ed.onInit.add(function(ed) {setTimeout(function() {ed.dom.add(ed.dom.select("head"), "link", {rel : "stylesheet", href : "'.$contentCss.'"});}, 5);});}';
		}

		$optionsJs = utils::jsEncode($options);

		$resp->blockJs('tinyMCE.init('.$optionsJs.');');

		return utils::htmlTag($this->htmlTagName,
			array_merge($this->html, array(
				'name'=>$this->name,
				'id'=>$this->id,
			)), utils::htmlOut($this->getValue()));
	}

}
