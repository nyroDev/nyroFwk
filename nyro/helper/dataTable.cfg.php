<?php
$cfg = array(
	'table'=>REQUIRED,
	'query'=>null,

	'name'=>REQUIRED,

	'nameParam'=>null,

	'nbPerPage'=>50,
	'page'=>1,
	'sortBy'=>null,
	'sortDir'=>'asc',

	'pageLinkReplace'=>'[page]',
	'pageLinkTpl'=>null,
	'pageLinkTpl1'=>null,

	'iconType'=>null,
	'addIdentField'=>true,
	'fields'=>null,

	'noData'=>'No Data.',

	'tplVars'=>array(),

	'actions'=>array(),
	'actionsAlt'=>array(),
	'actionsImg'=>array(),
	'actionsAllowed'=>null,
	'actionsAllowedDefault'=>null,
	'actionsConfirmDelete'=>true,
	'cache'=>array(),
);