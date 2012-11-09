<?php
/**
 * @author Cédric Nirousset <cedric@nyrodev.com>
 * @version 0.2
 * @package nyroFwk
 */
/**
 * File upload helper
 */
class helper_file extends object {

	/**
	 * Check if the image is valid (to be used by form_fileUploaded)
	 *
	 * @param string $file File pathname
	 * @param array $prm
	 * @return bool|string True if valid or string representing the error
	 */
	public function valid(array $file, array $prm = array()) {
		if (empty($file) || (isset($file['size']) && $file['size'] == 0))
			return true;
		
		if ($this->cfg->maxsize && $file['size'] > $this->cfg->maxsize)
			return sprintf($this->cfg->getInArray('validErrors', 'maxsize'), '%s', file::humanSize($this->cfg->maxsize, true));
		
		$type = $file['type'] != 'application/octet-stream' ? $file['type'] : file::getType($file['name']);
		if (count($this->cfg->mime) > 0 && !in_array($type, $this->cfg->mime))
			return $this->cfg->getInArray('validErrors', 'mime');
		
		return true;
	}

}