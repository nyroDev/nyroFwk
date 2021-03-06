<?php
$cfg = array(
	'table'=>REQUIRED,
	'query'=>null,

	'name'=>REQUIRED,
	'sessionName'=>null,
	'useSession'=>true,

	'nameParam'=>null,

	'nbPerPage'=>50,
	'nbPageMax'=>false,
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

	'tplVars'=>array(
		'sortIndicatorAsc'=>'^',
		'sortIndicatorDesc'=>'v',
	),

	'actions'=>array(),
	'actionsAlt'=>array(),
	'actionsImg'=>array(),
	'actionsAllowed'=>null,
	'actionsAllowedDefault'=>null,
	'actionsConfirmDelete'=>true,

	'addCheckAllJs'=>true,
	'multiple'=>array(),
	'multipleLabel'=>'Choose an action',
	'multipleSubmit'=>'ok',
	'multipleAction'=>request::uriDef(array('action'=>'multiple'), array('module')),

	'cache'=>array(),
);