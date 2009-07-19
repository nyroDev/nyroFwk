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
			'jquery'=>'jquery-1.3.2',
			'jqueryui'=>'jquery-ui-1.7.custom.min',
			'nyroModal'=>'jquery.nyroModal-1.5.1',
		),
		'ext'=>'js',
		'dirWeb'=>'js',
		'dirUriNyro'=>'js',
		'include'=>'<script type="text/javascript" src="%s"></script>',
		'block'=>'<script type="text/javascript">%s</script>',
		'depend'=>array(
			'debug'=>array('jquery'),
			'actionsConfirmDelete'=>array('jquery'),
			'nyroModal'=>array(
				'jquery',
				array('file'=>'nyroModal', 'type'=>'css')
			),
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
if (DEV) {
	$cfg['incFiles'] = array(
		array('type'=>'js', 'file'=>'jquery')
	);
}