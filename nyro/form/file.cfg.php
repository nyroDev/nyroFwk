<?php
$cfg = array(
	'fileUploadedPrm'=>array(),
	'helper'=>null,
	'helperPrm'=>array(),

	'htmlTagName'=>'input',
	'xulTagName'=>'textbox',
	
	'htmlWrap'=>'p',

	'autoDeleteOnGet'=>true,
	'deleteLabel'=>'delete',

	'showPreview'=>true,
	'showDelete'=>true,

	'plupload'=>array(
		'showCancelAll'=>true,
		'addFormVars'=>false,
		'runtimes'=>'html5,gears,flash,silverlight,html4',
		'hideDelay'=>750,
		'texts'=>array(
			'browse'=>'Browse...',
			'waiting'=>'Waiting',
			'error'=>'Error',
			'cancel'=>'Cancelled',
			'complete'=>'Complete',
			'cancelAll'=>'Cancel all',
		),
		'filters'=>array(
			array('title'=>'Images', 'extensions'=>'jpg,jpeg,gif,png')
		),
		'flash_swf_url'=>request::uri(array('lang'=>null, 'module'=>'css', 'action'=>'plupload', 'param'=>'plupload.flash.swf', 'out'=>false)),
		'silverlight_xap_url'=>request::uri(array('lang'=>null, 'module'=>'css', 'action'=>'plupload', 'param'=>'plupload.silverlight.xap', 'out'=>false)),
	),
	'html'=>array(
		'type'=>'file',
		'class'=>'file',
	),
);