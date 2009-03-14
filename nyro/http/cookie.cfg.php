<?php
$cfg = array(
	'name'=>REQUIRED,
	'value'=>null,
	'expire'=>2592000,
	'path'=>request::get('path'),
	'domain'=>null,
	'secure'=>false,
	'autoSave'=>true,
	'prefix'=>null,
);