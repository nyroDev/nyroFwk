<?php
$cfg = array(
	'db'=>db::getInstance(),
	'name'=>REQUIRED,
	'primary'=>null,
	'ident'=>null,

	'defId'=>'id',
	'defSep'=>' ',

	'inserted'=>'inserted',
	'updated'=>'updated',
	'deleted'=>'deleted',

	// used to add more info than extracted from the database
	'fields'=>array(),
	'linked'=>array(),
	'realted'=>array(),

	'whereRange'=>'1',

	'label'=>array(
		'id'=>'#',
	),
);