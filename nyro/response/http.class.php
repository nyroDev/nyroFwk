<?php
/**
 * @author Cédric Nirousset <cedric@nyrodev.com>
 * @version 0.2
 * @package nyroFwk
 */
/**
 * HTTP response
 */
class response_http extends response_abstract {

	/**
	 * Header to be sent
	 *
	 * @var array
	 */
	protected $headers;

	/**
	 * Callback function to call just before the out
	 *
	 * @var array
	 */
	protected $beforeOut = array();

	protected function afterInit() {
		$this->headers = $this->cfg->headers;
	}

	/**
	 * Get the response status
	 *
	 * @param bool $name True if the return should be the name instead of the code status
	 * @return int|string
	 */
	public function getStatus($name=false) {
		if ($name)
			return $this->cfg->statusCfg[$this->cfg->status];
		else
			return $this->cfg->status;
	}

	/**
	 * Set the reponse status by code
	 *
	 * @param int $status
	 * @return bool True if succesful
	 */
	public function setStatus($status) {
		if (array_key_exists($status, $this->cfg->statusCfg)) {
			$this->cfg->status = $status;
			return true;
		}
		return false;
	}

	/**
	 * Check if the compress mode is activate
	 *
	 * @return bool
	 */
	public function getCompress() {
		return $this->cfg->compress;
	}

	/**
	 * Activate or desactivate the compress mode
	 *
	 * @param bool $compress
	 */
	public function setCompress($compress) {
		$this->cfg->compress = (boolean) $compress;
	}

	/**
	 * Get the layout will be used
	 *
	 * @return string
	 */
	public function getLayout() {
		return $this->cfg->layout;
	}

	/**
	 * Set a new layout to use
	 *
	 * @param string $layout
	 */
	public function setlayout($layout) {
		$this->cfg->layout = $layout;
	}

	/**
	 * Get the ajax layout will be used
	 *
	 * @return string
	 */
	public function getAjaxLayout() {
		return $this->cfg->ajaxLayout;
	}

	/**
	 * Set a new ajax layout to use
	 *
	 * @param string $ajaxLayout
	 */
	public function setAjaxlayout($ajaxLayout) {
		$this->cfg->ajaxLayout = $ajaxLayout;
	}

	/**
	 * Add a http header to the response
	 *
	 * @param string $name Header name
	 * @param mixed $value Header value
	 * @param bool $replace True if replacement forced
	 * @return bool True if header added
	 */
	public function addHeader($name, $value, $replace=true) {
		if ($name == 'Content-Type')
			return $this->setContentType($value, $replace);
		if ($replace || !$this->hasHeader($name)) {
			$this->headers[$name] = $value;
			return true;
		}
		return false;
	}

	/**
	 * Set the content type Header of the response
	 *
	 * @param string $value The content type wanted (ie: html, js) (if not know in contentTypeCfg, it will be text/$value)
	 * @param bool $replace Indicate if the content-type could overwrite the current one
	 * @return bool True if the content-type was set
	 * @see setHeader
	 */
	public function setContentType($value, $replace=true) {
		if ($replace || !$this->hasHeader('Content-Type')) {
			if (array_key_exists($value, $this->cfg->contentTypeCfg))
				$value = $this->cfg->getInArray('contentTypeCfg', $value);
			else if (strpos($value, '/') === false)
				$value = 'text/'.$value;
			$this->headers['Content-Type'] = $value.'; charset='.$this->cfg->charset;
			return true;
		}
		return false;
	}

	/**
	 * Make the response to expire in 32 days
	 */
	public function neverExpire() {
		$this->addHeader('Expires', gmdate('D, j M Y H:i:s', strtotime('+32 days')).' GMT');
	}

	/**
	 * Get a header value
	 *
	 * @param string|null $name Header name or null to get all of them
	 * @return mixed|null The header value or null if not set
	 */
	public function getHeader($name=null) {
		if (is_null($name))
			return $this->headers;
		if ($this->hasHeader($name))
			return $this->headers[$name];
		return null;
	}

	/**
	 * Check if a header is set
	 *
	 * @param string $name Header name
	 * @return bool
	 */
	public function hasHeader($name) {
		return array_key_exists($name, $this->headers);
	}

	/**
	 * Clear the header by reaffecting the default values, provided in the config
	 */
	public function clearHeaders() {
		$this->headers = $this->cfg->headers;
	}

	/**
	* Send HTTP headers and cookies.
	*/
	public function sendHeaders() {
		if (!headers_sent()) {
			header('HTTP/1.0 '.$this->getStatus().' '.$this->getStatus(true));

			foreach($this->headers as $name=>$value) {
				header($name.': '.$value);
			}
		}
	}

	/**
	 * Add a callback before the out
	 *
	 * @param callback $callback
	 */
	public function addBeforeOut($callback) {
		$this->beforeOut[] = $callback;
	}

	/**
	 * Execute the before out callbacks
	 */
	protected function beforeOut() {
		foreach($this->beforeOut as $bo)
			call_user_func($bo);

		if ($this->cfg->compress && !ob_get_length())
			ob_start('ob_gzhandler');
	}

	/**
	 * Send The response
	 *
	 * @param bool $headerOnly Send only the header and exit
	 */
	public function send($headerOnly=false) {
		if (!headers_sent()) {
			$this->sendHeaders();
			$this->beforeOut();
			if ($headerOnly)
				exit(0);
		}

		$layout = request::isAjax()? $this->cfg->ajaxLayout : $this->cfg->layout;
		if (!$layout) {
			return $this->content;
		} else {
			$tpl = factory::get('tpl', array(
				'module'=>'out',
				'action'=>$layout,
				'default'=>'layout',
				'layout'=>false,
				'cache'=>array('auto'=>false)
			));
			$tpl->set('content', $this->content);
			return $tpl->fetch();
		}
	}

	/**
	 * Send a text response (exit the programm)
	 *
	 * @param string $text
	 */
	public function sendText($text) {
		$this->sendHeaders();
		$this->beforeOut();
		echo $text;
		exit(0);
	}

	/**
	 * Send a file for download
	 *
	 * @param string $file File Path
	 * @param null|string $name File name. If not provided, the real filname will be used
	 */
	public function sendFile($file, $name=null) {
		$name = $name? $name : basename($file);
		if (file::exists($file)) {
			$this->cfg->compress = false;
			$this->neverexpire();
			$this->addHeader('Pragma', 'public, no-cache');
			$this->addHeader('Last-Modified', gmdate('D, d M Y H:i:s').' GMT');
			$this->addHeader('Cache-Control', 'no-store, no-cache, must-revalidate, pre-check=0, post-check=0, max-age=0');
			$this->addHeader('Content-Transfer-Encoding', 'none');
			$this->addHeader('Content-Type', 'application/download; name="'.$name.'"');
			$this->addHeader('Content-Disposition', 'attachment; filename="'.$name.'"');
			$this->addHeader('Content-Description', 'File Transfer');
			$this->addHeader('Content-length', file::size($file).'bytes');
			$this->sendText(file::read($file));
		}
	}

	/**
	 * Show a file to the client
	 *
	 * @param string $file File Path
	 */
	public function showFile($file) {
		if (file::exists($file)) {
			$type = file::getType($file);
			if ((request::isMobile() || (isset($_SERVER['HTTP_RANGE']) && $_SERVER['HTTP_RANGE'])) && (strpos($type, 'audio') === 0 || strpos($type, 'video') === 0)) {
				$this->rangeDownload($file);
			} else {
				$this->cfg->compress = false;
				$this->neverexpire();
				$this->addHeader('Last-Modified', gmdate('D, j M Y H:i:s', filemtime($file)).' GMT', true);
				$this->addHeader('Content-Type', $type, true);
				$this->addHeader('Cache-Control', 'public', false);
				$this->addHeader('Pragma', null, false);
				$this->addHeader('Content-length', file::size($file), true);
				$this->sendText(file::read($file));
			}
		}
	}
	
	/**
	 * Send a range download for mobile devices or supported browsers
	 *
	 * @param string $file File Path
	 */
	protected function rangeDownload($file) {
		// From http://mobiforge.com/developing/story/content-delivery-mobile-devices
		$fp = @fopen($file, 'rb');
		header('Content-Type: '.file::getType($file));

		$size   = file::size($file);
		$length = $size;
		$start  = 0;
		$end    = $size - 1;
		header('Accept-Ranges: 0-'.$length);

		if (isset($_SERVER['HTTP_RANGE'])) {
			$c_start = $start;
			$c_end   = $end;
			// Extract the range string
			list(, $range) = explode('=', $_SERVER['HTTP_RANGE'], 2);
			// Make sure the client hasn't sent us a multibyte range
			if (strpos($range, ',') !== false) {
				header('HTTP/1.1 416 Requested Range Not Satisfiable');
				header('Content-Range: bytes '.$start.'-'.$end.'/'.$size);
				exit;
			}

			if ($range{0} == '-') {
				$c_start = $size - substr($range, 1);
			} else {
				$range  = explode('-', $range);
				$c_start = $range[0];
				$c_end   = (isset($range[1]) && is_numeric($range[1])) ? $range[1] : $size;
			}
			// End bytes can not be larger than $end.
			$c_end = ($c_end > $end) ? $end : $c_end;
			// Validate the requested range and return an error if it's not correct.
			if ($c_start > $c_end || $c_start > $size - 1 || $c_end >= $size) {
				header('HTTP/1.1 416 Requested Range Not Satisfiable');
				header('Content-Range: bytes '.$start.'-'.$end.'/'.$size);
				exit;
			}
			$start  = $c_start;
			$end    = $c_end;
			$length = $end - $start + 1; // Calculate new content length
			fseek($fp, $start);
			header('HTTP/1.1 206 Partial Content');
		}
		// Notify the client the byte range we'll be outputting
		header('Content-Range: bytes '.$start.'-'.$end.'/'.$size);
		header('Content-Length: '.$length);

		// Start buffered download
		$buffer = 1024 * 8;
		while(!feof($fp) && ($p = ftell($fp)) <= $end) {
			if ($p + $buffer > $end) {
				$buffer = $end - $p + 1;
			}
			set_time_limit(0); // Reset time limit for big files
			echo fread($fp, $buffer);
			flush();
		}
		fclose($fp);
		exit;
	}

	/**
	 * Return the content commented regarding the request type
	 *
	 * @param string $comment
	 * @return string
	 */
	public function comment($comment) {
		$tmp = '';
		switch(request::get('out')) {
			case 'js':
			case 'json':
			case 'css':
				$tmp = "/*\n[comment]\n*/\n";
				break;
			case 'html':
			case 'xml':
				$tmp = "<!--\n[comment]\n-->\n";
				break;
		}
		return str_replace('[comment]', $comment, $tmp);
	}

	/**
	 * Redirect the user with a header content
	 *
	 * @param string $url
	 * @param int $status
	 */
	public function redirect($url, $status=301) {
		$this->clearHeaders();
		$this->setStatus($status);
		$this->addHeader('Location', $url);
		$this->sendHeaders();
		$this->beforeOut();
		exit(0);
	}

	/**
	 * Redirect with an error the user
	 *
	 * @param string $url The url where to redirect
	 * @param int $number The HTTP error number
	 */
	public function error($url=null, $number=404) {
		$this->redirect(request::uri($url? $url : '/'.$number), $number);
	}

}
