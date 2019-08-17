<?php
define("DS", DIRECTORY_SEPARATOR);
define("PROJ", realpath(dirname(__FILE__)) . DS);
require_once(PROJ . "res" . DS . "utils.php");
$db = getGLOB_DatabaseConnection();

$res = $db->query("PRAGMA table_info(device);");
$r = $res->fetchAll(PDO::FETCH_COLUMN, 1);
print_me($r);





?>