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

		$options = $this->tinyMce;

		if (is_array($this->cfg->tinyBrowser)) {
			$tinyBrowser = $this->cfg->tinyBrowser;
			$options['file_browser_callback'] = 'function(field_name, url, type, win) {
				tinyMCE.activeEditor.windowManager.open({
					file : "'.$tinyBrowser['url'].'?'.session::getInstance()->getSessIdForce().'='.urlencode(session_id()).'&type=" + type'.($tinyBrowser['subdir'] ? '+"&subdir='.$tinyBrowser['subdir'].'"' : '').',
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
		} else if (is_array($this->cfg->nyroBrowser) && $this->cfg->getInArray('nyroBrowser', 'active')) {
			$nyroBrowser = $this->cfg->nyroBrowser;
			$options['file_browser_callback'] = 'function(field_name, url, type, win) {
				tinyMCE.activeEditor.windowManager.open({
					file : "'.$nyroBrowser['url'].'?'.session::getInstance()->getSessIdForce().'='.urlencode(session_id()).'&type="+type+"&config='.$nyroBrowser['config'].'&",
					title : "'.$nyroBrowser['title'].'",
					width : '.$nyroBrowser['width'].',
					height : '.$nyroBrowser['height'].',
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

		if (array_key_exists('content_css', $options) && $options['content_css']) {
			$contentCss = $options['content_css'];
			$options['setup'] = 'function(ed) {ed.onInit.add(function(ed) {setTimeout(function() {ed.dom.add(ed.dom.select("head"), "link", {rel : "stylesheet", href : "'.$contentCss.'"});}, 5);});}';
		}
		unset($options['content_css']);

		$resp = response::getInstance()->getProxy();
		$resp->addJs('jquery.tinymce');
		$resp->blockjQuery('$("#'.$this->id.'").tinymce('.utils::jsEncode($options).');');

		return utils::htmlTag($this->htmlTagName,
			array_merge($this->html, array(
				'name'=>$this->name,
				'id'=>$this->id,
			)), utils::htmlOut($this->getValue()));
	}

}
