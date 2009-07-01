<?php if (security::getInstance()->isLogged()): ?>
	<ul id="menu">
	<?php
	foreach($links as $l=>$name) {
		if ($name == 'hr')
			echo '<li class="hr"><span></span></li>';
		else
			echo '<li><a href="'.$l.'">'.$name.'</a></li>';
	}
	if (!empty($linksTable)) {
		foreach($linksTable as $name=>$l)
			echo '<li><a href="'.$l.'">'.$name.'</a></li>';
		echo '<li class="hr"><span></span></li>';
	}
	?>
		<li><a href="<?php echo security::getInstance()->getPage('logout', true) ?>">D&eacute;connexion</a></li>
	</ul>
<?php endif; ?>