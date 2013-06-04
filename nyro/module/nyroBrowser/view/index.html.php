<?php echo $form ?><br />
<fieldset id="files" class="<?php echo $type ?>">
	<legend><?php echo $filesTitle ?></legend>
	<form action="<?php echo $uri ?>" method="get">
		<input type="hidden" name="config" value="<?php echo $config ?>" />
		<input type="hidden" name="type" value="<?php echo $type ?>" />
		<input type="text" name="search" value="<?php echo $search ?>" size="25" />
		<input type="submit" value="<?php echo $searchButton ?>" />
	</form>
	<br />
	<?php if (count($files)): ?>
		<?php if ($type == 'image'): ?>
		<ul>
			<?php foreach($files as $f): ?>
			<li>
				<a href="<?php echo $f[1] ?>" title="<?php echo $f[3].', '.$f[4] ?>" <?php
				if (isset($f[6]) && is_array($f[6]))
					echo 'data-width="'.$f[6][0].'" data-height="'.$f[6][1].'"'
				?>>
					<?php echo $imgHelper->view($f[0]) ?><br />
					<?php echo $f[2] ?>
				</a>
				<a href="<?php echo $f[5] ?>" class="delete"><?php echo $delete ?></a>
			</li>
			<?php endforeach; ?>
		</ul>
		<?php else: ?>
		<table>
			<tr>
				<th><?php echo $name ?></th>
				<th class="size"><?php echo $size ?></th>
				<th class="date"><?php echo $date ?></th>
			</tr>
			<?php foreach($files as $f): ?>
			<tr>
				<td>
					<a href="<?php echo $f[1] ?>" title="<?php echo $f[3].', '.$f[4] ?>"><?php echo $f[2] ?></a>
					<a href="<?php echo $f[5] ?>" class="delete"><?php echo $delete ?></a>
				</td>
				<td class="size"><?php echo $f[3] ?></td>
				<td class="date"><?php echo $f[4] ?></td>
			</tr>
			<?php endforeach; ?>
		</table>
		<?php endif; ?>
	<?php else: ?>
		<p><?php echo $noFiles ?></p>
	<?php endif; ?>
</fieldset>
