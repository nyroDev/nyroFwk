<?php
$cfg = array(
	'headers'=>array(),
	'incFiles'=>array(),
	'meta'=>array(
		'title'=>NYRONAME,
		'robots'=>'index, follow',
		'description'=>'nyro project',
		'keywords'=>'nyro, project',
		'language'=>request::get('lang')
	),
	'js'=>array(
		'alias'=>array(
			'jquery'=>'jquery-1.3.1',
			'jqueryui'=>'jquery-ui-personalized-1.6rc6',
		),
		'ext'=>'js',
		'dirWeb'=>'js',
		'dirUriNyro'=>'js',
		'include'=>'<script type="text/javascript" src="%s"></script>',
		'block'=>'<script type="text/javascript">%s</script>',
		'depend'=>array(
			'debug'=>array('jquery'),
			'jqueryui'=>array(
				'jquery',
				array('file'=>'jqueryui', 'type'=>'css')
			)
		)
	),
	'css'=>array(
		'alias'=>array(),
		'ext'=>'css',
		'dirWeb'=>'css',
		'dirUriNyro'=>'css',
		'include'=>'<link rel="stylesheet" href="%s" type="text/css" media="%s" />',
		'block'=>'<style type="text/css">%s</style>',
		'depend'=>array()
	)
);