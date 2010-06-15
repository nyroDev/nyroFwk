<?php
$cfg = array(
	'tableClass'=>'db_table',
	'rowsetClass'=>'db_rowset',
	'rowClass'=>'db_row',

	'linked'=>'linked',
	'related'=>'related',

	'table'=>array(),

	'rowset'=>array(),

	'row'=>array(),

    'defCfg'=>'default',

    'i18n'=>'i18n',
	'relatedValue'=>'value',

	'default'=>array(
		'use'=>'pdo_mysql',
	),

    'admin'=>array(
		'use'=>'pdo_mysql',
        'base'=>'to52',
    ),
);