<?php
$cfg = array(

	'prefixExec'=>null,

	// if compress == true
	'all'=>array(
		'php'=>false,
		'compress'=>DEV ? false : true,
		'disk_cache'=>DEV ? false : true,
		'etags'=>DEV ? false : true,
		'expires_offset'=>'32d',
		'cache_dir'=>TMPROOT.'compress',
		'gzip_compress'=>true,
		'remove_whitespace'=>true,
		'charset'=>'UTF-8',
	),
	'js'=>array(
		'php'=>true,
		'patch_ie'=>true,
		'remove_firebug'=>false,
	),
	'css'=>array(
		'convert_urls'=>false,
	),
);