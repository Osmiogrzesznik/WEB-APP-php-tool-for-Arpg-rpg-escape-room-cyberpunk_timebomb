<?php
 $a = array("a","b","c");


 $b = array_fill(0,count($a),"?");

 echo implode(",",$b);   


?>