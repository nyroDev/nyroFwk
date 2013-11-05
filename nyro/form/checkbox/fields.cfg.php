<?php
$cfg = array(
	'table'=>REQUIRED,
	'fields'=>REQUIRED,
	'replaceKey'=>'REPLACEKEY',
	'sepViewSubValues'=>', ',
	'sepLabelViewSubValues'=>': ',
	'html'=>array(
		'global'=>'<ol class="mulValue checkboxes hasSubFields">[values]</ol>',
		'value'=>'<li class="checkbox_fields"><input type="checkbox" name="[name]" value="[value]" id="[id]-[value]" [plus]/><label for="[id]-[value]">[label]</label>[des]<div class="subFields">[fields]</div></li>',
	),
	'formOpts'=>array(
		'captcha'=>false,
		'html'=>array(
			'errorPos'=>'section',
			'global'=>'[content]',
			'line'=>'<li class="[classLine]"><label for="[id]">[label]</label>[field][des]</li>',

			'lineError'=>'<li class="lineError [classLine]"><label for="[id]">[label]</label>[field][errors][des]</li>',
			'lineErrorWrap'=>'<ol class="lineErrors">[errors]</ol>',
			'lineErrorLine'=>'<li>[error]</li>',

			'lineHidden'=>'<div style="display: none;">[label][field][des]</div>',
			'des'=>'<span>[des]</span>',
			'section'=>'<ol>[fields]</ol>',
			'sectionError'=>'<ol>[fields]</ol>',
		),
	),
);