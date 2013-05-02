<?php
$cfg = array(
	'server'=>null,
	'options'=>array(
		'connect'=>true
	),
	'dbName'=>REQUIRED,
	
	'tableClass'=>'db_mongo_table',
	'rowsetClass'=>'db_mongo_rowset',
	'rowClass'=>'db_mongo_row',
	'whereClass'=>'db_mongo_where',
	'whereClauseClass'=>'db_mongo_whereClause',
	
	'configuration'=>array(
		'tables'=>REQUIRED
	),
	
	'cache'=>array(
		'enabled'=>false
	)
);