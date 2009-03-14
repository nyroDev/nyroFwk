<?php

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
	}

	/**
	 * @todo See how it should work (security, etc...)
	 */
	protected function execIndex($prm=null) {
		return $this->execScaffoldList($prm);
	}

	protected function execShow($prm=null) {
		return $this->execScaffoldShow($prm);
	}

	protected function execScaffoldIndex($prm=null) {
		if (strtolower($this->cfg->prefixExec) == 'scaffold') {
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
				$this->setViewAction($this->cfg->prefixExec.'List');
				return $this->execScaffoldList($prm);
			}
		}
	}

	protected function execScaffoldList($prm=null) {
		$iconType = $this->cfg->iconType? $this->cfg->iconType : $this->cfg->name;

		$filterTable = null;
		$query = null;
		if (!empty($this->cfg->filter)) {
			$filterTable = factory::getHelper('filterTable', array(
				'table'=>$this->table,
				'fields'=>is_array($this->cfg->filter)? $this->cfg->filter : null,
			));
			$query = array('where'=>$filterTable->getWhere());
		}

		$dataTable = factory::getHelper('dataTable', array_merge(array(
			'table'=>$this->table,
			'query'=>$query,
			'name'=>$this->cfg->name.'DataTable',
			'iconType'=>$iconType,
			'cache'=>$this->cfg->cache,
			'fields'=>$this->cfg->list,
			'actions'=>array(
				'show'=>request::uriDef(array('action'=>'show','param'=>'[id]')),
				'edit'=>request::uriDef(array('action'=>'edit','param'=>'[id]')),
				'delete'=>request::uriDef(array('action'=>'delete','param'=>'[id]')),
			),
			'actionsAlt'=>array(
				'show'=>tr::__('scaffold_show'),
				'edit'=>tr::__('scaffold_edit'),
				'delete'=>tr::__('scaffold_delete'),
			)
		), $this->cfg->listPrm));

		$this->setViewVars(array(
			'filterTable'=>$filterTable,
			'dataTable'=>$dataTable,
			'iconType'=>$iconType,
			'addPage'=>request::uriDef(array('action'=>'add', 'param'=>''))
		));
	}

	/**
	 * Function to be rewritten in eventual child to change the way the scaffold works
	 * Available actions:
	 * - show, formShow
	 * - add, beforeAdd, afterAdd, formAdd
	 * - edit, beforeEdit, afterEdit, formEdit
	 * - delete, beforeDelete, afterDelete
	 *
	 * @param string $action
	 */
	protected function hook($action) {}


	protected function execScaffoldShow($prm=null) {
		$id = $prm[0];

		$this->row = $this->table->find($id);
		$this->hook('show');

		$this->form = $this->row->getForm($this->getFields('show'), array('mode'=>'view'), false);
		$this->form->action = array('module'=>$this->table->getName(),'action'=>'edit','param'=>$id);
		$this->form->method = 'get';
		$this->form->setSubmitText(tr::__('scaffold_edit'));

		$this->hook('formShow');

		$this->setViewVars(array(
			'form'=>$this->form,
			'indexPage'=>$this->indexPage,
			'editPage'=>request::uriDef(array('action'=>'edit'))
		));
	}

	protected function execScaffoldAdd($prm=null) {
		return $this->addEditForm('add');
	}

	protected function execScaffoldEdit($prm=null) {
		return $this->addEditForm('edit', $prm[0]);
	}

	protected function addEditForm($action, $id=null) {
		$uAction = ucfirst($action);
		$this->row = $id? $this->table->find($id) : $this->table->getRow();
		$this->hook($action);

		$this->form = $this->row->getForm($this->getFields($action));

		if ($this->form->refillIfSent()) {
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
			'form'=>$this->form
		));
	}

	protected function execScaffoldDelete($prm=null) {
		$id = $prm[0];
		$this->row = $this->table->find($id);
		$this->hook('delete');
		$this->hook('beforeDelete');
		$this->row->delete();
		$this->hook('afterDelete');
		$resp = response::getInstance();
		$resp->addHeader('Location', $this->indexPage);
		$resp->send(true);
	}

	/**
	 * Publish the module to shown
	 *
	 * @return string The fetched view
	 */
	public function publish(array $prm = array()) {
		// Used for the child in no scaffolded action
		if (strpos($this->cfg->viewAction, 'scaffold') !== 0)
			return parent::publish($prm);

		if (!$this->cfg->viewAction)
			return null;

		$tpl = factory::get('tpl', array(
			'layout'=>$this->cfg->layout,
			'module'=>'scaffold',
			'action'=>$this->cfg->name.ucfirst($this->cfg->viewAction),
			'defaultModule'=>'scaffold',
			'default'=>$this->cfg->viewAction,
			'cache'=>$this->cfg->cache
		));
		$tpl->setA($this->cfg->viewVars);
		return $tpl->fetch($prm);
	}

	/**
	 * Get the Fields for a specific actions
	 *
	 * @param string $action Action name (list, add, edit or show)
	 * @return array
	 */
	protected function getFields($action) {
		return ($this->cfg->check($action) &&
			($tmp = $this->cfg->get($action)) &&
			is_array($tmp))? $tmp
			: ($this->cfg->autoRelated
				?array_merge($this->cols, $this->related)
				:$this->cols);
	}
}
