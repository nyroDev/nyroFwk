<?php
/**
 * @author Cédric Nirousset <cedric@nyrodev.com>
 * @version 0.2
 * @package nyroFwk
 */
/**
 * Helper to send email
 */
class helper_email extends nObject {

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
	public function to($addr, $add = false) {
		$this->addr($addr, 'to', $add);
	}

	/**
	 * Add a cc address
	 *
	 * @param string|array $addr Email address
	 * @param bool $add True to add instead of replacing
	 */
	public function cc($addr, $add = false) {
		$this->addr($addr, 'cc', $add);
	}

	/**
	 * Add a bcc address
	 *
	 * @param string|array $addr Email address
	 * @param bool $add True to add instead of replacing
	 */
	public function bcc($addr, $add = false) {
		$this->addr($addr, 'bcc', $add);
	}

	/**
	 * Add an address
	 *
	 * @param string|array $addr Email address
	 * @param string $type Type (to, cc or bcc)
	 * @param bool $add True to add instead of replacing
	 */
	public function addr($addr, $type, $add = false) {
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

		$headers.= $this->headerLine('Message-ID', '<'.uniqid().'@'.$this->cfg->serverName.'>');
		$headers.= $this->headerLine('Date', date("D, d M Y G:i:s O"));
		$headers.= $this->headerLine('From', $this->formatAddr(array($this->cfg->from, $this->cfg->fromName)));

		if (empty($this->cfg->replyTo))
			$this->cfg->replyTo = $this->cfg->from;
		$headers.= $this->headerLine('Reply-To', $this->formatAddr($this->cfg->replyTo));
		
		$headers.= $this->headerLine('Return-Path', $this->cfg->from);
		$headers.= $this->headerLine('X-Sender', $this->cfg->from);
		$headers.= $this->headerLine('X-Priority', $this->cfg->priority);
		$headers.= $this->headerLine('X-Mailer', $this->cfg->xMailer);
		
		if (!empty($this->cfg->confirmReading))
			$headers.= $this->headerLine('Disposition-Notification-To', $this->formatAddr($this->cfg->confirmReading));

		if (!empty($this->cfg->cc))
			$headers.= $this->headerLine('Cc', $this->headerAddr($this->cfg->cc));

		if (!empty($this->cfg->bcc))
			$headers.= $this->headerLine('Bcc', $this->headerAddr($this->cfg->bcc));

		if (is_array($this->cfg->customHeader))
			foreach($this->cfg->customHeader as $k=>$v)
				$headers.= $this->headerLine($k, $this->encodeHeader($v));
		
		$headers.= $this->headerLine('MIME-Version', '1.0');

		if (empty($this->attachment) && strlen($this->cfg->html) == 0)
			$message_type = 'simpleText';
		else {
			if (strlen($this->cfg->text) > 0 && strlen($this->cfg->html) > 0
					&& !empty($this->attachment))
				$message_type = 'altAttach';
			else if (!empty($this->attachment) > 0)
				$message_type = 'attach';
			else
				$message_type = 'alt';
		}

		switch($message_type) {
			case 'simpleText':
				$headers.= $this->headerLine('Content-Type', 'text/plain; charset='.$this->cfg->charset);
				$headers.= $this->headerLine('Content-Transfer-Encoding', $this->cfg->encoding);
				
				$body = $this->encode($this->wrapText($this->cfg->text));
				break;
			case 'attach':
			case 'altAttach':
				$boundaryMix = $this->getBoundary();
				$headers.= $this->headerLine('Content-Type', 'multipart/mixed;'.$this->cfg->crlf.' boundary="'.$boundaryMix.'"');

				// Content Part
				$body = $this->textLine('--'.$boundaryMix);
				$body.= $this->getBody();

				$body.= $this->textLine(null);
				// Attachement Part
				foreach($this->attachment as $at) {
					$body.= $this->textLine('--'.$boundaryMix);
					$body.= $this->headerLine('Content-Type', $at['type'].'; name="'.$at['name'].'"');
					$body.= $this->headerLine('Content-Transfer-Encoding', $this->cfg->fileEncoding);
					$body.= $this->headerLine('Content-Disposition', 'attachment; name="'.$at['name'].'"');
					$body.= $this->textLine(null);
					$body.= $this->encode(file::read($at['file']), $this->cfg->fileEncoding);
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

		$param = $this->cfg->addParam;
		if ($this->cfg->addParamSender)
			$param.= $this->cfg->addParamSender.$this->cfg->from.' '.$param;
		return mail($to, $this->encodeHeader($this->cfg->subject), $body, $headers, $param);
	}

	/**
	 * Create a random boundary
	 *
	 * @return string
	 */
	protected function getBoundary($ln = 24) {
		$tmp = '';
		for($i = 0; $i < $ln; $i++)
			$tmp.= rand(0, 9);
		return $tmp;
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
					if (strlen($w)) {
						if ($l+3>75) {
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
			if (strlen($w)) {
				if ($l+1>75) {
					$e.= '='.$this->cfg->crlf;
					$l = 0;
				}
				$e.= $w;
				$l++;
				$w = '';
			}
			if (strlen($c)) {
				if ($en) {
					$c = sprintf('=%02X', $o);
					$el = 3;
					$n = 1;
					$b = 1;
				}
				else
					$el=1;
				if ($l+$el>75) {
					$e.= '='.$this->cfg->crlf;
					$l = 0;
				}
				$e.= $c;
				$l+= $el;
			}
		}
		if (strlen($w)) {
			if ($l+3>75)
				$e.= '='.$this->cfg->crlf;
			$e.= sprintf('=%02X', ord($w));
		}
		return $e;
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

			$boundary = '------------'.$this->getBoundary();

			if ($headers) {
				$headers.= $this->headerLine('Content-Type', 'multipart/alternative;'.$this->cfg->crlf.' boundary="'.$boundary.'"');
				//$headers.= $this->textLine(' boundary="'.$boundary.'"');
			} else {
				$body.= $this->headerLine('Content-Type', 'multipart/alternative;'.$this->cfg->crlf.' boundary="'.$boundary.'"');
				//$body.= $this->textLine(' boundary="'.$boundary.'"');
				$body.= $this->textLine('');
			}

			// Text part
			$body.= $this->textLine('This is a multi-part message in MIME format.');
			$body.= $this->textLine('--'.$boundary);
		}
		$body.= $this->headerLine('Content-Type', 'text/plain; charset='.$this->cfg->charset);
		//$body.= $this->textLine(' charset="'.$this->cfg->charset.'"');
		$body.= $this->headerLine('Content-Transfer-Encoding', $this->cfg->encoding);
		//$body.= $this->headerLine('Content-Disposition', 'inline');
		$body.= $this->textLine(null);
		$body.= $this->textLine($this->encode($this->wrapText($text)));

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
							$cid = 'part'.$i.'.'.$this->getBoundary(16).'@'.$this->cfg->serverName;
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
				$boundaryRel = '------------'.$this->getBoundary();
				$body.= $this->headerLine('Content-Type', 'multipart/related;'.$this->cfg->crlf.' boundary="'.$boundaryRel.'"');
				//$body.= $this->textLine(' boundary="'.$boundaryRel.'"');
				$body.= $this->textLine(null);
				$body.= $this->textLine(null);
				$body.= $this->textLine('--'.$boundaryRel);
			}

			$body.= $this->headerLine('Content-Type', 'text/html; charset='.$this->cfg->charset.'');
			//$body.= $this->textLine(' charset="'.$this->cfg->charset.'"');
			$body.= $this->headerLine('Content-Transfer-Encoding', $this->cfg->encoding);
			//$body.= $this->headerLine('Content-Disposition', 'inline');
			$body.= $this->textLine(null);
			//$body.= $this->textLine($this->quotePrintable($html));
			$body.= $this->textLine($this->encode($this->wrapText($html)));

			if (!empty($inlineImages)) {
				foreach($inlineImages as $img) {
					$body.= $this->textLine('--'.$boundaryRel);
					$body.= $this->headerLine('Content-Type', $img['type']); //.'; name="'.$img['name'].'"');
					$body.= $this->headerLine('Content-Transfer-Encoding', $this->cfg->fileEncoding);
					$body.= $this->headerLine('Content-ID', '<'.$img['cid'].'>');
					//$body.= $this->headerLine('Content-Disposition', 'inline; filename="'.$img['name'].'"');
					$body.= $this->textLine(null);
					$body.= $this->encode(file::read($img['file']), $this->cfg->fileEncoding);
				}
				$body.= $this->textLine('--'.$boundaryRel.'--');
				$body.= $this->textLine(null);
			}

			$body.= '--'.$boundary.'--';
		}

		return $body;
	}

	/**
	 * Encode a content regarding the encoding config
	 *
	 * @param string $val
	 * @param null|string $encoding Force encoding type to use
	 * @return string
	 */
	protected function encode($val, $encoding=null) {
		$encoding = is_null($encoding) ? $this->cfg->encoding : $encoding;
		switch ($encoding) {
			case 'base64':
				return chunk_split(base64_encode($val), 72, $this->cfg->crlf);
			case '8bit':
				$val = $this->fixEOL($val);
				if (substr($val, -(strlen($this->cfg->crlf))) != $this->cfg->crlf)
				  $val.= $this->cfg->crlf;
				break;
		}
		return $val;
	}


	/**
	 * Wraps message for use with mailers that do not
	 * automatically perform wrapping and for quoted-printable.
	 * 
	 * @param string $message The message to wrap
	 * @param boolean $qp_mode Whether to run in Quoted-Printable mode
	 * @return string
	*/
	public function wrapText($message, $qp_mode = false) {
		$length = $this->cfg->wrapLength;
		$soft_break = ($qp_mode) ? sprintf(" =%s", $this->cfg->crlf) : $this->cfg->crlf;
		// If utf-8 encoding is used, we will need to make sure we don't
		// split multibyte characters when we wrap
		$is_utf8 = (strtolower($this->cfg->charset) == "utf-8");

		$message = $this->fixEOL($message);
		if (substr($message, -1) == $this->cfg->crlf)
			$message = substr($message, 0, -1);

		$line = explode($this->cfg->crlf, $message);
		$cpt = count($line);
		$message = '';
		for ($i = 0 ;$i < $cpt; $i++) {
			$line_part = explode(' ', $line[$i]);
			$cptLine = count($line_part);
			$buf = '';
			for ($e = 0; $e<$cptLine; $e++) {
				$word = $line_part[$e];
				if ($qp_mode and (strlen($word) > $length)) {
					$space_left = $length - strlen($buf) - 1;
					if ($e != 0) {
						if ($space_left > 20) {
							$len = $space_left;
							if ($is_utf8) {
								$len = $this->UTF8CharBoundary($word, $len);
							} elseif (substr($word, $len - 1, 1) == "=") {
								$len--;
							} elseif (substr($word, $len - 2, 1) == "=") {
								$len -= 2;
							}
							$part = substr($word, 0, $len);
							$word = substr($word, $len);
							$buf .= ' ' . $part;
							$message .= $buf . sprintf("=%s", $this->cfg->crlf);
						} else {
							$message .= $buf . $soft_break;
						}
						$buf = '';
					}
					while (strlen($word) > 0) {
						$len = $length;
						if ($is_utf8) {
							$len = $this->UTF8CharBoundary($word, $len);
						} elseif (substr($word, $len - 1, 1) == "=") {
							$len--;
						} elseif (substr($word, $len - 2, 1) == "=") {
							$len -= 2;
						}
						$part = substr($word, 0, $len);
						$word = substr($word, $len);

						if (strlen($word) > 0) {
							$message .= $part . sprintf("=%s", $this->cfg->crlf);
						} else {
							$buf = $part;
						}
					}
				} else {
					$buf_o = $buf;
					$buf .= ($e == 0) ? $word : (' ' . $word);

					if (strlen($buf) > $length and $buf_o != '') {
						$message .= $buf_o . $soft_break;
						$buf = $word;
					}
				}
			}
			$message .= $buf . $this->cfg->crlf;
		}

		return $message;
	}

	/**
	 * Changes every end of line from CR or LF to CRLF
	 *
	 * @return string
	 */
	public function fixEOL($str) {
		$str = str_replace("\r\n", "\n", $str);
		$str = str_replace("\r", "\n", $str);
		$str = str_replace("\n", $this->cfg->crlf, $str);
		return $str;
	}

	/**
	 * Finds last character boundary prior to maxLength in a utf-8
	 * quoted (printable) encoded string
	 *
	 * @param string $encodedText Utf-8 QP text
	 * @param int $maxLength Find last character boundary prior to this length
	 * @return int
	 */
	public function UTF8CharBoundary($encodedText, $maxLength) {
		$foundSplitPos = false;
		$lookBack = 3;
		while (!$foundSplitPos) {
			$lastChunk = substr($encodedText, $maxLength - $lookBack, $lookBack);
			$encodedCharPos = strpos($lastChunk, "=");
			if ($encodedCharPos !== false) {
				// Found start of encoded character byte within $lookBack block.
				// Check the encoded byte value (the 2 chars after the '=')
				$hex = substr($encodedText, $maxLength - $lookBack + $encodedCharPos + 1, 2);
				$dec = hexdec($hex);
				if ($dec < 128) { // Single byte character.
					// If the encoded char was found at pos 0, it will fit
					// otherwise reduce maxLength to start of the encoded char
					$maxLength = ($encodedCharPos == 0) ? $maxLength :
					$maxLength - ($lookBack - $encodedCharPos);
					$foundSplitPos = true;
				} elseif ($dec >= 192) { // First byte of a multi byte character
					// Reduce maxLength to split at start of character
					$maxLength = $maxLength - ($lookBack - $encodedCharPos);
					$foundSplitPos = true;
				} elseif ($dec < 192) { // Middle byte of a multi byte character, look further back
					$lookBack += 3;
				}
			} else {
				// No encoded character found
				$foundSplitPos = true;
			}
		}
		return $maxLength;
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
			return addcslashes($val, "\0..\37\177\\\"");
			/*
			if (($val == $encoded) && !preg_match('/[^A-Za-z0-9!#$%&\'*+\/=?^_`{|}~ -]/', $val))
				return $encoded;
			else
				return $encoded;
			 */
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
