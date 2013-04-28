<?php
$cfg = array(
	'db'=>db::getInstance(),
	'name'=>REQUIRED,

	'defId'=>REQUIRED,
	'defSep'=>' ',

	'inserted'=>'inserted',
	'updated'=>'updated',
	'deleted'=>'deleted',

	// used to add more info than extracted from the database
	'fields'=>array(),
	'linked'=>array(),
	'related'=>array(),

	'cacheEnabled'=>true,
	'cacheClearTargeting'=>true,
);