<?php
$cfg = array(
	'path'=>TMPROOT.'cache'.DS.'file'.DS,
	'tags'=>array(),
	'request'=>array(),
	'serialize'=>true,
	'startFile'=>array(
		NYRONAME,
		NYROENV,
		'-REQUEST::LANG-',
		'-REQUEST::OUT-',
	)
);