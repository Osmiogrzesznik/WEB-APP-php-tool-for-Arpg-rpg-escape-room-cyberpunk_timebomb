<?php

$DBH = new PDO("sqlite:rafka_timebomb.sqlite");
try {
	$DBH->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
	echo "\nSorry Bo , opening db went wrong- " . $e->getMessage() . $ip . " " . $info;
	file_put_contents('PDOErrors.txt', $e->getMessage(), FILE_APPEND);
};

$time = time();
if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
	$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
} else {
	$ip = $_SERVER['REMOTE_ADDR'];
}
$http_user_agent = getenv('HTTP_USER_AGENT');
//$referrer = getenv('HTTP_REFERER');
$info = $time ." IP: " . $ip . " USRAGT: " . $http_user_agent;
file_put_contents('visitors.txt', $info, FILE_APPEND);

function getRowCount($DBH,$tablename){

$qry= <<<EOD
select COUNT(*) FROM $tablename
EOD;

$countrows = $DBH->query($qry);

$RowCount = $countrows->fetchColumn();

return $RowCount  ;
}

?>