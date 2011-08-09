<?php
/**
 * @author Cédric Nirousset <cedric@nyrodev.com>
 * @version 0.2
 * @package nyroFwk
 */
/**
 * Used by form_file to save the uploaded file
 */
class form_fileUploaded extends object {

	/**
	 * The files uploaded info, retrieved from $_FILES
	 *
	 * @var array
	 */
	protected $file = array();

	/**
	 * Directory where the files are saved, eventually including the subdir
	 *
	 * @var string
	 */
	protected $dir;

	/**
	 * The saved web path name
	 *
	 * @var string
	 */
	protected $saved;

	/**
	 * Subdirectory configured, by adding a directory separator if needed
	 *
	 * @var string
	 */
	protected $subdir;

	/**
	 * Potential Helper for valid, upload and delete action
	 *
	 * @var helper_$type|null
	 */
	protected $helper;

	protected function afterInit() {
		$this->dir = $this->cfg->dir;
		if ($this->cfg->subdir) {
			$this->subdir = $this->cfg->subdir.DS;
			$this->dir.= $this->subdir;
		}
		file::createDir($this->dir);

		if (strpos($this->cfg->name, '[')) {
			$tmp = explode('[', str_replace(']', '', $this->cfg->name));
			$tmpfile = utils::getValInArray($_FILES, $tmp);
			if (!empty($tmpfile) && is_array($tmpfile)) {
				$this->file = $tmpfile;
				$this->file['saved'] = false;
			}
		} else if (array_key_exists($this->cfg->name, $_FILES)) {
			$this->file = $_FILES[$this->cfg->name];
			$this->file['saved'] = false;
		}

		$this->helper = factory::isCreable('helper_'.$this->cfg->helper)
			? factory::getHelper($this->cfg->helper, $this->getHelperPrm('factory'))
			: null;

		if ($this->cfg->autoSave && !empty($this->file) && $this->isvalid() === true)
			$this->save();
	}

	/**
	 * Get the helper object
	 *
	 * @return null|helper_file
	 */
	public function getHelper() {
		return $this->helper;
	}
	
	/**
	 * Get helper parameters to use with callHelper
	 *
	 * @param string $name Parameter name
	 * @return array The value parameter
	 * @see callHelper
	 */
	protected function getHelperPrm($name) {
		$tmp = $this->cfg->getInArray('helperPrm', $name);
		return is_array($tmp)? $tmp : array();
	}

	/**
	 * Call an helper function
	 *
	 * @param string $fct Function name
	 * @param string $filename Filename
	 * @param mixed $defRet Default return if not callabled
	 * @return mixed The helper function return or $defRet
	 */
	protected function callHelper($fct, $filename = null, $defRet = true) {
		$callback = array($this->helper, $fct);
		if ($this->helper && is_callable($callback))
			return call_user_func($callback, $filename, $this->getHelperPrm($fct));
		return $defRet;
	}

	/**
	 * Save the file uploaded.
	 * Add a key 'saved' in the file array info
	 */
	public function save() {
		if (!$this->file['saved'] && $this->isValid() === true) {
			$name = $this->safeFileName($this->file['name']);
			$savePath = $this->dir.$name;
			if (move_uploaded_file($this->file['tmp_name'], $savePath)) {
				$tmpName = $this->callHelper('upload', $savePath);
				if ($tmpName && !is_bool($tmpName)) {
					$name = $tmpName;
					$savePath = $this->dir.$tmpName;
				}
				$webPath = str_replace(DS, '/', $this->subdir.$name);
				$this->file['saved'] = array(
					'name'=>$name,
					'savePath'=>$savePath,
					'webPath'=>$webPath
				);
				if ($this->cfg->deleteCurrent && ($current = $this->getCurrent())
						&& str_replace('/', DS, $savePath) != str_replace('/', DS, $this->cfg->dir.$current)
						&& (!array_key_exists('webPath', $this->file) || $current != $this->file['webPath'])) {
					$this->callHelper('delete', $current);
					file::delete($this->cfg->dir.$current);
					$this->setCurrent(null);
				}
				$this->saved = $webPath;
			}
		}
	}

	/**
	 * The files uploaded info, retrieved from $_FILES
	 *
	 * @return array
	 */
	public function getInfo() {
		return $this->file;
	}

	/**
	 * Get the current file path
	 *
	 * @return string
	 */
	public function getCurrent() {
		return $this->saved? $this->saved : $this->cfg->current;
	}

	/**
	 * Set the current file path
	 *
	 * @param string $file
	 * @param bool $refill
	 */
	public function setCurrent($file, $refill=false) {
		if ($file || !$refill)
			$this->cfg->current = $file;
	}

	/**
	 * Get the view for the uploaded element
	 *
	 * @return string
	 */
	public function getView() {
		$ret = null;
		if ($current = $this->getCurrent()) {
			if (!$ret = $this->callHelper('view', $current, null))
				$ret = utils::htmlTag('a', array('href'=>request::uploadedUri($current)), $current);
		}
		return $ret;
	}

	/**
	 * Indicate if the file was saved in this request
	 *
	 * @return boolean
	 */
	public function isSaved() {
		return array_key_exists('saved', $this->file) && $this->file['saved'];
	}

	/**
	 * Delete the uploaded file
	 *
	 * @return string
	 */
	public function delete() {
		$ret = false;
		$current = $this->getCurrent();
		if ($current && strpos($current, $this->cfg->dir) === false)
			$current = $this->cfg->dir.$current;
		if ($current && file::exists($current)) {
			$ret = $this->callHelper('delete', $current);
			file::delete($current);
			$this->setCurrent(null);
		}
		return $ret;
	}

	/**
	 * Indicate if the file uploaded is valid
	 *
	 * @return boolean
	 */
	public function isValid() {
		$file = $this->getInfo();
		$tmp = !request::isPost() || ($this->cfg->current) ||
			(array_key_exists('error', $file) && $file['error'] === 0
			&& array_key_exists('size', $file) && $file['size'] > 0);
		$helperValid = $this->callHelper('valid', $file);
		return $tmp? (is_bool($helperValid) ? $helperValid : $helperValid) : ($this->cfg->required ? 'required' : true);
	}

	/**
	 * Create a filename to not erease existing files
	 *
	 * @param string $name Filename
	 * @return string Filename useable
	 */
	protected function safeFileName($name) {
		$name = utils::urlify(strtolower($name), '.');
		$ext = file::getExt($name);
		if ($ext)
			$ext = '.'.$ext;
		$initName = substr($name, 0, -strlen($ext));
		$i = 2;
		while(file::exists($this->dir.DS.$name)) {
			$name = $initName.'-'.$i.$ext;
			$i++;
		}
		return $name;
	}

	public function __toString() {
		return $this->getCurrent()? $this->getCurrent() : '';
	}

}