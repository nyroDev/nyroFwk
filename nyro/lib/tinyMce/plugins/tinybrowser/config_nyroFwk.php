<?php
$tinybrowser['sessioncheck'] = 'dummySessionStuffforTinyBrowser';
$_SESSION[$tinybrowser['sessioncheck']] = true;

$tinybrowser['language'] = request::get('lang');

$tinybrowser['docroot'] = FILESROOT.'tinyBrowser';

if (isset($_GET['subdir']))
	$tinybrowser['docroot'].= '/'.$_GET['subdir'];

// File upload paths (set to absolute by default)
$tinybrowser['path']['image'] = '/images/'; // Image files location - also creates a '_thumbs' subdirectory within this path to hold the image thumbnails
$tinybrowser['path']['media'] = '/media/'; // Media files location
$tinybrowser['path']['file']  = '/'; // Other files location

$tinybrowser['thumbsrc'] = 'link';
// File link paths - these are the paths that get passed back to TinyMCE or your application (set to equal the upload path by default)
$tinybrowser['link']['image'] = request::uploadedUri('tinyBrowser/images/', array('controller'=>false)); //$tinybrowser['path']['image']; // Image links
$tinybrowser['link']['media'] = request::uploadedUri('tinyBrowser/media/', array('controller'=>false)); //$tinybrowser['path']['media']; // Media links
$tinybrowser['link']['file']  = request::uploadedUri('tinyBrowser/', array('controller'=>false)); //$tinybrowser['path']['file']; // Other file links

$tinybrowser['imagequality'] = 100;
$tinybrowser['thumbquality'] = 100;