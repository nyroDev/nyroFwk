<?php
$cfg = array(
	'html'=>array(
		'plus'=>null,
		'global'=>'<ol class="mulValue checkboxes">[values]</ol>',
		'globalInline'=>'<ol class="mulValue checkboxes inline">[values]</ol>',
		'value'=>'<li><input type="radio" name="[name]" value="[value]" id="[id]-[value]" [plus]/><label for="[id]-[value]">[label]</label>[des]</li>',
		'selected'=>' checked="checked"',
		'disabled'=>' disabled="disabled"',
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