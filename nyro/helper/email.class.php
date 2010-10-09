<?php
/**
 * @author Cédric Nirousset <cedric@nyrodev.com>
 * @version 0.2
 * @package nyroFwk
 */
/**
 * Helper to send email
 */
class helper_email extends object {

	/**
	 * Attached files
	 *
	 * @var array
	 */
	protected $attachment = array();

	protected function afterInit() {
		$this->parseHtml();
	}

	/**
	 * Add a to address
	 *
	 * @param string|array $addr Email address
	 * @param bool $add True to add instead of replacing
	 */
	public function to($addr, $add=false) {
		$this->addr($addr, 'to', $add);
	}

	/**
	 * Add a cc address
	 *
	 * @param string|array $addr Email address
	 * @param bool $add True to add instead of replacing
	 */
	public function cc($addr, $add=false) {
		$this->addr($addr, 'cc', $add);
	}

	/**
	 * Add a bcc address
	 *
	 * @param string|array $addr Email address
	 * @param bool $add True to add instead of replacing
	 */
	public function bcc($addr, $add=false) {
		$this->addr($addr, 'bcc', $add);
	}

	/**
	 * Add an address
	 *
	 * @param string|array $addr Email address
	 * @param string $type Type (to, cc or bcc)
	 * @param bool $add True to add instead of replacing
	 */
	public function addr($addr, $type, $add=false) {
		$tmp = $this->cfg->get($type);
		if ($add) {
			if (!is_array($tmp))
				$tmp = array($tmp);
		} else
			$tmp = array();
		$tmp[] = $addr;
		$this->cfg->set($type, $tmp);
	}

	/**
	 * Attach a file
	 *
	 * @param string|array $prm File path if string or array with key:
	 *  - string file (required) File path name
	 *  - string name File name
	 *  - string type File Mime type
	 * @return bool True if added (file exists)
	 */
	public function attach($prm) {
		if (!is_array($prm))
			$prm = array('file'=>$prm);

		if (!array_key_exists('file', $prm))
			return false;

		$ret = false;
		if (file::exists($prm['file'])) {
			if (!array_key_exists('name', $prm))
				$prm['name'] = file::name($prm['file']);
			if (!array_key_exists('type', $prm))
				$prm['type'] = file::getType($prm['file']);
			$this->attachment[] = $prm;
			$ret = true;
		}
		return $ret;
	}

	/**
	 * Send the email
	 *
	 * @return bool True if successful
	 */
	public function send() {
		if (!is_array($this->cfg->to))
			$this->cfg->to = array($this->cfg->to);
		if (!is_array($this->cfg->cc))
			$this->cfg->cc = array($this->cfg->cc);
		if (!is_array($this->cfg->bcc))
			$this->cfg->bcc = array($this->cfg->bcc);

		if (empty($this->cfg->serverName))
			$this->cfg->serverName = request::get('serverName');

		$headers = '';

		$headers.= $this->headerLine('Return-Path', $this->cfg->from);
		$headers.= $this->headerLine('Date', date("D, d M Y G:i:s O"));
		$headers.= $this->headerLine('X-Sender', $this->cfg->from);
		$headers.= $this->headerLine('Message-ID', '<'.uniqid().'@'.$this->cfg->serverName.'>');
		$headers.= $this->headerLine('X-Priority', $this->cfg->priority);
		$headers.= $this->headerLine('X-Mailer', $this->cfg->xMailer);
		$headers.= $this->headerLine('From', $this->formatAddr(array($this->cfg->from, $this->cfg->fromName)));

		if (empty($this->cfg->replyTo))
			$this->cfg->replyTo = $this->cfg->from;
		$headers.= $this->headerLine('Reply-To', $this->formatAddr($this->cfg->replyTo));

		if(!empty($this->cfg->confirmReading))
			$headers.= $this->headerLine('Disposition-Notification-To', $this->formatAddr($this->cfg->confirmReading));

		if (!empty($this->cfg->cc))
			$headers.= $this->headerLine('Cc', $this->headerAddr($this->cfg->cc));

		if (!empty($this->cfg->bcc))
			$headers.= $this->headerLine('Bcc', $this->headerAddr($this->cfg->bcc));

		if (is_array($this->cfg->customHeader))
			foreach($this->cfg->customHeader as $k=>$v)
				$headers.= $this->headerLine($k, $this->encodeHeader($v));

		$headers.= $this->headerLine('MIME-Version', '1.0');

		if(empty($this->attachment) && strlen($this->cfg->html) == 0)
			$message_type = 'simpleText';
		else {
			if(strlen($this->cfg->text) > 0 && strlen($this->cfg->html) > 0
					&& !empty($this->attachment))
				$message_type = 'altAttach';
			else if(!empty($this->attachment) > 0)
				$message_type = 'attach';
			else
				$message_type = 'alt';
		}

		switch($message_type) {
			case 'simpleText':
				$headers.= $this->headerLine('Content-Type', 'text/plain; charset='.$this->cfg->charset);
				$headers.= $this->headerLine('Content-Transfer-Encoding', $this->cfg->encoding);
				$body = $this->encode($this->cfg->text);
				break;
			case 'attach':
			case 'altAttach':
				$boundaryMix = $this->getBoundary();
				$headers.= $this->headerLine('Content-Type', 'multipart/mixed; boundary="'.$boundaryMix.'"');

				// Content Part
				$body = $this->textLine('--'.$boundaryMix);
				$body.= $this->getBody();

				// Attachement Part
				foreach($this->attachment as $at) {
					$body.= $this->textLine('--'.$boundaryMix);
					$body.= $this->headerLine('Content-Type', $at['type'].'; name="'.$at['name'].'"');
					$body.= $this->headerLine('Content-Transfer-Encoding', $this->cfg->encoding);
					$body.= $this->headerLine('Content-Disposition', 'attachment; name="'.$at['name'].'"');
					$body.= $this->textLine(null);
					$body.= $this->encode(file::read($at['file']));
					$body.= $this->textLine(null);
				}
				$body.= $this->textLine('--'.$boundaryMix.'--');
				break;
			case 'alt':
				$body = $this->getBody($headers);
				break;
		}

		$addr = array();
		foreach($this->cfg->to as $v)
			$addr[] = $this->formatAddr($v);
		$to = implode(', ', $addr);

		return mail($to, $this->encodeHeader($this->cfg->subject), $body, $headers, $this->cfg->addParam);
	}

	/**
	 * Create a random boundary
	 *
	 * @return string
	 */
	protected function getBoundary() {
		return '_Part_'.md5(uniqid (rand())).'_';
	}

	/**
	 * Quote a string to be included in a "printable" email part
	 *
	 * @param string $str The string to quote
	 * @return string
	 */
	protected function quotePrintable($str) {
		$ln=strlen($str);
		for($w=$e='', $n=0, $l=0, $i=0; $i<$ln; $i++) {
			$c = $str[$i];
			$o = ord($c);
			$en = 0;
			switch($o) {
				case 9:
				case 32:
					$w = $c;
					$c = '';
					break;
				case 10:
				case 13:
					if(strlen($w)) {
						if($l+3>75) {
							$e.= '='.$this->cfg->crlf;
							$l = 0;
						}
						$e.= sprintf('=%02X', ord($w));
						$l+= 3;
						$w = '';
					}
					$e.= $c;
					$l = 0;
					continue 2;
				case 46:
				case 70:
				case 102:
					$en = ($l==0 || $l+1>75);
					break;
				default:
					if ($o>127 || $o<32 || !strcmp($c,'='))
						$en = 1;
					break;
			}
			if(strlen($w)) {
				if($l+1>75) {
					$e.= '='.$this->cfg->crlf;
					$l = 0;
				}
				$e.= $w;
				$l++;
				$w = '';
			}
			if(strlen($c)) {
				if($en) {
					$c = sprintf('=%02X', $o);
					$el = 3;
					$n = 1;
					$b = 1;
				}
				else
					$el=1;
				if($l+$el>75) {
					$e.= '='.$this->cfg->crlf;
					$l = 0;
				}
				$e.= $c;
				$l+= $el;
			}
		}
		if(strlen($w)) {
			if($l+3>75)
				$e.= '='.$this->cfg->crlf;
			$e.= sprintf('=%02X', ord($w));
		}
		return $e;



		$return = '';
		$iL = strlen($str);
		for($i=0; $i<$iL; $i++) {
			$char = $str[$i];
			if(ctype_print($char) && !ctype_punct($char))
				$return .= $char;
			else
				$return .= sprintf('=%02X', ord($char));
		}
		return $return;
		return str_replace('%', '=', rawurlencode($str));
	}


	/**
	 * Get the body content
	 *
	 * @param string|null $headers Header to edit or null to add the header in the email
	 * @return string
	 */
	protected function getBody(&$headers=null) {
		$body = null;

		//$text = $this->quotePrintable($this->cfg->text);
		$text = $this->cfg->text;

		if ($this->cfg->html) {
			if (empty($this->cfg->text))
				//$text = $this->quotePrintable(utils::html2Text($this->cfg->html));
				$text = utils::html2Text($this->cfg->html);

			$boundary = $this->getBoundary();

			if ($headers) {
				$headers.= $this->headerLine('Content-Type', 'multipart/alternative; boundary="'.$boundary.'"');
				//$headers.= $this->textLine(' boundary="'.$boundary.'"');
			} else {
				$body.= $this->headerLine('Content-Type', 'multipart/alternative; boundary="'.$boundary.'"');
				//$body.= $this->textLine(' boundary="'.$boundary.'"');
				$body.= $this->textLine('');
			}

			// Text part
			$body.= $this->textLine('--'.$boundary);
		}
		$body.= $this->headerLine('Content-Type', 'text/plain; charset='.$this->cfg->charset.'');
		//$body.= $this->textLine(' charset="'.$this->cfg->charset.'"');
		$body.= $this->headerLine('Content-Transfer-Encoding', $this->cfg->encoding);
		$body.= $this->headerLine('Content-Disposition', 'inline');
		$body.= $this->textLine(null);
		$body.= $this->textLine($this->encode($text));

		if ($this->cfg->html) {
			// HTML part
			$body.= $this->textLine('--'.$boundary);

			$html = $this->cfg->html;

			$inlineImages = false;
			if ($this->cfg->htmlInlineImage) {
				$rootUri = request::get('rootUri');
				preg_match_all('@src="('.$rootUri.'|/)(.+)"@siU', $html, $matches);
				if (!empty($matches)) {
					$images = array_unique($matches[2]);
					$inlineImages = array();
					$i = 1;
					foreach($images as $img) {
						if (file::webExists($img)) {
							$file = WEBROOT.str_replace('/', DS, $img);
							$cid = 'part'.$i.'.'.$this->getBoundary();
							$inlineImages[] = array(
								'cid'=>$cid,
								'file'=>$file,
								'name'=>file::name($file),
								'type'=>file::getType($file)
							);
							$i++;
							$html = preg_replace('@src="('.$rootUri.'|/)('.$img.')"@siU', 'src="cid:'.$cid.'"', $html);
						}
					}
				}
			}

			if (!empty($inlineImages)) {
				$boundaryRel = $this->getBoundary();
				$body.= $this->headerLine('Content-Type', 'multipart/related; boundary="'.$boundaryRel.'"');
				//$body.= $this->textLine(' boundary="'.$boundaryRel.'"');
				$body.= $this->textLine(null);
				$body.= $this->textLine('--'.$boundaryRel);
			}

			$body.= $this->headerLine('Content-Type', 'text/html; charset='.$this->cfg->charset.'');
			//$body.= $this->textLine(' charset="'.$this->cfg->charset.'"');
			$body.= $this->headerLine('Content-Transfer-Encoding', $this->cfg->encoding);
			$body.= $this->headerLine('Content-Disposition', 'inline');
			$body.= $this->textLine(null);
			//$body.= $this->textLine($this->quotePrintable($html));
			$body.= $this->textLine($this->encode($html));

			if (!empty($inlineImages)) {
				foreach($inlineImages as $img) {
					$body.= $this->textLine('--'.$boundaryRel);
					$body.= $this->headerLine('Content-Type', $img['type'].'; name="'.$img['name'].'"');
					$body.= $this->headerLine('Content-Transfer-Encoding', $this->cfg->encoding);
					$body.= $this->headerLine('Content-ID', '<'.$img['cid'].'>');
					$body.= $this->headerLine('Content-Disposition', 'inline; filename="'.$img['name'].'"');
					$body.= $this->textLine(null);
					$body.= $this->textLine($this->encode(file::read($img['file'])));
				}
				$body.= $this->textLine('--'.$boundaryRel.'--');
				$body.= $this->textLine(null);
			}

			$body.= $this->textLine('--'.$boundary.'--');
			$body.= $this->textLine(null);
		}

		return $body;
	}

	/**
	 * Encode a content regarding the encoding config
	 *
	 * @param string $val
	 * @return string
	 */
	protected function encode($val) {
		switch($this->cfg->encoding) {
			case 'base64';
				return chunk_split(base64_encode($val));
		}
		return $val;
	}

	/**
	 * Create the string to add in the header
	 *
	 * @param string $name Header name
	 * @param mixed $val Header value
	 * @return string
	 */
	protected function headerLine($name, $val) {
		return $this->textLine($name.': '.$val);
	}

	/**
	 * Create a text line with a crlf
	 *
	 * @param string $val
	 * @return string
	 */
	protected function textLine($val) {
		return $val.$this->cfg->crlf;
	}

	/**
	 * Enter description here...
	 *
	 * @param unknown_type $name
	 * @param array $val
	 * @return unknown
	 */
	protected function headerAddr(array $val) {
		$addr = array();
		foreach($val as $v)
			$addr[] = $this->formatAddr($v);

		return implode(', ', $addr);
	}

	/**
	 * Format address
	 *
	 * @param string|array $addr email or array with email and name
	 * @return string
	 */
	protected function formatAddr($addr) {
		if (is_array($addr)) {
			if (array_key_exists(1, $addr))
				return $this->encodeHeader($addr[1]).' <'.$addr[0].'>';
			else
				return $addr[0];
		} else
			return $addr;
	}

	/**
	 * Encode header
	 *
	 * @param string $val
	 * @param string $type Type of content (text or phrase)
	 * @return string
	 */
	protected function encodeHeader($val) {
		$nb = 0;

		if (!preg_match('/[\200-\377]/', $val)) {
			$encoded = addcslashes($val, "\0..\37\177\\\"");
			if (($val == $encoded) &&
				!preg_match('/[^A-Za-z0-9!#$%&\'*+\/=?^_`{|}~ -]/', $val))
				return ($encoded);
			else
				return '"'.$encoded.'"';
		}
		$nb = preg_match_all('/[^\040\041\043-\133\135-\176]/', $val, $matches);

		if ($nb == 0)
			return $val;

		$maxlen = 75;
		$maxlen = 75 - 7 - strlen($this->cfg->charset) - $maxlen % 4;
		$encoded = trim(chunk_split(base64_encode($val), $maxlen, "\n"));
		$encoded = preg_replace('/^(.*)$/m', ' =?'.$this->cfg->charset.'?B?\\1?=', $encoded);
		$encoded = trim(str_replace("\n", $this->cfg->crlf, $encoded));

		return $encoded;
	}

	/**
	 * Render the HTML setting if it is set to used a tpl
	 */
	protected function parseHtml() {
		if (is_array($this->cfg->html))
			$this->cfg->html = utils::render($this->cfg->html);
	}

	public function __get($name) {
		return $this->cfg->get($name);
	}

	public function __set($name, $val) {
		$this->cfg->set($name, $val);
		if ($name == 'html')
			$this->parseHtml();
	}

}
