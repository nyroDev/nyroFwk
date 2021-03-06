<?php
/**
 * @author Cédric Nirousset <cedric@nyrodev.com>
 * @version 0.2
 * @package nyroFwk
 */
/**
 * Helper to show db data with pagination
 */
class helper_dataTable extends nObject {

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

		if ($this->cfg->useSession)
			$this->session = session::getInstance(array(
				'nameSpace'=>'dataTable_'.($this->cfg->sessionName ? $this->cfg->sessionName : $this->cfg->name)
			));

		$paramFromRequest = array('page', 'sortBy', 'sortDir');
		$paramA = request::get('paramA');
		foreach($paramFromRequest as $pfr) {
			if ($this->cfg->useSession && $this->session->check($pfr))
				$this->cfg->set($pfr, $this->session->get($pfr));

			if (array_key_exists($pfr.$this->cfg->nameParam, $paramA)) {
				$val = $paramA[$pfr.$this->cfg->nameParam];
				if ($this->cfg->useSession)
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
				if (!$this->table->getCfg()->autoJoin) {
					$f = $related['tableObj']->getRawName();
					$query = $this->cfg->query;
					if (!isset($query['join']))
						$query['join'] = array();
					$query['join'][] = array(
						'table'=>$f,
						'dir'=>'left outer',
						'on'=>$this->table->getRawName().'.'.$related['fk1']['link']['ident'].'='.$f.'.'.$related['fk1']['name']
					);
					$query['join'][] = array(
						'table'=>$related['table'],
						'dir'=>'left outer',
						'on'=>$f.'.'.$related['fk2']['name'].'='.$related['table'].'.'.$related['fk2']['link']['ident']
					);
					
					if ($related['fk2']['link']['i18nFields']) {
						$i18nTableName = $related['table'].db::getCfg('i18n');
						$i18nTable = db::get('table', $i18nTableName, array('db'=>$this->table->getDb()));
						$primary = $i18nTable->getPrimary();
						$query['join'][] = array(
							'table'=>$i18nTableName,
							'dir'=>'left outer',
							'on'=>$f.'.'.$related['fk2']['name'].'='.$i18nTableName.'.'.$primary[0].
								' AND '.$i18nTableName.'.'.$primary[1].'="'.request::get('lang').'"'
						);
					}
					
					$query['group'] = $this->table->getRawName().'.'.$this->table->getIdent();
					$this->cfg->query = $query;
				}
			} else if ($this->table->isLinked($this->cfg->sortBy)) {
				$linked = $this->table->getLinked($this->cfg->sortBy);
				$tmpSort = array();
				foreach(explode(',', trim($linked['fields'].','.$linked['i18nFields'], ',')) as $tmp)
					$tmpSort[] = $linked['field'].'.'.$tmp;
				$this->sortBy = implode(', ', $tmpSort);
				if (!$this->table->getCfg()->autoJoin) {
					$query = $this->cfg->query;
					if (!isset($query['join']))
						$query['join'] = array();
					$alias = $linked['field'];
					if ($linked['i18nFields']) {
						$alias1 = $alias.'1';
						$query['join'][] = array(
							'table'=>$linked['table'],
							'alias'=>$alias1,
							'dir'=>'left outer',
							'on'=>$this->table->getRawName().'.'.$linked['field'].'='.$alias1.'.'.$linked['ident']
						);
						$i18nTableName = $linked['table'].db::getCfg('i18n');
						$i18nTable = db::get('table', $i18nTableName, array('db'=>$this->table->getDb()));
						$primary = $i18nTable->getPrimary();
						$query['join'][] = array(
							'table'=>$i18nTableName,
							'alias'=>$alias,
							'dir'=>'left outer',
							'on'=>$alias1.'.'.$linked['ident'].'='.$alias.'.'.$primary[0].
								' AND '.$alias.'.'.$primary[1].'="'.request::get('lang').'"'
						);
					} else {
						$query['join'][] = array(
							'table'=>$linked['table'],
							'alias'=>$alias,
							'dir'=>'left outer',
							'on'=>$this->table->getRawName().'.'.$linked['field'].'='.$alias.'.'.$linked['ident']
						);
					}
					$this->cfg->query = $query;
				}
			} else if ($this->cfg->sortBy) {
				if (strpos($this->cfg->sortBy, $this->table->getName()) !== false || strpos($this->cfg->sortBy, ".") !== false)
					$this->sortBy = $this->cfg->sortBy;
				else
					$this->sortBy = $this->table->getName().'.'.$this->cfg->sortBy;
			}
		}
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
					'order'=>$this->sortBy ? $this->sortBy.' '.$this->cfg->sortDir : ''
				)));
		return $this->data;
	}

	/**
	 * Get the number of results
	 *
	 * @return int
	 */
	public function getCount() {
		if (is_null($this->count))
			$this->count = count($this->table->select($this->getQuery()));
		return $this->count;
	}

	/**
	 * Get the number of pages
	 *
	 * @return int
	 */
	public function getNbPage() {
		$nbPage = ceil($this->getCount() / $this->cfg->nbPerPage);
		if ($this->cfg->nbPageMax && $nbPage > $this->cfg->nbPageMax)
			$nbPage = $this->cfg->nbPageMax;
		return $nbPage;
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
				if ($this->cfg->addIdentField && !in_array($this->table->getIdent(), $headersT))
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
				if ($typeField == 'file' && is_array($tmp = $this->table->getField($h, 'comment')) && array_key_exists(0, $tmp))
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
			$actionsAlt = null;
			$actionsImg = null;
			if (is_array($this->cfg->actions) && !empty($this->cfg->actions)) {
				$actions = array();
				if (!$this->cfg->addIdentField)
					array_unshift($headersT, $this->table->getIdent());
				array_walk($headersT, create_function('&$h', '$h = "[".$h."]";'));
				$dataK = null;
				$i = 0;
				foreach($data as $d) {
					$tmp = $this->getActions($d);
					$tmpVals = $d->getValues('flatNoRelated');
					$vals = array();
					foreach($headersT as $k=>$v) {
						$v = substr($v, 1, -1);
						$vals[$k] = array_key_exists($v, $tmpVals) ? $tmpVals[$v] : null;
					}

					$curData = $d->getValues('data');
					unset($curData['related']);
					unset($curData['linked']);
					if (is_null($dataK)) {
						$dataK = array_keys($curData);
						array_walk($dataK, create_function('&$h', '$h = "[".$h."]";'));
					}
					foreach($tmp as &$t)
						$t = str_replace($dataK, $curData, $t);

					$actions[$i] = $tmp;
					$i++;
				}
				if (!empty($actions) && $this->cfg->actionsConfirmDelete)
					response::getInstance()->addJs('actionsConfirmDelete');

				$actionsKey = array_keys($this->cfg->actions);
				$actionsAlt = $this->cfg->actionsAlt;
				if (!is_array($actionsAlt) || count($actionsAlt) < count($actionsKey)) {
					foreach($actionsKey as $v)
						if (!array_key_exists($v, $actionsAlt))
							$actionsAlt[$v] = ucfirst($v);
				}

				$actionsImg = $this->cfg->actionsImg;
				foreach($actionsKey as $v) {
					if (!array_key_exists($v, $actionsImg))
						$actionsImg[$v] = utils::getIcon(array(
									'name'=>$v,
									'attr'=>array('title'=>$actionsAlt[$v]),
									'alt'=>$actionsAlt[$v],
									'type'=>$this->cfg->iconType,
								));
				}
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

			$hasMultiple = count($this->cfg->multiple) > 0;
			if ($hasMultiple && $this->cfg->addCheckAllJs)
				response::getInstance()->addJs('checkAll');
			
			$tpl->setA(array_merge(array(
				'headers'=>$headers,
				'list'=>$data,
				'nbPage'=>$nbPage,
				'currentPage'=>$this->cfg->page,
				'pageLinks'=>$pageLinks,
				'actions'=>$actions,
				'actionsImg'=>$actionsImg,
				'actionsAlt'=>$actionsAlt,
				'iconType'=>$this->cfg->iconType,
				'tblName'=>$this->table->getName(),
				'sortBy'=>$this->cfg->sortBy,
				'sortByReal'=>$this->sortBy,
				'sortDir'=>$this->cfg->sortDir,
				'hasMultiple'=>$hasMultiple,
				'multipleLabel'=>$this->cfg->multipleLabel,
				'multipleSubmit'=>$this->cfg->multipleSubmit,
				'multipleAction'=>$this->cfg->multipleAction,
				'multipleIdent'=>$this->table->getIdent(),
				'multiple'=>$this->cfg->multiple
			), $this->cfg->tplVars));
		} else {
			// No data
			if ($this->cfg->page > 1) {
				$newPage = $this->cfg->page-1;
				$uri = null;
				if ($newPage == 1 && $this->cfg->pageLinkTpl1)
					$uri = $this->cfg->pageLinkTpl1;
				if (!$uri) {
					$prmReplace = $this->cfg->pageLinkReplace;
					if (!$this->cfg->pageLinkTpl) {
						$paramUrlA = request::get('paramA');
						unset($paramUrlA['page'.$this->cfg->nameParam]);
						$prmReplaceSortBy = '[sortBy]';
						$prmReplaceSortDir = '[sortDir]';
						$paramUrlA['sortBy'.$this->cfg->nameParam] = $prmReplaceSortBy;
						$paramUrlA['sortDir'.$this->cfg->nameParam] = $prmReplaceSortDir;
						$paramUrlA['page'.$this->cfg->nameParam] = 1;

						if ($this->cfg->sortBy) {
							$paramUrlA['sortBy'.$this->cfg->nameParam] = $this->cfg->sortBy;
							$paramUrlA['sortDir'.$this->cfg->nameParam] = $this->cfg->sortDir;
						} else {
							unset($paramUrlA['sortBy'.$this->cfg->nameParam]);
							unset($paramUrlA['sortDir'.$this->cfg->nameParam]);
						}
						$paramUrlA['page'.$this->cfg->nameParam] = $prmReplace;
						$tmpPageLink = request::uriDef(array('paramA'=>$paramUrlA));
					} else
						$tmpPageLink = $this->cfg->pageLinkTpl;
					$uri = str_replace($prmReplace, $newPage, $tmpPageLink);
				}
				response::getInstance()->redirect($uri);
			}
			$tpl->set('noData', utils::htmlOut($this->cfg->noData));
			$tpl->set('list', null);
			$tpl->setA($this->cfg->tplVars);
		}
		return $tpl->fetch(array('tplExt'=>$type));
	}

	/**
	 * Get the actions for a specific ID
	 *
	 * @param db_row $row The Row
	 * @return array The filtered actions (from cfg->actions)
	 */
	protected function getActions($row) {
		$tmp = $this->cfg->actions;
		$val = null;
		if (is_callable($this->cfg->actionsAllowed))
			$val = call_user_func($this->cfg->actionsAllowed, $row);
		if (!$val)
			$val = $this->cfg->actionsAllowedDefault;
		if (is_array($val)) {
			$tmp2 = array();
			foreach($val as $v) {
				if (isset($tmp[$v]))
					$tmp2[$v] = $tmp[$v];
			}
			$tmp = $tmp2;
		}
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
		try {
			return $this->to(request::get('out'));
		} catch (Exception $e) {
			if (DEV) {
				print_r($e); exit;
				debug::trace($e, 2);
			} else
			   throw $e;
		}
	}

}