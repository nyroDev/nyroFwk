<?php
$cfg = array(
	'headers'=>array(),
	'incFiles'=>array(),
	'titleInDes'=>'. ',
	'meta'=>array(
		'title'=>NYRONAME,
		'robots'=>'index, follow',
		'description'=>'nyro project',
		'keywords'=>'nyro, project',
		'language'=>request::get('lang'),
	),
	'link'=>array(),
	'js'=>array(
		'alias'=>array(
			'jqueryui'=>'jquery-ui.min',
			'nyroModal'=>'jquery.nyroModal',
		),
		'ext'=>'js',
		'dirWeb'=>'js',
		'dirUriNyro'=>'js',
		'include'=>'<script type="text/javascript" src="%s"></script>',
		'block'=>'<script type="text/javascript">
//<![CDATA[
%s
//]]>
</script>',
		'depend'=>array(
			'debug'=>array('jquery'),
			'actionsConfirmDelete'=>array('jquery'),
			'nyroModal'=>array(
				'jquery',
				array('file'=>'nyroModal', 'type'=>'css'),
			),
			'jqueryui'=>array(
				'jquery',
				array('file'=>'jqueryui', 'type'=>'css'),
			),
			'checkboxFields'=>array('jquery'),
		),
		'dependAfter'=>array(),
	),
	'css'=>array(
		'alias'=>array(),
		'ext'=>'css',
		'dirWeb'=>'css',
		'dirUriNyro'=>'css',
		'include'=>'<link rel="stylesheet" href="%s" type="text/css" media="%s" />',
		'block'=>'<style type="text/css">%s</style>',
		'depend'=>array(),
	),
);
if (($lang = request::get('lang')) != 'en') {
	$cfg['js']['dependAfter'] = array(
		'jqueryui'=>array(
			'i18n_jquery.ui.datepicker-'.$lang,
		),
	);
}
if (DEV) {
	$cfg['incFiles'] = array(
		array('type'=>'js', 'file'=>'jquery'),
		array('type'=>'js', 'file'=>'jquery.lint'),
	);
}