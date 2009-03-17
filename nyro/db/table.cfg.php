<?php
$cfg = array(
	'db'=>db::getInstance(),
	'name'=>REQUIRED,
	'primary'=>null,
	'ident'=>null,

	'defId'=>'id',

	'inserted'=>'inserted',
	'updated'=>'updated',
	'deleted'=>'deleted',

	// used to add more info than extracted from the database
	'fields'=>array(),
	'linked'=>array(),
	'realted'=>array(),
	
	'label'=>array(
		'id'=>'#'
	)
);