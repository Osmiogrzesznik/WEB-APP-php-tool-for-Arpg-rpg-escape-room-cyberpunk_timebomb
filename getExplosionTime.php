
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
<pre>
<?php




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