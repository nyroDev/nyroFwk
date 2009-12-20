<?php
$cfg = array(
	'pages'=>array(
		'forbidden'=>'/403',
		'login'=>'/login',
		'logged'=>'/my',
		'logout'=>'/logout',
	),
	'noSecurity'=>array(
		array('module'=>'compress'),
		array('module'=>'pages','action'=>'logout'),
		array('module'=>'pages','action'=>'login'),
	),
);