<?php
$cfg = array(
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
	
	'multipleDelete'=>NYROENV == 'admin',
	'multipleAction'=>request::uriDef(array('action'=>'multiple'), array('module')),
	'multiple'=>array(),

	'listPrm'=>array(),
);