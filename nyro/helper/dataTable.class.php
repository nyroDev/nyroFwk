<?php

class helper_dataTable extends object {

	/**
	 * Table object
	 *
	 * @var db_table
	 */
	protected $table;

	/**
	 * Session object
	 *
	 * @var session_abstract
	 */
	protected $session;

	/**
	 * Nb of results
	 *
	 * @var int
	 */
	protected $count;

	/**
	 * SortBy requested
	 *
	 * @var string
	 */
	protected $sortBy;

	/**
	 * Cached data
	 *
	 * @var array
	 */
	protected $data;
	
	protected function afterInit() {
		$this->table = $this->cfg->table;

		if (!is_array($this->cfg->query))
			$this->cfg->query = array();

		if (empty($this->cfg->module))
			$this->cfg->module = utils::getModuleName(debug::callFrom(6, null));

		$this->session = session::getInstance(array(
			'nameSpace'=>'dataTable_'.$this->cfg->name
		));

		$paramFromRequest = array('page', 'sortBy', 'sortDir');
		$paramA = request::get('paramA');
		foreach($paramFromRequest as $pfr) {
			if ($this->session->check($pfr))
				$this->cfg->set($pfr, $this->session->get($pfr));

			if (array_key_exists($pfr.$this->cfg->nameParam, $paramA)) {
				$val = $paramA[$pfr.$this->cfg->nameParam];
				$this->session->set(array(
					'name'=>$pfr,
					'val'=>$val
				));
				$this->cfg->set($pfr, $val);
			}
		}

		if ($this->cfg->check('sortBy')) {
			if ($this->table->isRelated($this->cfg->sortBy)) {
				$related = $this->table->getRelated($this->cfg->sortBy);
				$tmp = array();

				$fields = array_filter(explode(',', $related['fk2']['link']['fields']));
				$tableName = $related['fk2']['link']['table'];
				foreach($fields as $f)
					$tmp[] = $tableName.'.'.$f;

				$fields = array_filter(explode(',', $related['fk2']['link']['i18nFields']));
				$tableName.= db::getCfg('i18n');
				foreach($fields as $f)
					$tmp[] = $tableName.'.'.$f;
				$this->sortBy = implode(', ', $tmp);
			} else if ($this->cfg->sortBy)
				$this->sortBy = $this->table->getName().'.'.$this->cfg->sortBy;
		}

		$this->count = $this->table->count($this->getQuery());
	}

	/**
	 * Get the data for the current page
	 *
	 * @return db_rowset
	 */
	public function getData() {
		if (is_null($this->data))
			$this->data = $this->table->select(array_merge(
				$this->getQuery(),
				array(
					'start'=>($this->cfg->page-1)*$this->cfg->nbPerPage,
					'nb'=>$this->cfg->nbPerPage,
					'order'=>$this->sortBy? $this->sortBy.' '.$this->cfg->sortDir : ''
				)));
		return $this->data;
	}

	/**
	 * Get the number of pages
	 *
	 * @return int
	 */
	public function getNbPage() {
		return ceil($this->count / $this->cfg->nbPerPage);
	}

	/**
	 * Get the query parameter
	 *
	 * @return string|array
	 */
	public function getQuery() {
		return $this->cfg->query;
	}

	/**
	 * Create the data Table out
	 *
	 * @param string $type Out type
	 * @return string
	 */
	public function to($type) {
		$tpl = factory::get('tpl', array(
			'module'=>$this->cfg->module,
			'action'=>$this->cfg->name,
			'default'=>'dataTable',
			'cache'=>$this->cfg->cache,
			'layout'=>false,
		));

		$data = $this->getData();

		if (count($data)) {
			if (empty($this->cfg->fields)) {
				$headersT = $data->getFields('flatReal');
				if ($keyRelated = array_search('related', $headersT))
					unset($headersT[$keyRelated]);
				foreach($this->table->getI18nFields() as $f)
					$headersT[] = db::getCfg('i18n').$f['name'];
			} else {
				$headersT = $this->cfg->fields;
				if (!in_array($this->table->getIdent(), $headersT))
					array_unshift($headersT, $this->table->getIdent());
			}
			$headers = array();
			$prmReplaceSortBy = '[sortBy]';
			$prmReplaceSortDir = '[sortDir]';
			$paramUrlA = request::get('paramA');
			unset($paramUrlA['page'.$this->cfg->nameParam]);
			$paramUrlA['sortBy'.$this->cfg->nameParam] = $prmReplaceSortBy;
			$paramUrlA['sortDir'.$this->cfg->nameParam] = $prmReplaceSortDir;
			$paramUrlA['page'.$this->cfg->nameParam] = 1;
			$tmpSortLink = request::uriDef(array('paramA'=>$paramUrlA));
			foreach ($headersT as $k=>$h) {
				$typeField = $this->table->getField($h, 'type');
				if ($typeField == 'file' && is_array($tmp = $this->table->getField($h, 'comment')))
					$typeField = $tmp[0];
				$headers[$k] = array(
					'label' => $this->table->getLabel($h),
					'name' => $h,
					'url'=>str_replace(
						array($prmReplaceSortBy, $prmReplaceSortDir),
						array(
							db::isI18nName($h)? $this->table->getI18nTable()->getName().'_'.db::unI18nName($h): $h,
							$this->cfg->sortBy == $h && $this->cfg->sortDir == 'asc'? 'desc' : 'asc'
						),
						$tmpSortLink),
					'type'=>$typeField
				);
			}

			$actions = null;
			if (is_array($this->cfg->actions) && !empty($this->cfg->actions)) {
				$actions = array();
				array_walk($headersT, create_function('&$h', '$h = "[".$h."]";'));
				$i = 0;
				foreach($data as $d) {
					$tmp = $this->getActions($d->getId());
					foreach($tmp as &$t) {
						$t = str_replace($headersT, $d->getValues('flat'), $t);
					}
					$actions[$i] = $tmp;
					$i++;
				}
				if (!empty($actions) && $this->cfg->actionsConfirmDelete) {
					response::getInstance()->addJs('actionsConfirmDelete');
				}
			}

			$actionsAlt = $this->cfg->actionsAlt;
			if (is_array($actionsAlt)) {
				foreach(array_keys($actions) as $k=>$v)
					$actionsAlt[$k] = ucfirst($k);
			}

			if ($this->cfg->sortBy) {
				$paramUrlA['sortBy'.$this->cfg->nameParam] = $this->cfg->sortBy;
				$paramUrlA['sortDir'.$this->cfg->nameParam] = $this->cfg->sortDir;
			} else {
				unset($paramUrlA['sortBy'.$this->cfg->nameParam]);
				unset($paramUrlA['sortDir'.$this->cfg->nameParam]);
			}

			$nbPage = $this->getNbPage();

			$pageLinks = array();
			$prmReplace = $this->cfg->pageLinkReplace;
			if (!$this->cfg->pageLinkTpl) {
				$paramUrlA['page'.$this->cfg->nameParam] = $prmReplace;
				$tmpPageLink = request::uriDef(array('paramA'=>$paramUrlA));
			} else
				$tmpPageLink = $this->cfg->pageLinkTpl;
			for($i = 1; $i<=$nbPage; $i++)
				$pageLinks[$i] = str_replace($prmReplace, $i, $tmpPageLink);
			if ($this->cfg->pageLinkTpl1)
				$pageLinks[1] = $this->cfg->pageLinkTpl1;

			$tpl->setA(array(
				'headers'=>$headers,
				'list'=>$data,
				'nbPage'=>$nbPage,
				'currentPage'=>$this->cfg->page,
				'pageLinks'=>$pageLinks,
				'actions'=>$actions,
				'actionsAlt'=>$actionsAlt,
				'iconType'=>$this->cfg->iconType,
			));
		} else {
			// No data
			$tpl->set('noData', utils::htmlOut($this->cfg->noData));
		}

		return $tpl->fetch(array('tplExt'=>$type));
	}

	/**
	 * Get the actions for a specific ID
	 *
	 * @param mixed $id The Row ID
	 * @return array The filtered actions (from cfg->actions)
	 */
	protected function getActions($id) {
		$tmp = $this->cfg->actions;
		if ($val = $this->cfg->getInArray('actionsAllowed', 'id'.$id))
			$tmp = array_intersect_key($tmp, array_flip($val));
		return $tmp;
	}

	/**
	 * Create the data Table HTML out
	 *
	 * @return string
	 */
	public function toHtml() {
		return $this->to('html');
	}

	/**
	 * Create the data Table out regarding the request out
	 *
	 * @return string
	 */
	public function __toString() {
		return $this->to(request::get('out'));
	}

}