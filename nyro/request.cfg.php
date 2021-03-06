<?php
$cfg = array(
	'lang'=>'en',
	'defaultLang'=>null,
	'forceLang'=>false,
	'forceNoLang'=>false,
	'noForceLang'=>array(
		'css/', 'js/', 'img/', 'nyroUtils/'
	),
	'module'=>'pages',
	'action'=>'index',
	'param'=>array(),
	'text'=>null,
	'out'=>'html',
	'noOut'=>false,
	'forceNoOut'=>true,
	'defaultOut'=>false,
	'noController'=>'index.php',
	
	'allowScaffold'=>false,

	'forceSecure'=>false,
	'forceServerName'=>false,
	'defaultServerName'=>null,

	'empty'=>'_',
	'sep'=>'/',
	'sepParam'=>',',
	'sepParamSub'=>':',
	
	'absolutizeAllUris'=>false,

	'avlLang'=>array(
		'en'=>'English',
		'fr'=>'Français'
	),

	'outCfg'=>array(
		'html'=>'http_html',
		'xml'=>'http',
		'xul'=>'http_xul',
		'js'=>'http',
		'css'=>'http',
		'json'=>'http',
	),

	'alias'=>array(
		'/'=>'/pages/home',
		'/uploads/(.+)'=>'/pages/upload/',
		'(.+)/uploads/(.+)'=>'/pages/upload/',
		'/login'=>'/pages/login',
		'/logout'=>'/pages/logout',
		'/my'=>'/pages/my',

		'/403'=>'/pages/error/403',
		'/404'=>'/pages/error/404',
		'/500'=>'/pages/error/500',

		'/js/(.+)\.js'=>'/compress/js/\1.js',
		'/js/tiny_mce/(.+)'=>'/nyroUtils/tinyMce/\1',
		'/css/(.+)\.(.+)'=>'/compress/cssExt/\1.\2',
		'/css/(.+)\.css'=>'/compress/css/\1.css',
	),
);