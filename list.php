<h1>Available Controllers</h1><ul><?php foreach(glob('www/*.php') as $f):?>	<?php	$tmp = explode('/', $f);	$f = $tmp[1] != 'index.php' ? $tmp[1] : './';	?>	<li><a href="<?php echo $f ?>"><?php echo $tmp[1] ?></a><?php endforeach; ?></ul>