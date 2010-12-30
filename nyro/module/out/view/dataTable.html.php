<?php if(!empty($list)):?>
	<table>
		<thead>
			<tr>
				<?php
				foreach($headers as $h) {
					if ($h['url']) {
						if ($h['name'] == $sortBy || $tblName.'.'.$h['name'] == $sortBy) {
							echo '<th>
								<a href="'.$h['url'].'">'.$h['label'].'</a>
								'.($sortDir == 'asc' ? $sortIndicatorAsc : $sortIndicatorDesc).'
								</th>';
						} else
							echo '<th><a href="'.$h['url'].'">'.$h['label'].'</a></th>';
					} else
						echo '<th>'.$h['label'].'</th>';
					if ($h['type'] == 'image')
						$imgHelper = factory::getHelper('image', array(
							'w'=>50,
							'h'=>50
						));
				}
				if ($actions)
					echo '<th>Actions</th>';
				?>
			</tr>
		</thead>
		<tbody>
			<?php
			$i = 0;
			foreach($list as $l) {
				echo '<tr>';
				foreach($headers as $h) {
					$val = $l->get($h['name'], 'flatReal');
					switch($h['type']) {
						case 'date':
							$val = $val ? utils::formatDate($val) : $val;
							break;
						case 'datetime':
						case 'timestamp':
							$val = $val ? utils::formatDate($val, 'datetime') : $val;
							break;
						case 'image':
							$val = $val ? $imgHelper->view($val) : $val;
							break;
						case 'tinyint':
							$val = ucfirst(tr::__($val ? 'yes' : 'no'));
							break;
					}
					echo '<td>'.(is_array($val)? implode(', ', $val) : $val).'</td>';
				}
				if ($actions) {
					echo '<td>';
					if (array_key_exists($i, $actions))
						foreach($actions[$i] as $a=>$v) {
							$img = $actionsImg[$a];
							echo '<a href="'.$v.'" class="'.$a.'">'.($img? $img : $a).'</a> ';
						}
					echo '</td>';
				}
				echo '</tr>';
				$i++;
			}
			?>
		</tbody>
	</table>
	<?php
	if ($nbPage > 1) {
		/*
		foreach($pageLinks as $i=>$l) {
			if ($i == $currentPage)
				echo '<strong>'.$i.'</strong>';
			else
				echo '<a href="'.$l.'">'.$i.'</a>';
			if ($i < $nbPage)
				echo ' - ';
		}
		// */
		//*
		echo 'Pages : <select onchange="javascript:location.href=this.value">';
		foreach($pageLinks as $i=>$l) {
			if ($i == $currentPage)
				echo '<option value="'.$l.'" selected="selected">'.$i.'</option>';
			else
				echo '<option value="'.$l.'">'.$i.'</option>';
		}
		echo '</select>';
		// */
	}
	?>
<?php else: ?>
	<?php echo $noData ?>
<?php endif; ?>