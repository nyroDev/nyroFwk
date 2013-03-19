<?php
$cfg = array(
	'mode'=>'edit',

	'action'=>request::get('localUri'),
	'formPlus'=>null,
	'method'=>'post',
	'sectionName'=>'Form',
	'showSection'=>true,
	'outSection'=>true,
	'submitText'=>'Send',
	'submitPlus'=>null,

	'notValue'=>array(),

	'forceOnlyOneLang'=>false,
	'noForceLang'=>false,

	'sepLabel'=>': ',
	'emptyLabel'=>'&nbsp;',
	'requiredMoreLabel'=>'',

	'captcha'=>array(
		'name'=>'nyroTcha',
		'type'=>'captcha',
	),

	'html'=>array(
		'errorPos'=>'section',
		'global'=>'<form [plus]>
	[hidden]
	[errors]
	[content]
	[submit]
</form>',
		'line'=>'<li class="[classLine]"><label for="[id]">[label]</label>[field][des]</li>',

		'lineError'=>'<li class="lineError [classLine]"><label for="[id]" class="label">[label]</label>[field][errors][des]</li>',
		'lineErrorWrap'=>'<ol class="lineErrors">[errors]</ol>',
		'lineErrorLine'=>'<li>[error]</li>',

		'lineHidden'=>'<div style="display: none;">[label][field][des]</div>',
		'des'=>'<span>[des]</span>',
		'section'=>'<fieldset>
	<legend><span>[label]</span></legend>
	<ol class="first">
	[fields]
	</ol>
</fieldset>',
		'sectionError'=>'<fieldset class="error">
	<legend><span>[label]</span></legend>
	<ol class="sectionErrors first">[errors]</ol>
	<ol>
	[fields]
	</ol>
</fieldset>',
		'sectionErrorLine'=>'<li>[error]</li>',
		'submit'=>'<fieldset class="submit"><input type="submit" value="[submitText]" />[submitPlus]</fieldset>',

		'globalError'=>'<ol class="globalErrors">[errors]</ol>',
		'globalErrorLine'=>'<li>[error]</li>',

		'incFiles'=>array(
			array('type'=>'css', 'file'=>'form'),
			array('type'=>'css', 'file'=>'form-ie', 'condIE'=>'IE'),
		),
	),

	'htmlNoSection'=>array(
		'global'=>'<form [plus]>
[hidden]
<table>
	[content]
	[submit]
</table>
</form>',
		'line'=>'<tr>
	<th><label for="[id]">[label]</label></th>
	<td>[field][des]</td>
</tr>',
		'lineHidden'=>'[field]',
		'des'=>'<br />[des]',
		'section'=>'[fields]',
		'submit'=>'<tr><td></td><td><input type="submit" value="[submitText]" />[submitPlus]</td></tr>',
	),
	'htmlNoSectionView'=>array(
		'noHidden'=>true,
		'global'=>'
<table>
	[content]
</table>
',
		'line'=>'<tr>
	<th>[label]</th>
	<td>[field][des]</td>
</tr>',),



	'xul'=>array(
		'global'=>'
	[content]
	[submit]
',
		'line'=>'<label control="[id]">[label]</label>[field]',
		'lineHidden'=>'[field]',
		'des'=>'<description>[des]</description>',
		'section'=>'[fields]',
		'submit'=>'<input type="submit" value="[submitText]" />',
		'incFiles'=>array(
			array('type'=>'css', 'file'=>'form'),
			array('type'=>'css', 'file'=>'form-ie', 'condIE'=>'IE'),
		),
	),

);