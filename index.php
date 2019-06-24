<?php

$DBH = new PDO("sqlite:rafka_timebomb.sqlite");
try {
	$DBH->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
	echo "\nSorry Bo , opening db went wrong- " . $e->getMessage() . $ip . " " . $info;
	file_put_contents('PDOErrors.txt', $e->getMessage(), FILE_APPEND);
};

include("DBtools.php");

//$createAdmins = "CREATE TABLE IF NOT EXISTS `admins` ( `id` INT Primary key AUTO_INCREMENT , `ip` TEXT NOT NULL , `http_user_agent` TEXT NOT NULL ) ENGINE = InnoDB;";
//$DBH->exec($createAdmins);

// $dsn = 'mysql:dbname=rafka_timebomb;host=127.0.0.1';
// $user = 'root';
// $password = '';
// $DBH = new PDO($dsn, $user, $password);


$time = time();
if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
	$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
} else {
	$ip = $_SERVER['REMOTE_ADDR'];
}
$http_user_agent = getenv('HTTP_USER_AGENT');
//$referrer = getenv('HTTP_REFERER');
$info = $time . " USRAGT: " . $http_user_agent . " IP: " . $ip;



// # MS SQL Server and Sybase with PDO_DBLIB
// $DBH = new PDO("mssql:host=$host;dbname=$dbname, $user, $pass");
// $DBH = new PDO("sybase:host=$host;dbname=$dbname, $user, $pass");

// # MySQL with PDO_MYSQL
// $DBH = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);

// # SQLite Database
//$db= new sqlite3("StudRepSurveyDB") or die("cannot open db");



// $data = json_decode(file_get_contents("php://input"),true);

// $questionTable = "Answers_".$data["questionId"];
// $nickname = $data["nickname"];
// $answertext = $data["answertext"];



$queryAddAdmin = <<<EOD
INSERT INTO admins (
	`id` , `ip` , `http_user_agent`)
	VALUES 
	(NULL,:ip,:http_user_agent)
EOD;


//exit();
if ($AdminsCount == 0) {
	include("createAdmin.php");
}

try {
	$StatementHandle = $DBH->prepare($queryAddAdmin);
	//$StatementHandle->bindParam(':questionTable',$questionTable); CANNOT BIND TABLENAMES :((((
	$StatementHandle->bindParam(':ip', $ip);
	$StatementHandle->bindParam(':http_user_agent', $http_user_agent);
	//$StatementHandle->bindParam(':time',time());
	$StatementHandle->execute();

	// echo $queryAddAnswer;
	// echo "\n".$ip;
	echo "Thank You !!! Admin added Successfully $info";
	//todo colect data like ip and date and so on
	//todo add rows to tables

} catch (PDOException $e) {
	$m = "\nSorry Bo - statement went bubu " . $e->getMessage() . $ip . " " . $info;
	file_put_contents('PDOErrors.txt', $m, FILE_APPEND);
	echo $m;
}






$DBH = null;

exit();


?>
ok