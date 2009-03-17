<?php
$cfg = array(
	'db'=>db::getInstance(),
	'table'=>'user',
	'where'=>array(),
	'fields'=>array(
		'id'=>'id',
		'login'=>'email',
		'pass'=>'pass',
		'cryptic'=>'cryptic'
	),
	'labelStayConnected'=>'Stay connected',
	'cookie'=>array(
		'name'=>'stayConnected'
	),
	'cryptPassword'=>'md5',
	'cryptCryptic'=>'md5',

	'default'=>true,
	'spec'=>array(
		array('module'=>'pages', 'action'=>'my')
	),
	'rightRoles'=>array()
);