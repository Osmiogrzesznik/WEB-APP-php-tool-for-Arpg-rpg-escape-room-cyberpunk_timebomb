<?php

function getRowCount($DBH,$tablename){

$qry= <<<EOD
select COUNT(*) FROM $tablename
EOD;

$countrows = $DBH->query($qry);

$RowCount = $countrows->fetchColumn();

return $RowCount  ;
}

?>