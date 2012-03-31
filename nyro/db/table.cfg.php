<?php
$cfg = array(
	'db'=>db::getInstance(),
	'name'=>REQUIRED,
	'primary'=>null,
	'ident'=>null,

	'defId'=>'id',
	'defSep'=>' ',
	
	'autoJoin'=>true,

	'inserted'=>'inserted',
	'updated'=>'updated',
	'deleted'=>'deleted',
	'deleteCheckFile'=>false,

	'i18nGetDefaultLangIfNotExist'=>false,
	'optimAfterDelete'=>true,

	// used to add more info than extracted from the database
	'fields'=>array(),
	'linked'=>array(),
	'realted'=>array(),

	'whereRange'=>'1',

	'cacheEnabled'=>true,
	'cacheClearTargeting'=>true,

	'label'=>array(
		'id'=>'#',
	),
);