<?php
$cfg = array(
	'db'=>db::getInstance(),
	'name'=>null,

	'prefixExec'=>'scaffold',

	'autoRelated'=>true,
	'filter'=>null,
	'list'=>null,
	'show'=>null,
	'edit'=>null,

	'addFilterTableJs'=>true,
	'filterOpts'=>array(
		'formOpts'=>array(
			'formPlus'=>' class="filterTable"'
		)
	),
	'formOpts'=>array(),

	'allowAdd'=>true,
	'iconType'=>null,
	
	'actions'=>array(
		'show'=>request::uriDef(array('action'=>'show', 'param'=>'[id]')),
		'edit'=>request::uriDef(array('action'=>'edit', 'param'=>'[id]')),
		'delete'=>request::uriDef(array('action'=>'delete', 'param'=>'[id]')),
	),
	'multipleDelete'=>NYROENV == 'admin',
	'multipleAction'=>request::uriDef(array('action'=>'multiple'), array('module')),
	'multiple'=>array(),

	'listPrm'=>array(),
);