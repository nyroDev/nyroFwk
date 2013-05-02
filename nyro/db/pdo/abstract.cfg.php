<?php
$cfg = array(
	'tableClass'=>'db_pdo_table',
	'rowsetClass'=>'db_pdo_rowset',
	'rowClass'=>'db_pdo_row',
	'whereClass'=>'db_pdo_where',
	'whereClauseClass'=>'db_pdo_whereClause',
	
	'host'=>REQUIRED,
	'port'=>REQUIRED,
	'user'=>REQUIRED,
	'pass'=>REQUIRED,
	'base'=>REQUIRED,
	'driver'=>REQUIRED,
	'driverOptions'=>array(),
	'conQuery'=>array(
		'SET NAMES \'utf8\'',
	),
	'fetchMode'=>db_pdo_abstract::FETCH_ASSOC,
	'quoteIdentifier'=>'`',
	'quoteValue'=>'"',
	'sepCom'=>'~',
	'sepComVal'=>'¤',
);