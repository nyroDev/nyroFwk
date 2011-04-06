<?php
$cfg = array(
	'path'=>TMPROOT.'cache'.DS.'file'.DS,
	'tags'=>array(),
	'request'=>array(),
	'serialize'=>true,
	'startFile'=>array(
		NYRONAME,
		NYROENV,
		request::get('lang'),
		request::get('out'),
	)
);