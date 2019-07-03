<?php

// constants to determine if device just logged in or was active on this device
define("JUST_LOGGING_IN",2);
define("LOGGED_WITH_SESSION",3);
define("DEBUG_MODE",0);
/**
 * Class OneFileLoginApplication
 *
 * An entire php application with device registration, login and logout in one file.
 * Uses very modern password hashing via the PHP 5.5 password hashing functions.
 * This project includes a compatibility file to make these functions available in PHP 5.3.7+ and PHP 5.4+.
 *
 * @author Panique
 * @link https://github.com/panique/php-login-one-file/
 * @license http://opensource.org/licenses/MIT MIT License
 */
class RegisteredDeviceSettingsProvider
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
     * @var bool Login status of device
     */
    private $device_is_logged_in = false;

    /**
     * @var string System messages, likes errors, notices, etc.
     */
    public $feedback = "";

    /**
     * time when device accessed page
     *
     * @var int
     */
    public $time = 0; 
     /**
      * ip of device that just accessed page
      *
      * @var string
      */
     public $ip = null; 
     /**
      * device agent of device that just accessed page
      *
      * @var string
      */
     public $http_device_agent = null;

     /**
      * status of the way device just logged in
      * whether he is session active or just logged on a new device
      * @var int
      */
     public $device_logged_with = 0;

    /**
     * Does necessary checks for PHP version and PHP password compatibility library and runs the application
     */
    public function __construct()
    {
        $this->time = time();
        if ($this->performMinimumRequirementsCheck()) {
            $this->runApplication();
        }
    }

    /**
     * Performs a check for minimum requirements to run this application.
     * Does not run the further application when PHP version is lower than 5.3.7
     * Does include the PHP password compatibility library when PHP version lower than 5.5.0
     * (this library adds the PHP 5.5 password hashing functions to older versions of PHP)
     * @return bool Success status of minimum requirements check, default is false
     */
    private function performMinimumRequirementsCheck()
    {
        if (version_compare(PHP_VERSION, '5.3.7', '<')) {
            echo "Sorry, Simple PHP Login does not run on a PHP version older than 5.3.7 !";
        } elseif (version_compare(PHP_VERSION, '5.5.0', '<')) {
            require_once("libraries/password_compatibility_library.php");
            return true;
        } elseif (version_compare(PHP_VERSION, '5.5.0', '>=')) {
            return true;
        }
        // default return
        return false;
    }

    /**
    * 
    *gets Ip of the device or fakedIP from get/post params
    *lazy getter sets ip only if it's not set yet.
    *    @var $getFaked bool should ip be set from get params
    *    @return string ip
    */
    
    public function getIP($getFaked=DEBUG_MODE){
       
        if (isset($this->ip)){
       return $this->ip;
    }
            
       if($getFaked  &&  isset($_GET["ip"])) {
        $this->ip = $_GET["ip"];
        return $this->ip;
        }
       else if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $this->ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $this->ip = $_SERVER['REMOTE_ADDR'];
        }
        return $this->ip;
        }
        
  public function checkIsRegisteredDeviceAndLogin(){
//IF ip is in database display bomb/device interface with time counting down
if($this->createDatabaseConnection()){
        $sql = 'SELECT *
                FROM device
                WHERE device_ip = :connection_ip;
                ';
        $query = $this->db_connection->prepare($sql);
        $query->bindValue(':connection_ip', $this-ip);
        $query->execute();

        // Btw that's the weird way to get num_rows in PDO with SQLite:
        // if (count($query->fetchAll(PDO::FETCH_NUM)) == 1) {
        // Holy! But that's how it is. $result->numRows() works with SQLite pure, but not with SQLite PDO.
        // This is so crappy, but that's how PDO works.
        // As there is no numRows() in SQLite/PDO (!!) we have to do it this way:
        // If you meet the inventor of PDO, punch him. Seriously.
        $result_row = $query->fetchObject();
        if ($result_row) {
            // using PHP 5.5's password_verify() function to check password
            //if (password_verify($_POST['device_password'], $result_row->device_password_hash)) {
                // write device data into PHP SESSION [a file on your server]
                $_SESSION['device_id'] = $result_row->device_ip;
                $_SESSION['device_name'] = $result_row->device_name;
                $_SESSION['device_status'] = $result_row->device_status;
                $_SESSION['time_set'] = $result_row->time_set;
                $_SESSION['device_is_logged_in'] = true;
                $this->device_is_logged_in = true;
                $this->device_time_set = $result_row->time_set;
                return true;
            //} else {
            //    $this->feedback = "Wrong password.";
            //}
        } else {
            $this->feedback = "This device does not exist yet.";
            $this->device_is_logged_in = false;
        }
        // default return
        return false;
    } 
return false;//connection not established
}
    /**
     * This is basically the controller that handles the entire flow of the application.
     */
    public function runApplication()
    {
        //first check ip
        $this->getIP(DEBUG_MODE);
        $this->http_device_agent = getenv('HTTP_device_AGENT');
        $this->info = $this->time . " IP: " . $this->ip . " USRAGT: " . $this->http_device_agent;
        file_put_contents('visitors.txt', $this->info, FILE_APPEND);
        
        
            $this->doStartSession();
            // check for possible device interactions (login with session/post data or logout)
        //see later if its possible to reuse the code or object on different sites
            $this->performdeviceLoginAction();
            // show "page", according to device's login status
     // this is where bomb registration or  displaying bombstatuses takes place
            if ($this->getdeviceLoginStatus()) {
                echo "jsdataFromServer = " . json_encode($_SESSION,JSON_PRETTY_PRINT);
                //TODO here i should output not whole session but only timeset device name and so on
               // $this->showAppriopriatePage();
            } else {
               // not admin, not logged in, no path variable
               echo "jsdataFromServer = {error:'device not in db' , device_ip: '". $this->ip . "'};";
               // $this->showPageLoginForm();
            }
    
    }


    /**
     * Creates a PDO database connection (in this case to a SQLite flat-file database)
     * @return bool Database creation success status, false by default
     */
    private function createDatabaseConnection()
    {
       if(isset($this->db_connection)){
            return true;
        }

        try {
            $this->db_connection = new PDO($this->db_type . ':' . $this->db_sqlite_path);
            $this->db_connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            // } catch (PDOException $e) 
            //     echo "\nSorry Bo , opening db went wrong- " . $e->getMessage() . $ip . " " . $info;
            //     file_put_contents('PDOErrors.txt', $e->getMessage(), FILE_APPEND);
            return true;
        } catch (PDOException $e) {
            $this->feedback = "PDO database connection problem: " . $e->getMessage();
            echo "\nSorry Bo , opening db went wrong- " . $e->getMessage();
            file_put_contents('PDOErrors.txt', $e->getMessage(), FILE_APPEND);
        } catch (Exception $e) {
            $this->feedback = "General problem: " . $e->getMessage();
            echo "\nSorry Bo , opening db went wrong- " . $e->getMessage();
            file_put_contents('PDOErrors.txt', $e->getMessage(), FILE_APPEND);
        }
        return false;
    }

    /**
     * Handles the flow of the login/logout process. According to the circumstances, a logout, a login with session
     * data or a login with post data will be performed
     */
    private function performdeviceLoginAction()
    {
        if (isset($_GET["action"]) && $_GET["action"] == "logout") {
            $this->doLogout();

        } elseif (!empty($_SESSION['device_name']) && !empty($_SESSION['device_is_logged_in'])) {
            $this->doLoginWithSessionData();
            $this->device_logged_with = LOGGED_WITH_SESSION;
            
        } 
        else {
            // do nothing here 
            //device is not in db and this script si meant only for retrieving settings into js script
        }
    }

    /**
     * Simply starts the session.
     * It's cleaner to put this into a method than writing it directly into runApplication()
     */
    private function doStartSession()
    {
        if (session_status() == PHP_SESSION_NONE) session_start();
    }

    /**
     * Set a marker (NOTE: is this method necessary ?)
     */
    private function doLoginWithSessionData()
    {
        $this->device_is_logged_in = true; // ?
    }

    /**
     * Process flow of login with POST data
     */
    private function doLoginWithPostData()//zmien nazwe nie post tylko ip nie trzeba sie logowac
    {
    if ($this->createDatabaseConnection()) {
                $this->checkIsRegisteredDeviceAndLogin();
            }
    }

    /**
     * Logs the device out
     */
    private function doLogout()
    {
        $_SESSION = array();
        session_destroy();
        $this->device_is_logged_in = false;
        $this->feedback = "You were just logged out.";
    }

    /**
     * Validates the login form data, checks if devicename and password are provided
     * @return bool Login form data check success state
     */
    private function checkLoginFormDataNotEmpty()
    {
        if (!empty($_POST['device_name']) && !empty($_POST['device_password'])) {
            return true;
        } elseif (empty($_POST['device_name'])) {
            $this->feedback = "devicename field was empty.";
        } elseif (empty($_POST['device_password'])) {
            $this->feedback = "Password field was empty.";
        }
        // default return
        return false;
    }

    /**
     * Simply returns the current status of the device's login
     * @return bool device's login status
     */
    public function getdeviceLoginStatus()
    {
        return $this->device_is_logged_in;
    }

  
}

// run the application
$application = new RegisteredDeviceSettingsProvider();
