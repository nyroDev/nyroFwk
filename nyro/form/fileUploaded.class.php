<?php

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

		if (array_key_exists($this->cfg->name, $_FILES)) {
			$this->file = $_FILES[$this->cfg->name];
			$this->file['saved'] = false;
		}

		$this->helper = factory::isCreable('helper_'.$this->cfg->helper)
			? factory::getHelper($this->cfg->helper, $this->getHelperPrm('factory'))
			: null;

		if ($this->cfg->autoSave && !empty($this->file))
			$this->save();
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
		if (!$this->file['saved'] && $this->valid()) {
			$name = $this->safeFileName($this->file['name']);
			$savePath = $this->dir.$name;
			if (move_uploaded_file($this->file['tmp_name'], $savePath)) {
				$this->callHelper('upload', $savePath);
				$webPath = str_replace(DS, '/', $this->subdir.$name);
				$this->file['saved'] = array(
					'name'=>$name,
					'savePath'=>$savePath,
					'webPath'=>$webPath
				);
				if ($this->cfg->deleteCurrent && ($current = $this->getCurrent()) && $current != $this->file['webPath']) {
					$this->callHelper('delete', $current);
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
	 */
	public function setCurrent($file) {
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
				$ret = utils::htmlTag('a', array('href'=>request::uri($current)), $current);
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
		if ($current = $this->getCurrent())
			$ret = $this->callHelper('delete', $current);
		return $ret;
	}

	/**
	 * Indicate if the file uploaded is valid
	 *
	 * @return boolean
	 */
	public function valid() {
		$file = $this->getInfo();
		return array_key_exists('error', $file) && $file['error'] === 0
			&& array_key_exists('size', $file) && $file['size'] > 0
			&& $this->callHelper('valid', $file);
	}

	/**
	 * Create a filename to not erease existing files
	 *
	 * @param string $name Filename
	 * @return string Filename useable
	 */
	protected function safeFileName($name) {
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
		return $this->getCurrent();
	}
}