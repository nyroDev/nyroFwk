<?php
$cfg = array(
	'stop'=>DEV ? true : array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR),
	'errors'=> array(
		E_ERROR=>'Error',
		E_PARSE=>'Parse',
		E_CORE_ERROR=>'Core Error',
		E_COMPILE_ERROR=>'Compile Error',
		E_USER_ERROR=>'User Error',

		E_COMPILE_WARNING=>'Compile Warning',
		E_CORE_WARNING=>'Core Warning',
		E_NOTICE=>'Notice',
		E_RECOVERABLE_ERROR=>'Recoverable Error',
		E_STRICT=>'Strict',
		E_USER_NOTICE=>'User Notice',
		E_USER_WARNING=>'User Warning',
		E_WARNING=>'Warning',
	)
);