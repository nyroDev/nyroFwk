<?php
$cfg = array(
	'driver'=>'mysql',
	'driverOptions'=>array(
		PDO\Mysql::ATTR_USE_BUFFERED_QUERY=>true,
	),
	'host'=>'localhost',
	'port'=>3306,
	'user'=>'root',
	'pass'=>null,
	'base'=>'test',
	'prefix'=>false
);