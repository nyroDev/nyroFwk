<?php
/**
 * @author Cédric Nirousset <cedric@nyrodev.com>
 * @version 0.2
 * @package nyroFwk
 */
/**
 * bitLy API call
 *
 * @author Nyro
 */
class helper_bitLy extends object {

	/**
	 * Get Default parameter set in configuration file
	 *
	 * @return array
	 */
	protected function getDefPrm() {
		return $this->cfg->param;
	}

	/**
	 * Call the API and return an array of the json object if OK
	 *
	 * @param string $action The action to perform
	 * @param array $prm The setting for the call
	 * @return stdClass
	 */
	protected function doAction($action, array $prm) {
		$prm = array_merge($this->getDefPrm(), $prm);
		$url = $this->cfg->url.$action.'?';
		foreach($prm as $k=>$v)
			$url.= $k.'='.$v.'&';
		$content = file_get_contents($url);
		if ($content)
			return json_decode($content);
	}

	/**
	 * Shorten an URL with bit.ly
	 *
	 * @param string $longUrl The URL to shorten
	 * @return string the URL shortened
	 */
	public function shorten($longUrl) {
		$content = $this->doAction('shorten', array('longUrl'=>$longUrl));
		if ($content->statusCode == 'OK') {
			return $content->results->$longUrl->shortUrl;
		}
		return null;
	}

}
