<?php
$cfg = array(
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
		'plugins'=>'advlist,autolink,link,image,lists,charmap,print,preview,hr,anchor,pagebreak,searchreplace,wordcount,visualblocks,visualchars,code,fullscreen,insertdatetime,media,nonbreaking,save,table,contextmenu,directionality,template,paste,textcolor',
		'toolbar'=>'insertfile undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image media fullpage',
		'relative_urls'=>false,

	),
);