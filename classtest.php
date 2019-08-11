<?php
define("DS", DIRECTORY_SEPARATOR);
define("PROJ", realpath(dirname(__FILE__)) . DS);
require(PROJ . "res" . DS . "utils.php");



/**
 * Class OneFileLoginApplication
 *
 * An entire php application with user registration, login and logout in one file.
 * Uses very modern password hashing via the PHP 5.5 password hashing functions.
 * This project includes a compatibility file to make these functions available in PHP 5.3.7+ and PHP 5.4+.
 *
 * @author Panique
 * @link https://github.com/panique/php-login-one-file/
 * @license http://opensource.org/licenses/MIT MIT License
 */
class OneFileLoginApplication
{
    /**
     * @var string Type of used database (currently only SQLite, but feel free to expand this with mysql etc)
     */
    private $db_type = "sqlite"; //

    /**
     * @var string Path of the database file (create this with _install.php)
     */
    private $db_sqlite_path = "./rafka_timebomb.sqlite";

    /**
     * @var object Database connection
     */
    private $db_connection = null;

    /**
     * @var string System messages, likes errors, notices, etc.
     */
    public $feedback = "";

    
    /**
     * Does necessary checks for PHP version and PHP password compatibility library and runs the application
     */
    public function __construct()
    {
        $this->script_start_time = time();
        $this->scriptName = $_SERVER['SCRIPT_NAME']; //TODO: globalreplace to property scriptName
        $this->feedback = "";
    }

    public function addFeedback($msg)
    {
        $this->feedback .= $msg . "\n";
    }

    /**
     * This is basically the controller that handles the entire flow of the application.
     */
    public function runApplication()
    {
            $deviceObject = new Device();
            // $tableReady = $deviceObject->getTableReadyAllMadeByUser(1);
            // print_me($tableReady);
            
            $all = $deviceObject->getAllByUser(1);
            print_me($all);

        echo $this->feedback;
    }

    /**
     * Creates a PDO database connection (in this case to a SQLite flat-file database)
     * @return bool Database creation success status, false by default
     */
    private function getDatabaseConnection()
    {
        return GLOB_getDatabaseConnection();
    }

}

// run the application
$application = new OneFileLoginApplication();
    $application->runApplication();
