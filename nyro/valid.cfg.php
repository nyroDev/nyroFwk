<?php
$cfg = array(
	'label'=>REQUIRED,
	'value'=>REQUIRED,
	'rules'=>array(),
	'validEltArray'=>false,
	'noNeedRequired'=>array(
		'required',
		'groupedFields',
		'atLeastOneField'
	),
	'messages'=>array(
		'required'=>'%s is required.',
		'numeric'=>'%s should be numeric.',
		'int'=>'%s should be an integer.',
		'different'=>'%s should be different from %s.',
		'in'=>'The value %s is not allowed for %s.',
		'equalInput'=>'%s should be equal to %s.',
		'equal'=>'The value %s for %s is not the right one.',
		'minLength'=>'%s should be longer than %d characters.',
		'maxLength'=>'%s should be shorter than %d characters.',
		'url'=>'%s should be a valid URL.',
		'email'=>'%s should be a valid email address.',
		'dbUnique'=>'%s for %s already exists.',
		'dbExists'=>'%s for %s doesn\'t exists.',
		'groupedFields'=>'If %s is filled, you have to fill other(s) field(s).',
		'atLeastOneField'=>'%s should be filled.',
	),
);
