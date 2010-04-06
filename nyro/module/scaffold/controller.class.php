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

		$this->cfg->tplPrm = array(
			'layout'=>$this->cfg->layout,
			'module'=>'scaffold',
			'action'=>$this->cfg->name.ucfirst($this->cfg->viewAction),
			'defaultModule'=>'scaffold',
			'default'=>$this->cfg->viewAction,
			'cache'=>$this->cfg->cache
		);
	}

	/**
	 * @todo See how it should work (security, etc...)
	 */
	protected function execIndex($prm=null) {
		$this->setViewAction('list');
		return $this->execScaffoldList($prm);
	}

	protected function execShow($prm=null) {
		return $this->execScaffoldShow($prm);
	}

	protected function execScaffoldIndex($prm=null) {
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

	protected function execScaffoldList($prm=null) {
		$iconType = $this->cfg->iconType? $this->cfg->iconType : $this->cfg->name;

		$this->filterTable = null;
		$query = null;
		if (!empty($this->cfg->filter)) {
			$this->filterTable = factory::getHelper('filterTable', array(
				'table'=>$this->table,
				'fields'=>is_array($this->cfg->filter)? $this->cfg->filter : null,
			));
			$query = array('where'=>$this->filterTable->getWhere());
		}

		$this->dataTable = factory::getHelper('dataTable', array_merge_recursive(array(
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
			),
		), $this->cfg->listPrm));

		$this->hook('list');

		$this->setViewVars(array(
			'filterTable'=>$this->filterTable,
			'dataTable'=>$this->dataTable,
			'iconType'=>$iconType,
			'addPage'=>request::uriDef(array('action'=>'add', 'param'=>''))
		));
	}

	/**
	 * Function to be rewritten in eventual child to change the way the scaffold works
	 * Available actions:
	 * - show, formShow
	 * - add, formPostAdd, beforeAdd, afterAdd, formAdd
	 * - edit, formPostEdit, beforeEdit, afterEdit, formEdit
	 * - delete, beforeDelete, afterDelete
	 *
	 * @param string $action
	 */
	protected function hook($action) {}


	protected function execScaffoldShow($prm=null) {
		$id = $prm[0];

		$this->row = $this->table->find($id);
		$this->hook('show');

		$this->form = $this->row->getForm($this->getFields('show'), array('mode'=>'view', 'sectionName'=>tr::__('scaffold_show')), false);
		$this->form->action = array('module'=>$this->table->getName(),'action'=>'edit','param'=>$id);
		$this->form->method = 'get';
		$this->form->setSubmitText(tr::__('scaffold_edit'));
		$this->form->setSubmitplus('<a href="'.$this->indexPage.'">'.tr::__('scaffold_back').'</a>');

		$this->hook('formShow');

		$this->setViewVar('form', $this->form);
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

		$this->form = $this->row->getForm($this->getFields($action), array('sectionName'=>tr::__('scaffold_'.$action)));

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

		$this->setViewVar('form', $this->form);
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
