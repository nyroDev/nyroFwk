<?php
/**
 * @author Cédric Nirousset <cedric@nyrodev.com>
 * @version 0.2
 * @package nyroFwk
 */
/**
 * Scaffold controller to dynamically create administration
 */
class module_scaffold_controller extends module_abstract {

	/**
	 * Table information
	 *
	 * @var db_table
	 */
	protected $table = null;

	/**
	 * Row in action add, edit, and delete
	 *
	 * @var db_row
	 */
	protected $row = null;

	/**
	 * FilterTable for list
	 *
	 * @var helper_filterTable
	 */
	protected $filterTable;

	/**
	 * Datatable for list
	 *
	 * @var helper_dataTable
	 */
	protected $dataTable;

	/**
	 * Form in action show, add, and edit
	 *
	 * @var form
	 */
	protected $form = null;

	/**
	 * Columns array
	 *
	 * @var array
	 */
	protected $cols;

	/**
	 * Related table names array
	 *
	 * @var array
	 */
	protected $related;

	/**
	 * Fields informations
	 * @see db_abstract::fields
	 *
	 * @var array
	 */
	protected $fields;

	/**
	 * Index Page URL
	 *
	 * @var string
	 */
	protected $indexPage;
	
	/**
	 * Ids selected in multiple action
	 *
	 * @var array
	 */
	protected $ids;

	protected function afterInit() {
		parent::afterInit();

		if ($this->getName() != 'scaffold')
			$this->cfg->name = $this->getName();

		if (!empty($this->cfg->name)) {
			$this->cfg->overload('module_scaffold_'.$this->cfg->name);

			$this->table = db::get('table', $this->cfg->name, array(
				'name'=>$this->cfg->name
			));
			$this->cols = $this->table->getCols();
			$this->related = array_keys($this->table->getRelated());
			$this->fields = $this->table->getField();
			$this->indexPage = request::uriDef(array('action'=>'', 'param'=>''));
		}

		$this->cfg->tplPrm = array(
			'layout'=>$this->cfg->layout,
			'module'=>'scaffold',
			'action'=>$this->cfg->name.ucfirst($this->cfg->viewAction),
			'defaultModule'=>'scaffold',
			'default'=>$this->cfg->viewAction,
			'cache'=>$this->cfg->cache
		);
	}

	protected function execIndex($prm=null) {
		$this->setViewAction('list');
		return $this->execScaffoldList($prm);
	}

	protected function execShow($prm = null) {
		return $this->execScaffoldShow($prm);
	}

	protected function execScaffoldIndex($prm = null) {
		if ($this->isScaffolded()) {
			if (empty($this->cfg->name)) {
				$db = db::getInstance();
				$tables = $db->getTables();
				$links = array();
				foreach($tables as $t) {
					if (!strpos($t, '_') && !strpos($t, db::getCfg('i18n')))
						$links[$t] = request::uriDef(array('module'=>$t, 'action'=>'', 'param'=>''));
				}
				$this->setViewVar('links', $links);
			} else {
				$this->setViewAction('list');
				return $this->execScaffoldList($prm);
			}
		}
	}

	protected function isScaffolded() {
		return strtolower($this->prmExec['prefix']) == 'scaffold';
	}

	protected function execScaffoldList($prm = null) {
		$iconType = $this->cfg->iconType ? $this->cfg->iconType : $this->cfg->name;

		$this->filterTable = null;
		$query = null;
		if (!empty($this->cfg->filter)) {
			$this->filterTable = factory::getHelper('filterTable', array_merge($this->cfg->filterOpts, array(
				'table'=>$this->table,
				'fields'=>is_array($this->cfg->filter)? $this->cfg->filter : null,
			)));
			if ($this->cfg->addFilterTableJs)
				response::getInstance()->addJs('filterTable');
			if ($this->filterTable->hasValues())
				$this->filterTable->getForm()->getCfg()->formPlus = str_replace('class="', 'class="filterTableActive ', $this->filterTable->getForm()->getCfg()->formPlus);
			$this->hook('listFilter');
			$query = array('where'=>$this->updateFilterWhere($this->filterTable->getWhere()));
		}

		$conf = array(
			'table'=>$this->table,
			'query'=>$query,
			'name'=>$this->cfg->name.'DataTable',
			'iconType'=>$iconType,
			'cache'=>$this->cfg->cache,
			'fields'=>$this->cfg->list,
			'actions'=>array(
				'show'=>request::uriDef(array('action'=>'show', 'param'=>'[id]')),
				'edit'=>request::uriDef(array('action'=>'edit', 'param'=>'[id]')),
				'delete'=>request::uriDef(array('action'=>'delete', 'param'=>'[id]')),
			),
			'actionsAlt'=>array(
				'show'=>tr::__('scaffold_show'),
				'edit'=>tr::__('scaffold_goEdit'),
				'delete'=>tr::__('scaffold_delete'),
			),
			'multiple'=>$this->cfg->multiple,
			'multipleAction'=>$this->cfg->multipleAction,
		);
		
		if ($this->cfg->multipleDelete) {
			$conf['multiple'] = array_merge($conf['multiple'], array(
				'delete'=>array(
					'label'=>tr::__('scaffold_delete'),
				)
			));
		}
		
		factory::mergeCfg($conf, $this->cfg->listPrm);
		$this->dataTable = factory::getHelper('dataTable', $conf);

		$this->hook('list');

		$this->setViewVars(array(
			'filterTable'=>$this->filterTable,
			'dataTable'=>$this->dataTable,
			'iconType'=>$iconType,
			'allowAdd'=>$this->cfg->allowAdd,
			'addPage'=>request::uriDef(array('action'=>'add', 'param'=>''))
		));
	}
    
    protected function updateFilterWhere(db_where $where = null) {
        return $where;
    }
	
	protected function execScaffoldMultiple() {
		$action = http_vars::getInstance()->post('action');
		if ($action) {
			$fctName = 'multiple'.ucfirst($action);
			$uAction = 'Multiple'.ucfirst($action);
			$this->ids = http_vars::getInstance()->post($this->table->getIdent());
			$this->hook('before'.$uAction);
			$actionConf = $this->cfg->getInArray('multiple', $action);
			$call = null;
			if (is_array($actionConf) && isset($actionConf['callback']) && is_callable($actionConf['callback'])) {
				$call = $actionConf['callback'];
			} else if (is_callable(array($this, $fctName)))
				$call = array($this, $fctName);
			if (!is_null($call))
				call_user_func($call, $this->ids);
			$this->hook('after'.$uAction);
		}
		response::getInstance()->redirect($this->indexPage);
	}
	
	/**
	 * Multiple delete action
	 *
	 * @param array $ids 
	 */
	protected function multipleDelete(array $ids) {
		$this->table->delete($this->table->getWhere(array(
			'clauses'=>factory::get('db_whereClause', array(
					'name'=>$this->table->getRawName().'.'.$this->table->getIdent(),
					'in'=>$ids
				))
		)));
	}

	/**
	 * Function to be rewritten in eventual child to change the way the scaffold works
	 * Available actions:
	 * - list, listFilter
	 * - show, formShow
	 * - formInit, formInitAdd, formInitEdit
	 * - add, formPostAdd, beforeAdd, afterAdd, formAdd
	 * - edit, formPostEdit, beforeEdit, afterEdit, formEdit
	 * - delete, beforeDelete, afterDelete
	 * - beforeMultipleDelete, afterMultipleDelete, beforeMultiple*, afterMultiple*
	 *
	 * @param string $action
	 */
	protected function hook($action) {}

	protected function execScaffoldShow($prm = null) {
		$id = $prm[0];

		$this->row = $this->table->find($id);
		$this->hook('show');

		$this->form = $this->row->getForm($this->getFields('show'), array('mode'=>'view', 'sectionName'=>tr::__('scaffold_show')), false);
		$this->form->action = request::uriDef(array('module'=>$this->table->getName(),'action'=>'edit', 'param'=>$id));
		$this->form->method = 'get';
		$this->form->setSubmitText(tr::__('scaffold_goEdit'));
		$this->form->setSubmitplus('<a href="'.$this->indexPage.'">'.tr::__('scaffold_back').'</a>');

		$this->hook('formShow');

		$this->setViewVars(array(
			'row'=>$this->row,
			'form'=>$this->form
		));
	}

	protected function execScaffoldAdd($prm = null) {
		if (!$this->cfg->allowAdd) {
			response::getInstance()->redirect($this->indexPage);
		}
		return $this->addEditForm('add');
	}

	protected function execScaffoldDuplic($prm = null) {
		$this->setViewAction('add');
		return $this->addEditForm('duplic', $prm[0]);
	}

	protected function execScaffoldEdit($prm = null) {
		return $this->addEditForm('edit', $prm[0]);
	}

	protected function addEditForm($action, $id = null) {
		$uAction = ucfirst($action);
		$this->row = $id ? $this->table->find($id) : $this->table->getRow();
		if (!$this->row)
			response::getInstance()->redirect($this->indexPage);
		
		if ($action == 'duplic') {
			$tmp = $this->row;
			$this->row = $this->table->getRow();
			$this->row->setValues($tmp->getValues());
			$this->row->setValues($tmp->getValues('flat'));
			$action = 'add';
		}	
		
		$this->hook($action);

		$this->form = $this->row->getForm($this->getFields($action), array_merge(array('sectionName'=>tr::__('scaffold_'.$action)), $this->cfg->formOpts));
		$this->hook('formInit');
		$this->hook('formInit'.$uAction);

		if (request::isPost()) {
			$this->form->refill();
			$this->hook('formPost'.$uAction);
			if ($this->form->isValid()) {
				$this->row->setValues($this->form->getValues());
				$this->hook('before'.$uAction);
				if ($this->row->save()) {
					$this->hook('after'.$uAction);
					response::getInstance()->redirect($this->indexPage);
				}
			} else
				$this->setViewVar('errors', $this->form->getErrors());
		}

		$this->form->setSubmitText(tr::__('scaffold_'.$action));
		$this->form->setSubmitplus('<a href="'.$this->indexPage.'">'.tr::__('scaffold_back').'</a>');

		$this->hook('form'.$uAction);

		$this->setViewVars(array(
			'row'=>$this->row,
			'form'=>$this->form
		));
	}

	protected function execScaffoldDelete($prm = null) {
		$id = $prm[0];
		$this->row = $this->table->find($id);
		$this->hook('delete');
		$this->hook('beforeDelete');
		if ($this->row)
			$this->row->delete();
		$this->hook('afterDelete');
		response::getInstance()->redirect($this->indexPage);
	}

	/**
	 * Get the Fields for a specific actions
	 *
	 * @param string $action Action name (list, add, edit or show)
	 * @return array
	 */
	protected function getFields($action) {
		$tmp = $this->cfg->get($action);
		if (is_array($tmp))
			return $tmp;
		$ret = $this->cols;
		if ($this->cfg->autoRelated)
			$ret = array_merge($ret, $this->related);
		if (count($this->table->getI18nFields())) {
			foreach($this->table->getI18nFields() as $v)
				$ret[] = db::getCfg('i18n').$v['name'];
		}
		return $ret;
	}

}
