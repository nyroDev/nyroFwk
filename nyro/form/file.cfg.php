<?php
$cfg = array(
	'helper'=>null,
	'helperPrm'=>array(),

	'htmlTagName'=>'input',
	'xulTagName'=>'textbox',
	
	'deleteLabel'=>'delete',

	'uploadify'=>array(
		'uploader'=>request::uri(array('lang'=>null, 'module'=>'css', 'action'=>'uploadify', 'param'=>'uploader.swf', 'out'=>null)),
		'multi'=>true,
		'auto'=>true,
		'fileDesc'=>'Images',
		'buttonText'=>'Browse...',
		'fileExt'=>'*.jpg;*.gif;*.png',
		'wmode'=>'transparent',
		'cancelImg'=>request::uri(array('lang'=>null, 'module'=>'css', 'action'=>'uploadify', 'param'=>'cancel.png', 'out'=>null)),
		'scriptData'=>array('phpsessidForce'=>session_id())
	),
	'html'=>array(
		'type'=>'file',
		'class'=>'file'
	)
);