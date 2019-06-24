<?php

//included from index.php



if (!isset($_POST["name"])){
    
    
    
    
    }





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
$queryAddAdmin = <<<EOD
INSERT INTO admins (
	`id` , `ip` , `http_user_agent`,username,password)
	VALUES 
	(NULL,:ip,:http_user_agent,:username,:password)
EOD;

try{
	$StatementHandle = $DBH->prepare($queryAddAdmin);
	//$StatementHandle->bindParam(':questionTable',$questionTable); CANNOT BIND TABLENAMES :((((
	$StatementHandle->bindParam(':ip',$ip);
   $StatementHandle->bindParam(':username',$username);
$StatementHandle->bindParam(':password',$password);
	$StatementHandle->bindParam(':http_user_agent',$http_user_agent);
	//$StatementHandle->bindParam(':time',time());
	$StatementHandle->execute();

// echo $queryAddAdmin;
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