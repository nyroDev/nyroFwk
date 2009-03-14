<?php

class helper_file extends object {

	public function valid(array $file, $prm = null) {
		return (!$this->cfg->maxsize || $file['size'] < $this->cfg->maxsize) && in_array($file['type'], $this->cfg->mime);
	}
}