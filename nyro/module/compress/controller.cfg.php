<?php
$cfg = array(

	'prefixExec'=>null,

	'cache'=>array(
		'ttl'=>0,
		'serialize'=>false,
	),
	'all'=>array(
		'php'=>false,
		'compress'=>DEV ? false : true,
		'disk_cache'=>DEV ? false : true,
		'etags'=>DEV ? false : true,
		'expires_offset'=>'32d',
		'gzip_compress'=>true,
	),
	
	'css'=>array(
		'filters'=>array(
			'ImportImports'=>false,
			'RemoveComments'=>true, 
			'RemoveEmptyRulesets'=>true,
			'RemoveEmptyAtBlocks'=>true,
			'ConvertLevel3AtKeyframes'=>false,
			'ConvertLevel3Properties'=>false,
			'Variables'=>true,
			'RemoveLastDelarationSemiColon'=>true
		),
		'plugins'=>array(
			'Variables'=>true,
			'ConvertFontWeight'=>false,
			'ConvertHslColors'=>false,
			'ConvertRgbColors'=>false,
			'ConvertNamedColors'=>false,
			'CompressColorValues'=>false,
			'CompressUnitValues'=>false,
			'CompressExpressionValues'=>false
		)
	),
	'js'=>array(
		'php'=>true,
		'patch_ie'=>true,
	),

);