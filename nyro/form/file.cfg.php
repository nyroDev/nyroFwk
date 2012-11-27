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

	'uploadify'=>array(
		'uploader'=>request::uri(array('lang'=>null, 'module'=>'css', 'action'=>'uploadify', 'param'=>'uploader.swf', 'out'=>false)),
		'multi'=>true,
		'auto'=>true,
		'fileDesc'=>'Images',
		'buttonText'=>'Browse...',
		'fileExt'=>'*.jpg;*.gif;*.png',
		'wmode'=>'transparent',
		'cancelImg'=>request::uri(array('lang'=>null, 'module'=>'css', 'action'=>'uploadify', 'param'=>'cancel.png', 'out'=>false)),
		'scriptData'=>array(session::getInstance()->getSessIdForce()=>session_id()),
	),
	'plupload'=>array(
		'runtimes'=>'html5,gears,flash,silverlight,html4',
		'hideDelay'=>750,
		'texts'=>array(
			'browse'=>'Browse...',
			'waiting'=>'Waiting',
			'error'=>'Error',
			'cancel'=>'Cancelled',
			'complete'=>'Complete',
		),
		'filters'=>array(
			array('title'=>'Images', 'extensions'=>'jpg,gif,png')
		),
		'flash_swf_url'=>request::uri(array('lang'=>null, 'module'=>'css', 'action'=>'plupload', 'param'=>'plupload.flash.swf', 'out'=>false)),
		'silverlight_xap_url'=>request::uri(array('lang'=>null, 'module'=>'css', 'action'=>'plupload', 'param'=>'plupload.silverlight.xap', 'out'=>false)),
	),
	'html'=>array(
		'type'=>'file',
		'class'=>'file',
	),
);