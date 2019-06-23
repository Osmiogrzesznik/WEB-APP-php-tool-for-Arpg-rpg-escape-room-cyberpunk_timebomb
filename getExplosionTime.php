
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta http-equiv="X-UA-Compatible" content="ie=edge">
	<title>Document</title>
</head>
<body>
<pre>
amount of client devices/ each IP becomes ID - so instead of currently active users each client opening the link joins the game,
how
It is possible to implement admin stopping all clocks just before 
deadline, clients need just to poll server 10 seconds before it
and if the status changed stop clock on value set by admin (less than 10sec before explosion) 

AdminUser opens the admin and sets the time
store AdminIP, setExplosionTime, password(s)
//TODO store BombNames(for identification)
show Bombs statuses (active,refreshed)
for each Clientuser that opens the client page
	website opens with php 
		instantly checking activity status
		if clientIP is in Bombs 
			client just refreshed 
			store new refreshedTime in Bombs
			send appropriate settings and continue with stuff
		
		else if clientIP == AdminIP DO nothing //he checks the status
		else 
			//TODO ask for ADMINpassword redirect or include() 
			//TODO ifNot just redirect to different page with clock
			//TODO	that displays if all bombs are deactivated
			//TODO		instead of clock allow to register bomb
			//TODO 		with its name and password
			store add ClientIP to Bombs //TODO only if admin logged in and set it up to be a bomb
			store 


CREATE TABLE IF NOT EXISTS `rafka_timebomb`.`admins` ( `id` INT NOT NULL , `ip` INT NOT NULL , `http_user_agent` INT NOT NULL ) ENGINE = InnoDB;

<pre>
<?php
$DBH = new PDO("sqlite:rafka_timebomb.sqlite");

$createAdmins = "CREATE TABLE IF NOT EXISTS `admins` ( `id` INT Primary key AUTO_INCREMENT , `ip` TEXT NOT NULL , `http_user_agent` TEXT NOT NULL ) ENGINE = InnoDB;";
$DBH->exec($createAdmins);

// $dsn = 'mysql:dbname=rafka_timebomb;host=127.0.0.1';
// $user = 'root';
// $password = '';
// $DBH = new PDO($dsn, $user, $password);


$time = time();
if(!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
} else {
    $ip = $_SERVER['REMOTE_ADDR'];
}
$http_user_agent = getenv('HTTP_USER_AGENT');
//$referrer = getenv('HTTP_REFERER');
$info = $time . " USRAGT: " . $http_user_agent . " IP: " .$ip;


try {

	// # MS SQL Server and Sybase with PDO_DBLIB
	// $DBH = new PDO("mssql:host=$host;dbname=$dbname, $user, $pass");
	// $DBH = new PDO("sybase:host=$host;dbname=$dbname, $user, $pass");
   
	// # MySQL with PDO_MYSQL
	// $DBH = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
   
	// # SQLite Database
	//$db= new sqlite3("StudRepSurveyDB") or die("cannot open db");
	
	
$DBH->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
  }
  catch(PDOException $e) {
	echo "\nSorry Bo , opening db went wrong- ".$e->getMessage().$ip." ".$info;
    file_put_contents('PDOErrors.txt', $e->getMessage(), FILE_APPEND);
  }

// $data = json_decode(file_get_contents("php://input"),true);

// $questionTable = "Answers_".$data["questionId"];
// $nickname = $data["nickname"];
// $answertext = $data["answertext"];
$queryAddAnswer = <<<EOD
INSERT INTO admins (
	`id` , `ip` , `http_user_agent`)
	VALUES 
	(NULL,:ip,:http_user_agent)
EOD;

try{
	$StatementHandle = $DBH->prepare($queryAddAnswer);
	//$StatementHandle->bindParam(':questionTable',$questionTable); CANNOT BIND TABLENAMES :((((
	$StatementHandle->bindParam(':ip',$ip);
	$StatementHandle->bindParam(':http_user_agent',$http_user_agent);
	//$StatementHandle->bindParam(':time',time());
	$StatementHandle->execute();

// echo $queryAddAnswer;
// echo "\n".$ip;
echo "Thank You !!! Admin added Successfully $info";
//todo colect data like ip and date and so on
//todo add rows to tables

}
catch(PDOException $e) {
    $m = "\nSorry Bo - statement went bubu ".$e->getMessage().$ip." ".$info;
	file_put_contents('PDOErrors.txt', $m, FILE_APPEND);
	echo $m;
}






$DBH = null;

exit();
date_default_timezone_set('Europe/London');
//$whenExplosion = date('Y-m-dTh:i:s');
// $date = DateTime::createFromFormat('j-M-Y', '15-Feb-2009');
// echo $date->format('Y-m-d');

//comes from client - and later db
$minutesSet = 2;
$secondsSet = 30;
//this is stored in db
$explosionTimestamp = time() + $minutesSet * 60 + $secondsSet;


$arr = array(
	"explosionTimestamp" => $explosionTimestamp,
	"asDateString" =>  date('Y-m-dTh:i:s',$explosionTimestamp),
	"nowTimestamp" => time(),
	"nowAsDateString" =>  date('Y-m-dTh:i:s',time())
);
$response = json_encode($arr);
echo $response;

?>

<br>
	<script>document.write(~~(Date.now()/1000))</script>
</body>
</html>