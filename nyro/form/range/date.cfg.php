<?php
$cfg = array(
	'htmlTagName'=>'input',

	'defaultDate'=>time()-60*60*24*7,
	'defaultRange'=>60*60*24*7,

	'start'=>'1980-01-01',
	'end'=>(date('Y')+1).'-01-01',
	'disable'=>array(),

	'min'=>array(),
	'max'=>array(),
	'jsPrm'=>array(
		'buttonImage'=>utils::getIcon(array(
				'name'=>'show_month',
				'type'=>'calendar',
				'imgTag'=>false
			)),
		'buttonImageOnly'=>true,
		'showOn'=>'both',
	)
);
