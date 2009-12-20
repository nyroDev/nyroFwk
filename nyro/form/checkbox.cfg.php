<?php
$cfg = array(
	'uniqValue'=>false,

	'html'=>array(
		'plus'=>null,
		'global'=>'<ol class="mulValue checkboxes">[values]</ol>',
		'value'=>'<li><input type="checkbox" name="[name]" value="[value]" id="[id]-[value]" [plus]/><label for="[id]-[value]">[label]</label></li>',
		'selected'=>' checked="checked"',
		'group'=>'<li><em>[label]</em></li>[group]',
	),
	'xul'=>array(
		'plus'=>null,
		'global'=>'<select id="[name]" [plus]>[values]</select>',
		'value'=>'<option value="[value]" [plus]>[label]</option>',
		'selected'=>' selected="selected"',
		'group'=>'<optgroup label="[label]">[group]</optgroup>',
	),
);