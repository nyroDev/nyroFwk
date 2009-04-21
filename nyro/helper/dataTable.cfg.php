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
	'fields'=>null,

	'noData'=>'No Data.',

	'actions'=>array(),
	'cache'=>array(),
);