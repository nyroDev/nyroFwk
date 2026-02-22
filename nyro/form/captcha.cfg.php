<?php
$cfg = array(
	'label'=>'Don\'t fill this field (anti-robot feature)',
	'error'=>'It looks like you\'re a robot!',
	'errorFct'=>function() { response::getInstance()->redirect(request::uri("/")); },
	'classLine'=>'nyroTcha',
	'html'=>array(
		'class'=>'captcha text',
	),
);