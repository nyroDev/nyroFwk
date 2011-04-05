<?php
$cfg = array(
	'host'=>REQUIRED,
	'port'=>REQUIRED,
	'user'=>REQUIRED,
	'pass'=>REQUIRED,
	'base'=>REQUIRED,
	'getInstanceCfg'=>REQUIRED,
	'fetchMode'=>db::FETCH_ASSOC,
	'quoteIdentifier'=>'`',
	'sepCom'=>'~',
	'sepComVal'=>'¤',
	'quoteValue'=>'"',
	'cache'=>array(
		'ttl'=>3600,
		'request'=>array('uri'=>false, 'meth'=>array())
	),
);