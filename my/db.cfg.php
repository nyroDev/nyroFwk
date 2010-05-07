<?php
$cfg = array(
	'defCfg'=>'nyro',
	'nyro'=>array(
		'use'=>'pdo_mysql',
		'base'=>'to_determine'
	),
);

if (strpos(request::get('serverName'), 'mywebsite.com') !== false) {
	// Database config for prod server
	$cfg['nyro']['base'] = 'prod';
	$cfg['nyro']['user'] = 'prod';
	$cfg['nyro']['pass'] = 'prod';
}
