<?php
/**
 * @author Cedric Nirousset <cedric@nyrodev.com>
 * @version 0.2
 * @package nyro
 */
/**
 * ????
 * @todo
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
		if (empty($file))
			return true;
		return (!$this->cfg->maxsize || $file['size'] < $this->cfg->maxsize) && in_array($file['type'], $this->cfg->mime);
	}
}