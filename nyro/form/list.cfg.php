<?php
$cfg = array(
	'htmlTagName'=>'select',

	'html'=>array(
		'plus'=>'id="[name]"',
		'global'=>'<select name="[name]" [plus]>[values]</select>',
		'value'=>'<option value="[value]" [plus]>[label]</option>',
		'selected'=>' selected="selected"',
		'group'=>'<optgroup label="[label]">[group]</optgroup>',
	),
	'xul'=>array(
		'plus'=>null,
		'global'=>'<select id="[name]" [plus]>[values]</select>',
		'value'=>'<option value="[value]" [plus]>[label]</option>',
		'selected'=>' selected="selected"',
		'group'=>'<optgroup label="[label]">[group]</optgroup>',
	),
);