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
	public function getStatus($name = false) {
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
	public function addHeader($name, $value, $replace = true) {
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
	public function setContentType($value, $replace = true) {
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
	public function getHeader($name = null) {
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
	public function send($headerOnly = false) {
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
	 * @param bool $delete Indicate if the file should be deleted after download
	 */
	public function sendFile($file, $name = null, $delete = false) {
		$name = $name? $name : basename($file);
		if (file::exists($file))
			$this->mediaDownload($file, true, $name, $delete);
		else
			$this->error();
	}

	/**
	 * Send a file for download using a string
	 *
	 * @param string $file File contents
	 * @param string $name File name.
	 */
	public function sendFileAsString($file, $name) {
		$this->cfg->compress = false;
		$this->neverExpire();
		$this->addHeader('Expires', '0');
		$this->addHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0');
		$this->addHeader('Content-Type', 'application/force-download');
		$this->addHeader('Content-Disposition', 'attachment; filename='.$name.'');
		$this->addHeader('Last-Modified', gmdate('D, j M Y H:i:s').' GMT', true);
		$this->addHeader('Pragma', null, false);
		$this->sendText($file);
	}

	/**
	 * Show a file to the client
	 *
	 * @param string $file File Path
	 */
	public function showFile($file) {
		if (file::exists($file)) {
			$type = file::getType($file);
			if (strpos($type, 'audio') === 0 || strpos($type, 'video') === 0) {
				$this->mediaDownload($file);
			} else {
				$this->cfg->compress = false;
				$this->neverExpire();
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
	 * Send a media to download, using HTTP range or not, is possible
	 *
	 * @param string $file File Path
	 * @param bool $forceDownload True if the media should be forced to download
	 * @param string $fileName Filename to send to the browser. If null, basename will be used
	 * @param bool $delete Indicate if the file should be deleted after download
	 */
	protected function mediaDownload($file, $forceDownload = false, $fileName = null, $delete = false) {
		$fileName = $fileName ? $fileName : basename($file);
		if(strstr($_SERVER['HTTP_USER_AGENT'], 'MSIE'))
			$fileName = preg_replace('/\./', '%2e', $fileName, substr_count($fileName, '.') - 1);
		$fileModified = filemtime($file);
		$fileSize = filesize($file);
		$fileType = file::getType($file);
		$audio = strpos($fileType, 'audio') === 0;
		$video = strpos($fileType, 'video') === 0;

		$seekStart = 0;
		$seekEnd = -1;
		$bufferSize = 8*1024;
		$partialDownload = false;
		$httpRangeDownload = false;

		if (isset($_SERVER['HTTP_RANGE'])) {
			$range = explode('-', substr($_SERVER['HTTP_RANGE'], strlen('bytes=')));
			if($range[0] > 0)
				$seekStart = intval($range[0]);
			$seekEnd = $range[1] > 0 ? intval($range[1]) : -1;
			$partialDownload = true;
			$httpRangeDownload = true;
		} else if ($audio && request::isMobile()) {
			$partialDownload = true;
			$httpRangeDownload = true;
		}

		if ($seekEnd < $seekStart)
			$seekEnd = $fileSize - 1;

		$contentLength = $seekEnd - $seekStart + 1;

		if(!$fileHandle = fopen($file, 'rb'))
			$this->error();

		// headers
		header('Pragma: public');
		if ($forceDownload) {
			if (ini_get('zlib.output_compression'))
				ini_set('zlib.output_compression', 'Off');

			header('Expires: 0');
			header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
			header('Cache-Control: private', false);

			header('Content-Type: application/force-download');
			header('Content-Type: application/octet-stream');
			header('Content-Type: application/download');
			header('Content-type: '.$fileType);
			header('Content-Disposition: attachment; filename='.$fileName.'');
		} else {
			header('Cache-Control: public');
			header('Content-type: '.$fileType);
			header('Content-Disposition: inline; filename='.$fileName.'');
		}
		header('Last-Modified: '.date('D, d M Y H:i:s \G\M\T', $fileModified));
		header("Content-Transfer-Encoding: binary\n");
		if ($httpRangeDownload) {
			header('HTTP/1.0 206 Partial Content');
			header('Status: 206 Partial Content');
			header('Accept-Ranges: bytes');
			header('Content-Range: bytes '.$seekStart.'-'.$seekEnd.'/'.$fileSize);
		}
		header('Content-Length: '.$contentLength);

		if ($seekStart > 0) {
			$partialDownload = true;
			fseek($fileHandle, $seekStart);
			if ($fileType == 'video/x-flv')
				echo 'FLV', pack('C', 1), pack('C', 1), pack('N', 9), pack('N', 9);
		}

		$this->setCompress(false);
		$this->beforeOut();

		$speed = 0;
		$bytesSent = 0;
		$chunk = 1;
		$throttle = $video ? 320 : ($audio ? 84 : 0);
		$burst = 1024 * ($video ? 500 : ($audio ? 120 : 0));
		while (!(connection_aborted() || connection_status() == 1) && $bytesSent < $contentLength) {
			// 1st buffer size after the first burst has been sent
			if ($bytesSent >= $burst)
				$speed = $throttle;

			// make sure we don't read past the total file size
			if ($bytesSent + $bufferSize > $contentLength)
				$bufferSize = $contentLength - $bytesSent;

			// send data
			echo fread($fileHandle, $bufferSize);
			$bytesSent+= $bufferSize;

			// clean up
			flush();

			// throttle
			if($speed && ($bytesSent - $burst > $speed * $chunk*1024)) {
				sleep(1);
				$chunk++;
			}
		}

		fclose($fileHandle);

		if ($delete)
			file::delete($file);
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
	public function redirect($url, $status = 301) {
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
	public function error($url = null, $number = 404) {
		$this->redirect(request::uri($url? $url : '/'.$number), $number);
	}

}
