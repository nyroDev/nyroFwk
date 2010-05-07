<?php
$cfg = array(
	'security'=>array(
		'use'=>'default'
	),
	'response_http_html'=>array(
		'meta'=>array(
			'title'=>'Administration',
		),
		'incFiles'=>array(
			array('type'=>'css', 'file'=>'reset'),
			array('type'=>'css', 'file'=>'jqueryui'),
			array('type'=>'css', 'file'=>'admin'),
			array('type'=>'css', 'file'=>'form'),
			
			array('type'=>'js', 'file'=>'jquery'),
			array('type'=>'js', 'file'=>'jqueryui'),
			array('type'=>'js', 'file'=>'actionsConfirmDelete'),
		),
	),
);