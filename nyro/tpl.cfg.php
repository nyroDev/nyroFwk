<?php
$cfg = array(
	'module'=>REQUIRED,
	'action'=>REQUIRED,
	'param'=>null,

	'defaultModule'=>'out',
	'default'=>'default',

	'layout'=>true,

	'cache'=>array(
		'auto'=>true,
		'layout'=>true,
		'ttl'=>60,
		'request'=>array(),
	),
);