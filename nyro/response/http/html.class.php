<?php
/**
 * @author Cedric Nirousset <cedric@nyrodev.com>
 * @version 0.2
 * @package nyro
 */
/**
 * HTML Response
 * Provide functions to manage the head parts
 */
class response_http_html extends response_http {

	/**
	 * Include Files (CSS or Js)
	 *
	 * @var array
	 */
	protected $incFiles;

	/**
	 * Blocks to write in the head (css, javascript)
	 *
	 * @var array
	 */
	protected $blocks = array();

	/**
	 * Blocks to be execute once jQuery is loaded
	 *
	 * @var array
	 */
	protected $blocksJquery = array();

	/**
	 * Used to store the TinyMce Gzip Option
	 *
	 * @var array
	 */
	protected $tinyMceGzipOpt = null;

	protected function afterInit() {
		parent::afterInit();
		$this->incFiles = array(
			'js'=>array('nyro'=>array(), 'web'=>array(), 'nyroLast'=>array()),
			'css'=>array('nyro'=>array(), 'web'=>array(), 'nyroLast'=>array())
		);
		if (!empty($this->cfg->incFiles) && is_array($this->cfg->incFiles)) {
			foreach($this->cfg->incFiles as $ic)
				$this->add($ic);
		}
	}

	/**
	 * Get the title
	 *
	 * @return string|null
	 */
	public function getTitle() {
		return $this->getMeta('title');
	}

	/**
	 * Set the title
	 *
	 * @param string $title
	 */
	public function setTitle($title) {
		$this->setMeta('title', $title);
	}

	/**
	 * Add a string before the acual title, with a spearator if needed
	 *
	 * @param string $title
	 * @param string $sep
	 */
	public function addTitleBefore($title, $sep=', ') {
		$this->setMetaBefore('title', $title, $sep);
	}

	/**
	 * Add a string after the acual title, with a spearator if needed
	 *
	 * @param string $title
	 * @param string $sep
	 */
	public function addTitleAfter($title, $sep=', ') {
		$this->setMetaAfter('title', $title, $sep);
	}

	/**
	 * Get a meta content
	 *
	 * @param string $name Meta name
	 * @return string|null
	 */
	public function getMeta($name) {
		return $this->cfg->getInArray('meta', $name);
	}

	/**
	 * Set a meta content
	 *
	 * @param string $name Meta name
	 * @param string $value Meta content
	 */
	public function setMeta($name, $value) {
		$this->cfg->setInArray('meta', $name, $value);
	}

	/**
	 * Add a string before the acual meta, with a spearator if needed
	 *
	 * @param string $name Meta Name
	 * @param string $value Meta content
	 * @param string $sep
	 */
	public function setMetaBefore($name, $value, $sep=', ') {
		$old = $this->getMeta($name);
		$value.= $old? $sep.$old : null;
		$this->setMeta($name, $value);
	}

	/**
	 * Add a string after the acual meta, with a spearator if needed
	 *
	 * @param string $name Meta Name
	 * @param string $value Meta content
	 * @param string $sep
	 */
	public function setMetaAfter($name, $value, $sep=', ') {
		$old = $this->getMeta($name);
		$value = $old? $old.$sep.$value : $value;
		$this->setMeta($name, $value);
	}

	/**
	 * Initialize the blocks array for the requested type
	 *
	 * @param string $type
	 */
	protected function initBlocks($type) {
		if (!array_key_exists($type, $this->blocks))
			$this->blocks[$type] = array();
	}

	/**
	 * Add an include file
	 *
	 * @param array $prm Same parameter as addCss or addJs, with adding:
	 *  - string type File type (js or css) (required)
	 * @return bool True if addedor already added, False if not found
	 * @throws nExecption if type or file not  provided
	 * @see addJs, addCss
	 */
	public function add(array $prm) {
		if (config::initTab($prm, array(
			'type'=>null,
			'file'=>null,
			'dir'=>'nyro',
			'media'=>'screen',
			'condIE'=>false,
			'verifExists'=>true
		))) {
			foreach($this->getDepend($prm['file'], $prm['type']) as $d) {
				if (is_array($d))
					$this->add(array_merge($prm, $d));
				else
					$this->add(array_merge($prm, array('file'=>$d)));
			}

            foreach($this->cfg->getInArray($prm['type'], 'alias') as $k=>$a) {
                if (strtolower($prm['file']) == strtolower($k))
                    $prm['file'] = $a;
            }

			$prmType = $this->cfg->get($prm['type']);

			$locDir = $prm['dir'];
			if (!array_key_exists($locDir, $this->incFiles[$prm['type']]))
				$locDir = 'nyro';

			$fileExt = $prm['file'].'.'.$prm['type'];

			if ($prm['verifExists'])
				$fileExists = $locDir == 'web'
					?file::webExists($prmType['dirWeb'].DS.$fileExt)
					:file::nyroExists(array(
								'name'=>'module_'.nyro::getCfg()->compressModule.'_'.$prm['type'].'_'.$prm['file'],
								'type'=>'tpl',
								'tplExt'=>$prm['type']
							));
			else
				$fileExists = true;

			if ($fileExists) {
				$this->incFiles[$prm['type']][$locDir][$prm['file']] = $prm;
				if ($prm['type'] == 'css') {
					$c = file::read($fileExists);
					preg_match_all('`@import (url\()?"(.+).css"\)?;`', $c, $matches);
					if (!empty($matches[2])) {
						$prefix = substr($prm['file'], 0, strpos($prm['file'], '_')+1);
						foreach($matches[2	] as $m)
							$this->add(array_merge($prm, array('file'=>$prefix.$m)));
					}
				}
				return true;
			}
			return false;
		} else
			throw new nException('reponse::add: parameters file and/or type not provied');
	}

	/**
	 * Get the included files
	 *
	 * @return array
	 */
	public function getIncFiles() {
		return $this->incFiles;
	}

	/**
	 * Get the dependancies for a file
	 *
	 * @param string $file File name
	 * @param string $type File type (js or css)
	 * @return array
	 */
	public function getDepend($file, $type='js') {
		$ret = array();

		$depend = $this->cfg->getInArray($type, 'depend');
		if (is_array($depend) && array_key_exists($file, $depend)) {
			if (is_array($depend[$file]))
				$ret = $depend[$file];
			else
				$ret = array($depend[$file]);
		}

		return $ret;
	}

	/**
	 * Add an javascript file
	 *
	 * @param string|array $prm Filename or parameter for the css file. Available keys are:
	 *  - string file: filename (required)
	 *  - string dir: Where include the file (possible values: nyro, web, or nyroLast)
	 *  - bool verifExists: indicate if the file should exist to be included
	 * @return bool True if added, False if not found or already added
	 * @see add
	 */
	public function addJs($prm) {
		if (is_array($prm))
			return $this->add(array_merge($prm, array('type'=>'js')));
		else
			return $this->add(array('file'=>$prm,'type'=>'js'));
	}

	/**
	 * Add an CSS file
	 *
	 * @param array $prm Filename or parameter for the css file. Available keys are:
	 *  - string file: filename (required)
	 *  - string dir: Where include the file (possible values: nyro, web, or nyroLast)
	 *  - string media: Media attribute
	 *  - string condIE: special string to include CSS for IE
	 *  - bool verifExists: indicate if the file should exist to be included
	 * @return bool True if added or already added, False if not found
	 * @see add
	 */
	public function addCss($prm) {
		if (is_array($prm))
			return $this->add(array_merge($prm, array('type'=>'css')));
		else
			return $this->add(array('file'=>$prm,'type'=>'css'));
	}

	/**
	 * Add a block
	 *
	 * @param string $block The block
	 * @param string $type the block type (js or css)
	 * @param bool $first True if needed to be placed on the first place
	 * @return bool True if added
	 */
	public function block($block, $type='js', $first=false) {
		$this->initBlocks($type);

		if ($first)
			array_unshift($this->blocks[$type], $block);
		else
			$this->blocks[$type][] = $block;

		return true;
	}

	/**
	 * Add a javascript block
	 *
	 * @param string $block The javascript block
	 * @param bool $first True if needed to be placed on the first place
	 * @return bool True if added
	 * @see block
	 */
	public function blockJs($block, $first=false) {
		return $this->block($block, 'js', $first);
	}

	/**
	 * Add a jQuery block
	 *
	 * @param string $block The javascript block
	 * @see blockJs
	 */
	public function blockjQuery($block) {
		$this->addJs('jquery');
		$this->blocksJquery[] = $block;
	}

	/**
	 * Add a CSS block
	 *
	 * @param string $block The CSS block
	 * @param bool $first True if needed to be placed on the first place
	 * @return bool True if added
	 * @see block
	 */
	public function blockCss($block, $first=false) {
		return $this->block($block, 'css', $first);
	}

	/**
	 * Add options for the tinyMceGzip
	 *
	 * @param string $type Attribute key
	 * @param string $value Values separate by ,
	 */
	public function tinyMceGzip($type, $value) {
		if (!$this->tinyMceGzipOpt)
			$this->tinyMceGzipOpt = array();
		$val = explode(',', $value);

		if (array_key_exists($type, $this->tinyMceGzipOpt)) {
			$val = array_diff($val, $this->tinyMceGzipOpt[$type]);
			$this->tinyMceGzipOpt[$type] = array_merge($this->tinyMceGzipOpt[$type], $val);
		} else {
			$this->tinyMceGzipOpt[$type] = $val;
		}
	}

	/**
	 * Get the HTML Head part requested or all (title, meta and included files)
	 *
	 * @param string $prm The requested part (title, meta or incFiles)
	 * @param string $ln New line character
	 * @return string The requested HTML part
	 */
	public function getHtmlElt($prm='all', $ln="\n") {
		$ret = null;
		switch($prm) {
			case 'title':
				$ret.= '<title>'.utils::htmlOut($this->getMeta('title')).'</title>';
				break;
			case 'meta':
				$ret.= $this->getHtmlMeta();
				break;
			case 'css':
				$ret.= $this->getHtmlIncFiles('css', $ln).$ln
						.$this->getHtmlBlocks('css', $ln);
				break;
			case 'js':
				$ret.= $this->getHtmlIncFiles('js', $ln).$ln
						.$this->getHtmlBlocks('js', $ln);
				break;
			default:
				$ret.= $this->getHtmlElt('title').$ln
						.$this->getHtmlElt('meta').$ln
						.$this->getHtmlElt('css', $ln);
		}
		return $ret.$ln;
	}

	/**
	 * Get the HTML Meta
	 *
	 * @param string $ln New line character
	 * @return string
	 */
	protected function getHtmlMeta($ln="\n") {
		$ret = null;

		if (array_key_exists('Content-Type', $this->headers))
			$ret.= '<meta http-equiv="Content-Type" content="'.$this->headers['Content-Type'].'" />'.$ln;

		if ($this->cfg->titleInDes)
			$this->cfg->setInArray('meta', 'description',
					$this->cfg->getInarray('meta', 'title').
					$this->cfg->titleInDes.
					$this->cfg->getInarray('meta', 'description'));
		foreach($this->cfg->meta as $k=>$v)
			$ret.= '<meta name="'.$k.'" content="'.utils::htmlOut($v).'" />'.$ln;
		return $ret;
	}

	/**
	 * Get the HTML included files
	 *
	 * @param string $type Docuement type (css or js)
	 * @param string $ln New line character
	 * @return string
	 */
	protected function getHtmlIncFiles($type, $ln="\n") {
		$ret = null;
		if (array_key_exists($type, $this->incFiles)) {
			$files = array_filter($this->incFiles[$type]);
			$prm = $this->cfg->get($type);

			foreach($files as $dir=>$file) {
				if ($type == 'css') {
					$tmp = array();
					foreach($file as $f) {
						$tmp[$f['condIE']][$f['media']][] = $f['file'];
					}
					foreach($tmp as $ie=>$t) {
						if ($ie)
							$ret.= '<!--[if '.$ie.']>'.$ln;
						foreach($t as $media=>$f)
							$ret.= $this->getIncludeTagFile($type,
											$f,
											$dir,
											$media
											).$ln;
						if ($ie)
							$ret.= '<![endif]-->'.$ln;
					}
				} else {
					$tmp = array();
					foreach($file as $f) {
						$tmp[$f['condIE']][] = $f['file'];
					}
					foreach($tmp as $ie=>$f) {
						if ($ie)
							$ret.= '<!--[if '.$ie.']>'.$ln;
						$ret.= $this->getIncludeTagFile($type,
										$f,
										$dir
										).$ln;
						if ($ie)
							$ret.= '<![endif]-->'.$ln;
					}
				}
			}
		}
		return $ret;
	}

	/**
	 * Get the tag to include an external file
	 *
	 * @param string $type (css|js)
	 * @param string $url Url of the content
	 * @param string $media Media info for css
	 * @return string The tag
	 */
	public function getIncludeTagFile($type, $files, $dir='nyro', $media='screen') {
		$prm = $this->cfg->get($type);
		$url = $dir == 'web'
					? request::get('path').$prm['dirWeb']
					: request::getPathControllerUri().$prm['dirUriNyro'];
		$url.= '/'.(is_array($files)? implode(request::getCfg('sepParam'), $files) : $files);
		$url.= '.'.$prm['ext'];
		return sprintf($prm['include'], $url, $media);
	}

	/**
	 * Get the HTML blocks
	 *
	 * @param string $type Docuement type (css or js)
	 * @param string $ln New line character
	 * @return string
	 */
	protected function getHtmlBlocks($type, $ln="\n") {
		$ret = null;

		if ($type == 'js' && $this->tinyMceGzipOpt) {
			if (array_key_exists('plugins', $this->tinyMceGzipOpt))
				$this->tinyMceGzipOpt['plugins'] = implode(',', $this->tinyMceGzipOpt['plugins']);
			if (array_key_exists('themes', $this->tinyMceGzipOpt))
				$this->tinyMceGzipOpt['themes'] = implode(',', $this->tinyMceGzipOpt['themes']);
			if (array_key_exists('languages', $this->tinyMceGzipOpt))
				$this->tinyMceGzipOpt['languages'] = implode(',', $this->tinyMceGzipOpt['languages']);
			$this->tinyMceGzipOpt['disk_cache'] = 'true';
			$this->tinyMceGzipOpt['debug'] = 'false';
			$ret.= sprintf($this->cfg->getInArray('js', 'block'), 'tinyMCE_GZ.init('.json_encode($this->tinyMceGzipOpt).');').$ln;
		}

		if ($type == 'js' && !empty($this->blocksJquery))
			$this->blockJs('jQuery(function($) {'.implode("\n", $this->blocksJquery).'});');

		if (array_key_exists($type, $this->blocks)) {
			$prm = $this->cfg->get($type);
			$ret.= sprintf($prm['block'], implode($ln, $this->blocks[$type]));
		}

		return $ret;
	}

	
	public function send($headerOnly=false) {
		$ret = parent::send($headerOnly);
		return DEV ? str_replace('</body>', debug::debugger().'</body>', $ret) : $ret;
	}
}
