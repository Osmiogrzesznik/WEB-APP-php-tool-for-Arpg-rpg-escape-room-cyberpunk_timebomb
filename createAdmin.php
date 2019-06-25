<?php

//included from index.php
if (
    isset($_POST["username"]) && 
    isset($_POST["password"])
        ){

    $password = $_POST["password"];
    $username = $_POST["username"];
 //   jest windexie juz $http_user_agent ip i time
    


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
	`id` , `ip` , `http_user_agent`,username,password)
	VALUES 
	(NULL,:ip,:http_user_agent,:username,:password)
EOD;

try{
  
	
	//$DBH->query("CREATE TABLE IF NOT EXISTS `admins` ( `id` INT NOT NULL , `ip` TEXT NOT NULL , `http_user_agent` TEXT NOT NULL , ) ENGINE = InnoDB;")
	if (!empty($DBH)){
		include("DBAndIPtools.php");
	}
    
    
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
echo "Thank You !!! Admin added $username added Successfully ";
include("showBombDevices.php");
//todo colect data like ip and date and so on
//todo add rows to tables

}
catch(PDOException $e) {
    $m = "\nSorry, database error occured :".$e->getMessage().$ip." ".$info;
	file_put_contents('PDOErrors.txt', $m, FILE_APPEND);
	echo $m;
}






$DBH = null;
}
else{
echo "sorry , you are either not logged in or cannot leave username or password blank";
}




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
<pre>
Co w przypadku gdy Admin sie zalogowal i odswiezyl strone ? nie dostanie sie teraz.

Jesli client z adresu zarejestrowanej bomby wchodzi na dodaj admina zablokuj,
przeciez kazdy mogl w ten sposob obejsc system. Index.php dodaje ten modul do strony tylko jesli jest 0 adminow.
</pre>
<br>
<form action="createAdmin.php" method="POST">
<input name="username" placeholder="username">
<input name="password" placeholder="password">
<input type="submit">

</form>

	<script>document.write(~~(Date.now()/1000))</script>
</body>
</html>