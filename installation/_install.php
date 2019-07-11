 <?php
$installation_sql_file = "install.sql";
$installation_sql_file2 = "install2.sql";
/**
 * This is the installation file for the 0-one-file version of the php-login script.
 * It simply creates a new and empty database.
 */

// error reporting config
error_reporting(E_ALL);

// config
$db_type = "sqlite";
$db_sqlite_path = "../rafka_timebomb.sqlite";

// create new database file / connection (the file will be automatically created the first time a connection is made up)
$db_connection = new PDO($db_type . ':' . $db_sqlite_path);
//make errors explicit to figure out wtf
try {
	$db_connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
	echo "\nSorry Bo , opening db went wrong- " . $e->getMessage() . $ip . " " . $info;
	file_put_contents('PDOErrors.txt', $e->getMessage(), FILE_APPEND);
};

// create new empty table inside the database (if table does not already exist)
$sql = file_get_contents($installation_sql_file);
if ($sql === false){
    echo "problem reading from file $installation_sql_file";
}

$queries = explode(";", $sql);
    foreach ($queries as $query) {
        $db_connection->query($query);
        echo "<h6>$query<h6><h4>executed OK</h4>";
    }

echo "<h4> all Done!!!</h4>";


//$sql = file_get_contents($installation_sql_file2);
//if ($sql === false){
//    echo "problem reading from file $installation_sql_file2";
//}

// execute the above query
//$query = $db_connection->prepare($sql);
//$query->execute();
//echo "2 ok";

// check for success
if (file_exists($db_sqlite_path)) {
    echo "Database $db_sqlite_path was created, installation was successful.";
} else {
    echo "Database $db_sqlite_path was not created, installation was NOT successful. Missing folder write rights ?";
}
