<?php
foreach($this->vars as $k=>$v) {
	if ($k != 'response')
		echo '<'.$k.'>'.$v.'</'.$k.'>';
}
?>