<?php
$cfg = array(
	'db'=>db::getInstance(),
	'table'=>'user',
	'where'=>array(),
	'fields'=>array(
		'login'=>'email',
		'pass'=>'pass',
		'cryptic'=>'cryptic',
		'lastConnected'=>'lastConnected',
	),
	'formOptions'=>array(),
	'sessionNameSpace'=>'security_default',
	'timeLastConnected'=>20,
	'stayConnected'=>true,
	'labelStayConnected'=>'Stay connected',
	'errorMsg'=>'The login/username couple doesn\'t match.',
	'errorText'=>'You don\'t have the permission to access to this page.',
	'cookie'=>array(
		'name'=>'stayConnected',
	),
	'cryptPassword'=>'md5',
	'cryptCryptic'=>'md5',
	'unloggedCryptic'=>'unlogged_',

	'default'=>false,
	'spec'=>array(
		array('module'=>'pages', 'action'=>'my'),
	),
	'rightRoles'=>array(),
);