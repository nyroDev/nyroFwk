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

	'optimAfterDelete'=>true,

	// used to add more info than extracted from the database
	'fields'=>array(),
	'linked'=>array(),
	'realted'=>array(),

	'whereRange'=>'1',

	'cacheEnabled'=>true,

	'label'=>array(
		'id'=>'#',
	),
);