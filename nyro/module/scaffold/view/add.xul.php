<?php
if ($form->hasErrors()) {
	echo debug::trace($form->getErrors());
}
?>
<?php echo $form; ?><br />
<a href="<?php echo $indexPage; ?>"><?php tr::__('scaffold_back', 1) ?></a>