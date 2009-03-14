<?php
/**
 * @author Cedric Nirousset <cedric@nyrodev.com>
 * @version 0.2
 * @package nyro
 */
/**
 * Cache sing files
 * Singleton
 */
class cache_file extends cache_abstract {

	/**
	 * Array parameter for the next call of save
	 *
	 * @var array
	 */
	protected $prmVar;

	/**
	 * Array parameter for the next call of end
	 *
	 * @var array
	 */
	protected $prmOut;

	/**
	 * Saved buffer with the call of start
	 *
	 * @var string
	 */
	private $obSave;

	/**
	 * Try to get a variable cached. If not found, information will be stored
	 * and used with the next call from save.
	 * The cache id is made with 5 kinds :
	 *  - the $id passed to the function
	 *  - the get, post, session or cookie variable if set
	 *  - the class and function name where the cache is call
	 *  - the tags if set
	 *  - 'get' is added (to differenciate from output caching)
	 *
	 * @param mixed $value the variable where the content must be placed
	 * @param array $prm Parameter for the cached variable:
	 *  - int ttl: Time to live, in minutes, 0 for eternal (default: 60)
	 *  - string id: Cache id (required)
	 *  - array tags: Optionnal tags for the id
	 *  - array request: Array for build the request ID (@see cache::idRequest)
	 *  - bool serialize: True if need to serialize the content (default: true)
	 * @return bool True if cache found and content in $value
	 * @see save
	 */
	public function get(&$value, array $prm) {
		if ($this->isEnabled() &&
			config::initTab($prm, array(
					'ttl'=>$this->cfg->ttl,
					'id'=>null,
					'tags'=>$this->cfg->tags,
					'request'=>$this->cfg->request,
					'serialize'=>$this->cfg->serialize
				))) {
			$prm['value'] = &$value;
			$prm['file'] = $this->file(array_merge($prm,
							array('callFrom'=>debug::callFrom(1),'type'=>'get')));
			$this->prmVar = $prm;
			if (file::exists($prm['file']) &&
					($prm['ttl'] == 0 || file::date($prm['file']) + $prm['ttl'] * 60 > time())) {
				if ($prm['serialize'])
					$value = unserialize(file::read($prm['file']));
				else
					eval('$value = '.file::read($prm['file']).';');
				return true;
			}
		}
		return false;
	}

	/**
	 * Save a variable with the setting passed by the last call from get
	 *
	 * @param mixed $value The variable to cache
	 * @return bool True if success
	 * @see get
	 */
	public function save() {
		$ret = null;
		if (!empty($this->prmVar))
			if ($this->prmVar['serialize'])
				$ret = file::write($this->prmVar['file'], serialize($this->prmVar['value']));
			else
				$ret = file::write($this->prmVar['file'], var_export($this->prmVar['value'], true));
		$this->prmVar = null;
		return $ret;
	}

	/**
	 * Try to get an output cached. If not found, information will be stored
	 * and used with the next call from end.
	 * The cache id is made with 5 kinds :
	 *  - the $id passed to the function
	 *  - the get, post, session or cookie variable if set
	 *  - the class and function name where the cache is call
	 *  - the tags if set
	 *  - 'cache' is added (to differenciate from variable caching)
	 *
	 * @param array $prm Parameter for the cached variable:
	 *  - int ttl: Time to live, in minutes, 0 for eternal (default: 60)
	 *  - string id: Cache id (required)
	 *  - array tags: Optionnal tags for the id
	 *  - array request: Array for build the request ID (@see cache::idRequest)
	 * @return bool True if cache found and content printed
	 * @see end
	 */
	public function start(array $prm) {
		if ($this->isEnabled() && config::initTab($prm, array(
				'ttl'=>$this->cfg->ttl,
				'id'=>null,
				'tags'=>$this->cfg->tags,
				'request'=>$this->cfg->request
			))) {
			$prm['file'] = $this->file(array_merge($prm,
							array('callFrom'=>debug::callFrom(2),'type'=>'cache')));
			if (file::exists($prm['file']) &&
				($prm['ttl'] == 0 ||
					file::date($prm['file']) + $prm['ttl'] * 60 > time())) {
				echo file::read($prm['file']);
				return true;
			} else {
				$this->obSave = '';
				/*
				if (ob_get_length()) {
					$this->obSave = ob_get_contents();
					ob_end_clean();
				}
				// */
				ob_start();
				$this->prmOut = $prm;
			}
		}
		return false;
	}

	/**
	 * Save the output with the setting passed by the last call from start,
	 * and show it
	 *
	 * @return bool True if store cache success
	 * @see start
	 */
	public function end() {
		$ret = null;
		if (!empty($this->prmOut)) {
			$content = ob_get_contents();
			ob_end_clean();
			if ($this->obSave) {
				echo $this->obSave.$content;
				ob_start();
			} else
				echo $content;
			$ret = file::write($this->prmOut['file'], $content);
		}
		$this->prmOut = null;
		return $ret;

	}

	/**
	 * Delete cached value. You cand define what you want.
	 * If you define nothing, all the cache will be deleted.
	 *
	 * @param array $prm Parameter for the cached variable to deleted:
	 *  - string class: ClassName which created the cache (optionnal)
	 *  - string func: function which created the cache (optionnal)
	 *  - string type: Cache type, could be 'get' or 'start' (optionnal)
	 *  - string id: Cache id (optionnal)
	 *  - array tags: Tags for the id (optionnal)
	 * @return int|bool Number of cache deleted or false
	 * @see get, start
	 */
	public function delete(array $prm = array()) {
		if (config::initTab($prm, array(
				'class'=>'*',
				'func'=>'*',
				'type'=>'*',
				'id'=>'*',
				'tags'=>false,
				'request'=>array('uri'=>false,'meth'=>array())
			))) {
			$file = $this->file(array_merge($prm, array('callFrom'=>$prm['class'].'-'.$prm['func'])));
			$file{strlen($file)-1} = '*';
			if (!empty($prm['tags'])) {
				for($i = 0; $i<count($prm['tags']); $i++) {
					$file = str_replace(','.$prm['tags'][$i].',', '*,'.$prm['tags'][$i].',', $file);
				}
			}
			$files = glob($file);
			$nb = 0;
			foreach($files as $f)
				if (file::delete($f))
					$nb++;
			return $nb;
		}
		return 0;
	}

	/**
	 * Indicate if a cache exists
	 *
	 * @param array $prm Parameter for the cached variable:
	 *  - string type: Cache type, must be 'get' or 'start' (required)
	 *  - string id: Cache id (required)
	 *  - array tags: Optionnal tags for the id
	 *  - array request: Array for build the request ID (@see cache::idRequest)
	 *  - bool serialize: True if need to serialize the content (default: true)
	 * @return bool True if it exists
	 * @see get, start
	 */
	public function exists(array $prm) {
		return file::exists($this->file($prm));
	}


	/**
	 * Return the cache file path
	 * The cache id is made with 5 kinds :
	 *  - the $id passed to the function
	 *  - the get, post, session or cookie variable if set
	 *  - the class and function name where the cache is call
	 *  - the tags if set
	 *  - 'cache' or 'get' is added (to differenciate output and variable caching)
	 *
	 * @param array $prm Parameter for the cached file:
	 *  - string callFrom: where the cached isrequested (format: className-Function) (required)
	 *  - string type: Cache type, must be 'get' or 'start' (required)
	 *  - string id: Cache id (required)
	 *  - array tags: Optionnal tags for the id
	 *  - array request: Array for build the request ID (@see cache::idRequest)
	 * @return string The cache file path
	 */
	private function file(array $prm) {
		$prm = array_merge(array(
			'callFrom'=>null,
			'type'=>null,
			'id'=>null,
			'tags'=>null,
			'tagsS'=>null
		), $prm);
		if (!empty($prm['tags'])) {
			sort($prm['tags']);
			$prm['tagsS'] = ',_,'.implode(',_,', $prm['tags']).',_,';
		}
		$fileA = array(
				NYRONAME,
				NYROENV,
				$prm['callFrom'],
				$prm['type'],
				$prm['id'],
				$prm['tagsS'],
				cache::idRequest($prm['request'])
			);
		return $this->cfg->path.implode('^', $fileA).'.cache';
	}
}
