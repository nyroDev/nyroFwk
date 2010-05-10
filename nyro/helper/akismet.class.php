<?php
/**
 * @author Cédric Nirousset <cedric@nyrodev.com>
 * @version 0.2
 * @package nyroFwk
 */
/**
 * Akismet API call
 *
 * @author Nyro
 */
class helper_akismet extends object {

	/**
	 * Check if the current configured comment is Spam
	 *
	 * @param array $vars Array for setting comment variables using the setCommentVar function
	 * @return bool True if it's spam, false if it's OK
	 */
	public function isSpam(array $vars = array()) {
		foreach($vars as $k=>$v)
			$this->setCommentVar($k, $v);

		$response = $this->sendRequest(
				$this->cfg->apiKey.'.'.$this->cfg->apiServer,
				'/'.$this->cfg->apiVersion.'/comment-check',
				$this->getQueryString());

		if($response[1] == 'invalid' && !$this->verifyKey())
			throw new nException('The Wordpress API key passed to the Akismet constructor is invalid. Please obtain a valid one from http://wordpress.com/api-keys/');

		return ($response[1] == 'true');
	}

	/**
	 * Submit a comment as spam
	 *
	 * @param array $vars Array for setting comment variables using the setCommentVar function
	 */
	public function submitSpam(array $vars = array()) {
		foreach($vars as $k=>$v)
			$this->setCommentVar($k, $v);

		$this->sendRequest(
				$this->cfg->apiKey.'.'.$this->cfg->apiServer,
				'/'.$this->cfg->apiVersion.'/submit-spam',
				$this->getQueryString());
	}

	/**
	 * Submit a comment as ham
	 *
	 * @param array $vars Array for setting comment variables using the setCommentVar function
	 */
	public function submitHam(array $vars = array()) {
		foreach($vars as $k=>$v)
			$this->setCommentVar($k, $v);

		$this->sendRequest(
				$this->cfg->apiKey.'.'.$this->cfg->apiServer,
				'/'.$this->cfg->apiVersion.'/submit-ham',
				$this->getQueryString());
	}

	/**
	 * Set a comment var to be send.
	 * See http://akismet.com/development/api/ for available keys
	 *
	 * @param string $key
	 * @param string $val
	 */
	public function setCommentVar($key, $val) {
		$this->cfg->setInArray('comment', $key, $val);
	}

	/**
	 * Set the comment author
	 *
	 * @param strong $author
	 */
	public function setAuthor($author) {
		$this->setCommentVar('comment_author', $author);
	}

	/**
	 * Set the author email
	 *
	 * @param string $email
	 */
	public function setAuthorEmail($email) {
		$this->setCommentVar('comment_author_email', $email);
	}

	/**
	 * Set the author url
	 *
	 * @param string $url
	 */
	public function setAuthorUrl($url) {
		$this->setCommentVar('comment_author_url', $url);
	}

	/**
	 * Set the comment content
	 *
	 * @param string $content
	 */
	public function setContent($content) {
		$this->setCommentVar('comment_content', $content);
	}

	/**
	 * Verify the key validity
	 *
	 * @return bool True if valid
	 */
	public function verifyKey() {
		$response = $this->sendRequest(
				$this->cfg->apiServer,
				'/'.$this->cfg->apiVersion.'/verify-key',
				'key='.$this->cfg->apiKey.'&blog='.$this->cfg->url);
		return $response[1] == 'valid';
	}

	/**
	 * Send the request against the submitted parameters
	 *
	 * @param string $host
	 * @param string $path
	 * @param string $request
	 * @return array
	 */
	protected function sendRequest($host, $path, $request) {
		$httpRequest = "POST ".$path." HTTP/1.0\r\n";
		$httpRequest.= "Host: ".$host."\r\n";
		$httpRequest.= "Content-Type: application/x-www-form-urlencoded; charset=utf-8\r\n";
		$httpRequest.= "Content-Length: ".strlen($request)."\r\n";
		$httpRequest.= "User-Agent: ".$this->cfg->userAgent."\r\n";
		$httpRequest.= "\r\n";
		$httpRequest.= $request;

		$response = '';
		if(false !== ($fs = @fsockopen($host, $this->cfg->apiPort, $errno, $errstr, 3))) {
			fwrite($fs, $httpRequest);
			while (!feof($fs))
				$response.= fgets($fs, 1160); // One TCP-IP packet
			fclose($fs);
			$response = explode("\r\n\r\n", $response, 2);
		}
		return $response;
	}

	/**
	 * Create the query string regarding the current configuration
	 *
	 * @return string
	 */
	protected function getQueryString() {
		$queryString = 'blog='.urlencode(stripslashes($this->cfg->url)).'&';
		$queryString.= 'user_agent='.urlencode(stripslashes($this->cfg->userAgent)).'&';
		foreach($this->cfg->comment as $k=>$v)
			$queryString.= $k.'='.urlencode(stripslashes($v)).'&';

		$vars = array_diff_key($_SERVER, array_flip($this->cfg->ignoreServerVars));
		foreach($vars as $k=>$v)
			$queryString.= $k.'='.urlencode(stripslashes($v)).'&';

		return $queryString;
	}
}