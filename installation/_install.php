 <?php

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

// create new empty table inside the database (if table does not already exist)
$sql = <<<EOD
CREATE TABLE IF NOT EXISTS `user` (
        `user_id` INTEGER PRIMARY KEY,
        `user_ip` varchar(64) NOT NULL,
        `http_user_agent` varchar(512) NOT NULL,
        `user_name` varchar(64) NOT NULL,
        `user_password_hash` varchar(255) NOT NULL,
        `user_email` varchar(64));
        CREATE UNIQUE INDEX `user_name_UNIQUE` ON `users` (`user_name` ASC);
        CREATE UNIQUE INDEX `user_email_UNIQUE` ON `users` (`user_email` ASC);
        
        CREATE TABLE devices
        ('device_id' INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, 
        'device_name' text
        'device_description' TEXT,
         'device_ip' TEXT NOT NULL,
         'device_http_user_agent' TEXT NOT NULL, 
        'device_password' TEXT NOT NULL ,
        'device_type_id' INTEGER NOT NULL , 
        'device_status' TEXT,
         'time_last_uppdated' DATETIME DEFAULT CURRENT_TIMESTAMP,
         CREATE UNIQUE INDEX `user_name_UNIQUE` ON `users` (`user_name` ASC);
        CREATE UNIQUE INDEX `user_email_UNIQUE` ON `users` (`user_email` ASC);
        ); 
EOD;

// execute the above query
$query = $db_connection->prepare($sql);
$query->execute();

// check for success
if (file_exists($db_sqlite_path)) {
    echo "Database $db_sqlite_path was created, installation was successful.";
} else {
    echo "Database $db_sqlite_path was not created, installation was NOT successful. Missing folder write rights ?";
}
