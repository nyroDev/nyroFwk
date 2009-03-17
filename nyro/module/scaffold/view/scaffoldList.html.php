<?php
echo '<a href="'.$addPage.'">'.utils::getIcon(array('name'=>'add','type'=>$iconType)).' '.tr::__('scaffold_add').'</a><br />';
echo $filterTable;
echo $dataTable;
?>