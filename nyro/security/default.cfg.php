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
	'errorMsg'=>'The login/username couple doesn\'t match.',
	'errorText'=>'You don\'t have the permission to access to this page.',
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