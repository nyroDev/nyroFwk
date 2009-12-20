<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $response->getMeta('language'); ?>" lang="<?php echo $response->getMeta('language'); ?>">
<head>
	<?php echo $response->getHtmlElt(); ?>
</head>
<body>
	<?php echo $content; ?>
<?php echo $response->getHtmlElt('js'); ?>
</body>
</html>