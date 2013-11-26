<?php
$cfg = array(
	'layout'=>'layout',
	'ajaxLayout'=>null,

	'charset'=>'utf-8',

	'status'=>'200',
	'compress'=>true,

	'attributes'=>array(
		'globalCache'=>true
	),
	
	'headers'=>array(
		'X-Powered-By'=>'nyroFwk '.NYROVERSION,
		'Content-Language'=>request::get('lang'),
		'Connection'=>'Keep-Alive'
	),
	
	'sendFileHeader'=>'X-Accel-Redirect',
	'sendFile'=>array(
		// 'sourceDir'=>'destDir'
	),
	'saveSendText'=>array(),

	'contentTypeCfg'=>array(
		'text'=>'text/plain',
		'js'=>'application/javascript',
		'json'=>'application/json',
		'xul'=>'application/vnd.mozilla.xul+xml',
	),

	'statusCfg'=>array(
		'100'=>'Continue',
		'101'=>'Switching Protocols',
		'200'=>'OK',
		'201'=>'Created',
		'202'=>'Accepted',
		'203'=>'Non-Authoritative Information',
		'204'=>'No Content',
		'205'=>'Reset Content',
		'206'=>'Partial Content',
		'300'=>'Multiple Choices',
		'301'=>'Moved Permanently',
		'302'=>'Found',
		'303'=>'See Other',
		'304'=>'Not Modified',
		'305'=>'Use Proxy',
		'307'=>'Temporary Redirect',
		'400'=>'Bad Request',
		'401'=>'Unauthorized',
		'402'=>'Payment Required',
		'403'=>'Forbidden',
		'404'=>'Not Found',
		'405'=>'Method Not Allowed',
		'406'=>'Not Acceptable',
		'407'=>'Proxy Authentication Required',
		'408'=>'Request Timeout',
		'409'=>'Conflict',
		'410'=>'Gone',
		'411'=>'Length Required',
		'412'=>'Precondition Failed',
		'413'=>'Request Entity Too Large',
		'414'=>'Request-URI Too Long',
		'415'=>'Unsupported Media Type',
		'416'=>'Requested Range Not Satisfiable',
		'417'=>'Expectation Failed',
		'500'=>'Internal Server Error',
		'501'=>'Not Implemented',
		'502'=>'Bad Gateway',
		'503'=>'Service Unavailable',
		'504'=>'Gateway Timeout',
		'505'=>'HTTP Version Not Supported',
	),
);