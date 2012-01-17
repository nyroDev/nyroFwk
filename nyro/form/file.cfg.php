<?php
$cfg = array(
	'fileUploadedPrm'=>array(),
	'helper'=>null,
	'helperPrm'=>array(),

	'htmlTagName'=>'input',
	'xulTagName'=>'textbox',

	'autoDeleteOnGet'=>true,
	'deleteLabel'=>'delete',

	'showPreviewDelete'=>true,

	'uploadify'=>array(
		'uploader'=>request::uri(array('lang'=>null, 'module'=>'css', 'action'=>'uploadify', 'param'=>'uploader.swf', 'out'=>null)),
		'multi'=>true,
		'auto'=>true,
		'fileDesc'=>'Images',
		'buttonText'=>'Browse...',
		'fileExt'=>'*.jpg;*.gif;*.png',
		'wmode'=>'transparent',
		'cancelImg'=>request::uri(array('lang'=>null, 'module'=>'css', 'action'=>'uploadify', 'param'=>'cancel.png', 'out'=>null)),
		'scriptData'=>array(session::getInstance()->getSessIdForce()=>session_id()),
	),
	'html'=>array(
		'type'=>'file',
		'class'=>'file',
	),
);