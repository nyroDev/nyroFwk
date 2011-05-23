<?php
$cfg = array(
	'prefixExec'=>null,
	'default'=>array(
		'title'=>'Browser',
		'dir'=>'nyroBrowser',
		'subdir'=>null,
		'layout'=>'nyroBrowser',
		'formName'=>'Send new files',
		'search'=>'Search',
		'filesTitle'=>'Files',
		'noFiles'=>'No files found.',
		'name'=>'Filename',
		'size'=>'Size',
		'date'=>'Date',
		'delete'=>'delete',
		'incFiles'=>array(
			array('type'=>'css', 'file'=>'uploadify'),
			array('type'=>'css', 'file'=>'nyroBrowser'),
			
			array('type'=>'js', 'file'=>'jquery'),
			array('type'=>'js', 'file'=>'nyroBrowser'),
			array('type'=>'js', 'file'=>'actionsConfirmDelete'),
			array('type'=>'js', 'file'=>'tiny_mce/tiny_mce_popup', 'dir'=>'web', 'verifExists'=>false),
		),
		'imgHelper'=>factory::getHelper('image', array(
			'fit'=>true,
			'w'=>120,
			'h'=>120,
			'fileSaveAdd'=>'nyroBrowserThumb',
		)),
		'uploadedUri'=>array('controller'=>false),
		'uploadify'=>array(
			'image'=>array(),
			'media'=>array(
				'fileDesc'=>'Media',
				'fileExt'=>null,
			),
			'file'=>array(
				'fileDesc'=>'Files',
				'fileExt'=>null,
			),
		),
		'helper'=>array(
			'image'=>array(
				'name'=>'image',
				'prm'=>array(
					'upload'=>array(
						'w'=>600,
						'resizeSmaller'=>false
					)
				),
			),
			'media'=>array(
				'name'=>'file',
				'prm'=>array(),
			),
			'file'=>array(
				'name'=>'file',
				'prm'=>array(),
			),
		),
		'formCfg'=>array(
			'html'=>array(
				'line'=>'[field]',
				'section'=>'<fieldset>
					<legend><span>[label]</span></legend>
					[fields]
				</fieldset>',
				'incFiles'=>array(
					REPLACECONF=>true,
				),
			),
		),
	),
);