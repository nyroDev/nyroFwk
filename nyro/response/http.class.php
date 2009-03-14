<?php

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
	 * Add a http header to the response
	 *
	 * @param string $name Header name
	 * @param mixed $value Header value
	 * @param bool $replace True if replacement forced
	 * @return bool True if header added
	 */
	public function addHeader($name, $value, $replace=true) {
		if ($name == 'Content-Type') {
			return $this->setContentType($value, $replace);
		}
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

			if ($this->cfg->compress)
				ob_start('ob_gzhandler');
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
			$this->addHeader('Expires', date('r', time() - 60*60*24*30));
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
	 * show a file to the client
	 *
	 * @param string $file File Path
	 */
	public function showFile($file) {
		$name = $name? $name : basename($file);
		if (file::exists($file)) {
			$this->addHeader('Content-Type', file::getType($file));
			$this->addHeader('Content-length', file::size($file).'bytes');
			$this->sendText(file::read($file));
		}
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
		$url = $url? $url : '/'.$number;
		$this->redirect(request::uri($url), $number);
	}
}
