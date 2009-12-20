<?php
$cfg = array(
	'htmlTagName'=>'input',
	'xulTagName'=>'datepicker',

	'start'=>'1980-01-01',
	'end'=>(date('Y')+1).'-01-01',
	'disable'=>array(),

	'html'=>array(
		'class'=>'date',
	),

	'jsPrm'=>array(
		'buttonImage'=>utils::getIcon(array(
				'name'=>'show_month',
				'type'=>'calendar',
				'imgTag'=>false
			)),
		'buttonImageOnly'=>true,
		'showOn'=>'both',
	),

	'xul'=>array(),
);