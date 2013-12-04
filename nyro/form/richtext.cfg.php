<?php
$cfg = array(
	'html'=>array(
		'class'=>'text multiline richtext',
	),
	'nyroBrowser'=>array(
		'active'=>true,
		'config'=>'default',
		'url'=>request::uri('nyroBrowser'),
		'width'=>800,
		'height'=>550,
		'title'=>'Browser',
	),
	'tinyMce'=>array(
		'script_url'=>request::uri('js/tinyMce/tiny_mce_gzip.php'.(DEV ? null : '?diskcache=true')),
		'height'=>340,
		
		'language'=>request::get('lang'),
		
		'theme'=>'modern',
		'plugins'=>'lists,advlist,anchor,autolink,link,image,charmap,preview,hr,searchreplace,visualblocks,visualchars,code,fullscreen,insertdatetime,media,nonbreaking,table,paste,contextmenu,tabfocus,wordcount',
		'toolbar'=>'undo redo | styleselect | bold italic | removeformat | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image media fullpage',
		'relative_urls'=>false,
		
		'menubar'=>'insert edit view table tools',

	),
);