<?php
$cfg = array(
	'file'=>null,
	'fileSave'=>null,
	'fileSaveAdd'=>null,
	'autoFileSave'=>true,
	'w'=>0,
	'h'=>0,
	'bgColor'=>null,
	'fit'=>false,
	'useMaxResize'=>false,
	'crop'=>false,
	'resizeSmaller'=>true,
	'mask'=>null,
	'watermarks'=>null,
	'rebuild'=>false,
	'grayFilter'=>false,
	'filters'=>array(),
	'html'=>false,
	'forceHtmlSize'=>false,
	'alt'=>'Image',
	'htmlDefOptions'=>array(),
	'autoExt'=>array('gif', 'jpg', 'jpeg', 'png'),
	'forceExt'=>null,
	'filesRoot'=>FILESROOT,
	'webUri'=>false,
	'mime'=>array(
		'image/gif',
		'image/jpeg',
        'image/png',
        'image/x-png',
        'image/pjpeg',
	),
	'jpgQuality'=>100,
	'validErros'=>array(
		'notValidImg'=>'The file "%s" does not allow this type of image.'
	)
);