<?php
$cfg = array(
	'name'=>null,

	'prefixExec'=>'scaffold',

	'autoRelated'=>true,
	'filter'=>null,
	'list'=>null,
	'show'=>null,
	'edit'=>null,

	'formOpts'=>array(),

	'allowAdd'=>true,
	'iconType'=>null,
	
	'multipleDelete'=>NYROENV == 'admin',
	'multipleAction'=>request::uriDef(array('action'=>'multiple'), array('module')),
	'multiple'=>array(),

	'listPrm'=>array(),
);