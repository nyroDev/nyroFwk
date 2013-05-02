<?php
$cfg = array(
	'tableClass'=>REQUIRED,
	'rowsetClass'=>REQUIRED,
	'rowClass'=>REQUIRED,
	'whereClass'=>REQUIRED,
	'whereClauseClass'=>REQUIRED,

	'table'=>array(),
	'rowset'=>array(),
	'row'=>array(),

	'getInstanceCfg'=>REQUIRED,
	'cache'=>array(
		'ttl'=>3600,
		'request'=>array('uri'=>false, 'meth'=>array()),
		'startFile'=>array(
			NYRONAME,
			REPLACECONF=>true
		)
	),
);