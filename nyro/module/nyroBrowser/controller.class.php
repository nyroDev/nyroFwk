<?php
class module_nyroBrowser_controller extends module_abstract {
	
	protected $myCfg;
	protected $dir;
	protected $type;
	protected $config;
	protected $uri;
	
	protected function prepare() {
		$htVars = http_vars::getInstance();
		$this->type = $htVars->post('type', $htVars->get('type'));
		$this->config = $htVars->post('config', $htVars->get('config', 'default'));
		
		$this->myCfg = $this->cfg->default;
		if ($this->config != 'default' && is_array($this->cfg->get($this->config)))
			factory::mergeCfg($this->myCfg, $this->cfg->get($this->config));
		
		$this->dir = $this->myCfg['dir'];
		if ($this->myCfg['subdir'])
			$this->dir.= DS.$this->myCfg['subdir'];
		
		$this->dir.= DS.$this->type;
		
		$resp = response::getInstance();
		/* @var $resp response_http_html */
		$resp->setLayout($this->myCfg['layout']);
		$resp->setTitle($this->myCfg['title']);
		$resp->initIncFiles(false);
		foreach($this->myCfg['incFiles'] as $ic)
			$resp->add($ic);
		
		$this->uri = request::uriDef(array('module')).'?'.session::getInstance()->getSessIdForce().'='.urlencode(session_id()).'&type='.$this->type.'&config='.$this->config.'&';
	}
	
	protected function execIndex(array $prm = array()) {
		$this->prepare();
		
		$pattern = FILESROOT.$this->dir.DS.'*';
		$search = http_vars::getInstance()->get('search');
		if ($search) {
			$pattern.= $search.'*';
			$this->uri.= 'search='.$search.'&';
		}
		
		$delete = http_vars::getInstance()->get('delete');
		if ($delete) {
			$file = FILESROOT.urldecode($delete);
			if (file::exists($file)) {
				file::delete($file);
				file::multipleDelete(substr($file, 0, -strlen(file::getExt($file))-1).'_*');
				response::getInstance()->redirect($this->uri);
			}
		}
		
		$form = $this->getForm();
		
		if (request::isPost()) {
			$form->refill();
			if ($form->isValid())
				response::getInstance()->sendText('ok');
		}
		
		$files = array();
		foreach(file::search($pattern) as $f) {
			if (strpos($f, 'nyroBrowserThumb') === false) {
				$name = basename($f);
				if ($this->type == 'image' && strlen($name) > 15)
					$name = substr($name, 0, 15).'...'.file::getExt($f);
				$files[] = array(
					$f,
					request::uploadedUri(str_replace(FILESROOT, '', $f), $this->myCfg['uploadedUri']),
					$name,
					file::humanSize($f),
					utils::formatDate(filemtime($f)),
					$this->uri.'delete='.urlencode(str_replace(FILESROOT, '', $f)).'&'
				);
			}
		}
		
		$this->setViewVars(array(
			'uri'=>$this->uri,
			'form'=>$form,
			'config'=>$this->config,
			'type'=>$this->type,
			'files'=>$files,
			'searchButton'=>$this->myCfg['search'],
			'search'=>$search,
			'imgHelper'=>$this->myCfg['imgHelper'],
			'filesTitle'=>$this->myCfg['filesTitle'],
			'noFiles'=>$this->myCfg['noFiles'],
			'name'=>$this->myCfg['name'],
			'size'=>$this->myCfg['size'],
			'date'=>$this->myCfg['date'],
			'delete'=>$this->myCfg['delete'],
		));
	}
	
	protected function getForm() {
		$form = factory::get('form', array_merge(array(
			'sectionName'=>$this->myCfg['formName'],
			'action'=>$this->uri
		), $this->myCfg['formCfg']));
		
		$form->add('file', array(
			'name'=>'file',
			'subdir'=>$this->dir,
			'helper'=>$this->myCfg['helper'][$this->type]['name'],
			'helperPrm'=>$this->myCfg['helper'][$this->type]['prm'],
			'uploadify'=>array('scriptData'=>array(
				'type'=>$this->type,
				'config'=>$this->config,
			)),
		));
		$form->get('file')->uploadify(array(
			'script'=>$this->uri,
			'onAllComplete'=>'function() {window.location.href = "'.$this->uri.'";}'
		));
		
		return $form;
	}
	
}