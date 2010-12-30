<?php
$cfg = array(
	'table'=>REQUIRED,
	'all'=>'all',
	'listBool'=>array(
		'1'=>ucfirst(tr::__('yes')),
		'0'=>ucfirst(tr::__('no')),
		KEEPUNIQUE=>true,
	),
	'autoValidRule'=>array(
		'email', 'url',
	),
);