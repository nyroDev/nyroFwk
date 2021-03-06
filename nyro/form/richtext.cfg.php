<?php
$cfg = array(
	'nyroBrowser'=>array(
		'active'=>true,
		'config'=>'default',
		'url'=>request::uri('nyroBrowser'),
		'width'=>770,
		'height'=>480,
		'title'=>'Browser',
	),
	'tinyBrowser'=>array(
		'active'=>false,
		'url'=>str_replace('tinybrowser', 'tinybrowser/tinybrowser.php', request::uri('js/tiny_mce/plugins/tinybrowser')),
		'subdir'=>null,
		'width'=>770,
		'height'=>480,
		'title'=>'Browser',
	),
	'tinyMce'=>array(
		'script_url'=>request::uri('js/tiny_mce/tiny_mce_gzip.php'.(DEV ? null : '?diskcache=true')),
		'height'=>340,
		'theme'=>'advanced',
		'language'=>request::get('lang'),

		'plugins'=>'safari,pagebreak,style,layer,table,save,advhr,advimage,advlink,emotions,iespell,inlinepopups,insertdatetime,preview,media,searchreplace,print,contextmenu,paste,directionality,fullscreen,noneditable,visualchars,nonbreaking,xhtmlxtras,template',

		'theme_advanced_buttons1'=>'save,newdocument,|,bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,|,styleselect,formatselect,fontselect,fontsizeselect',
		'theme_advanced_buttons2'=>'cut,copy,paste,pastetext,pasteword,|,search,replace,|,bullist,numlist,|,outdent,indent,blockquote,|,undo,redo,|,link,unlink,anchor,image,cleanup,help,code,|,insertdate,inserttime,preview,|,forecolor,backcolor',
		'theme_advanced_buttons3'=>'tablecontrols,|,hr,removeformat,visualaid,|,sub,sup,|,charmap,emotions,iespell,media,advhr,|,print,|,ltr,rtl,|,fullscreen',
		'theme_advanced_buttons4'=>'insertlayer,moveforward,movebackward,absolute,|,styleprops,|,cite,abbr,acronym,del,ins,attribs,|,visualchars,nonbreaking,template,pagebreak',

		'theme_advanced_toolbar_location'=>'top',
		'theme_advanced_toolbar_align'=>'left',
		'theme_advanced_statusbar_location'=>'bottom',
		'theme_advanced_resizing'=>true,

		'content_css'=>'css/content.css',

		'template_external_list_url'=>'lists/template_list.js',
		'external_link_list_url'=>'lists/link_list.js',
		'external_image_list_url'=>'lists/image_list.js',
		'media_external_list_url'=>'lists/media_list.js',


		'plugins'=>'lists,safari,pagebreak,style,advimage,advlink,inlinepopups,media,searchreplace,contextmenu,paste,fullscreen,nonbreaking,xhtmlxtras',

		'theme_advanced_buttons1'=>'bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,|,cut,copy,paste,pastetext,pasteword,|,formatselect',
		'theme_advanced_buttons2'=>'search,replace,|,bullist,numlist,indent,outdent,|,undo,redo,|,link,unlink,anchor,image,media,|,fullscreen,code',
		'theme_advanced_buttons3'=>'',
		'theme_advanced_buttons4'=>'',

		'theme_advanced_toolbar_location'=>'top',
		'theme_advanced_toolbar_align'=>'left',
		'theme_advanced_statusbar_location'=>'bottom',
		'theme_advanced_resizing'=>true,

		'content_css'=>'',

		'template_external_list_url'=>'',
		'external_link_list_url'=>'',
		'external_image_list_url'=>'',
		'media_external_list_url'=>'',

		'relative_urls'=>false,
	),
);