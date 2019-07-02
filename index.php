<?php
date_default_timezone_set('Europe/London');
// constants to determine if user just logged in or was active on this device
define("JUST_LOGGING_IN", 2);
define("LOGGED_WITH_SESSION", 3);
define("DEBUG_MODE", 1);
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
     * @var bool Login status of user
     */
    private $user_is_logged_in = false;

    /**
     * @var bool Login status of device
     */
    private $device_is_logged_in = false;

    /**
     * @var string System messages, likes errors, notices, etc.
     */
    public $feedback = "";

    /**
     * time when user accessed page
     *
     * @var int
     */
    public $time = 0;
    /**
     * ip of user that just accessed page
     *
     * @var string
     */
    public $ip = null;
    /**
     * user agent of user that just accessed page
     *
     * @var string
     */
    public $http_user_agent = null;

    /**
     * status of the way user just logged in
     * whether he is session active or just logged on a new device
     * @var int
     */
    public $user_logged_with = 0;
    public $scriptName = null;
    /**
     * Does necessary checks for PHP version and PHP password compatibility library and runs the application
     */
    public function __construct()
    {
        $this->time = time();
        $this->scriptName = $_SERVER['SCRIPT_NAME'];
        $this->feedback = "";
    }

    /**
     * Performs a check for minimum requirements to run this application.
     * Does not run the further application when PHP version is lower than 5.3.7
     * Does include the PHP password compatibility library when PHP version lower than 5.5.0
     * (this library adds the PHP 5.5 password hashing functions to older versions of PHP)
     * @return bool Success status of minimum requirements check, default is false
     */
    public function performMinimumRequirementsCheck()
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
     *gets Ip of the user or fakedIP from get/post params
     *lazy getter sets ip only if it's not set yet.
     *    @var $getFaked bool should ip be set from get params
     *    @return string ip
     */

    public function getIP($getFaked = DEBUG_MODE)
    {

        if (isset($this->ip)) {
            return $this->ip;
        }

        if ($getFaked  &&  isset($_GET["ip"])) {
            $this->ip = $_GET["ip"];
            return $this->ip;
        } else if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $this->ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $this->ip = $_SERVER['REMOTE_ADDR'];
        }
        return $this->ip;
    }

    public function IsRegisteredDevice()
    {
        //IF ip is in database display bomb/device interface with time counting down
        if ($this->createDatabaseConnection()) {
            $sql = 'SELECT *
                FROM device
                WHERE device_ip = :connection_ip;
                ';
            $query = $this->db_connection->prepare($sql);
            $query->bindValue(':connection_ip', $this->getIP(DEBUG_MODE));
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
                //if (password_verify($_POST['user_password'], $result_row->user_password_hash)) {
                // write user data into PHP SESSION [a file on your server]
                $_SESSION['device_id'] = $result_row->device_ip;
                $_SESSION['device_name'] = $result_row->device_name;
                $_SESSION['device_status'] = $result_row->device_status;
                $_SESSION['time_set'] = $result_row->time_set;
                $this->device_is_logged_in = true;
                $this->device_time_set = $result_row->time_set;
                return true;
                //} else {
                //    $this->feedback .="Wrong password.";
                //}
            } else {
                $this->feedback .= "This device is not registered yet in db.";
            }
            // default return
            return false;
        }
        return false; //connection not established
    }
    /**
     * This is basically the controller that handles the entire flow of the application.
     */
    public function runApplication()
    {
        //first check ip
        $this->getIP(DEBUG_MODE);
        $this->http_user_agent = getenv('HTTP_USER_AGENT');
        $this->info = $this->time . " IP: " . $this->ip . " USRAGT: " . $this->http_user_agent;
        file_put_contents('visitors.txt', "\n" . $this->info, FILE_APPEND);






        // check is user wants to see register page (etc.)
        // !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
        //this is the backdoor everybody who will write register 
        // can backdoor into admin
        if (isset($_GET["action"]) && $_GET["action"] == "register") {
            $this->doRegistration();
            $this->showPageLoginForm();
        } else {
            // start the session, always needed!
            $this->doStartSession();
            //check first if device is Being registered
            if (isset($_GET["action"]) && $_GET["action"] == "registerDevice") {
                $this->doDeviceRegistration();
                // $this->showPageLoginForm();
                
                // dont show nothing yet, it will be taken care of later down in the code
                // if user is logged in and device was registered showpageloggedin will show all db
            }
            //check second if device is registered
            elseif ($this->IsRegisteredDevice()) {
                include("rafka timebomb z klawiatura.html");
                exit();
            };
            //if device is not registered  
            // check for possible userADmin interactions (login with session/post data or logout)
            $this->performUserLoginAction();
            // show "page", according to user's login status
            // this is where bomb registration or  displaying bombstatuses takes place
            // if userAdmin is logged in and device is not registered
            if ($this->getUserLoginStatus() && !$this->device_is_logged_in) {
                $this->showPageAddToDevices();
                $this->showPageLoggedIn();
            } elseif ($this->getUserLoginStatus() && $this->device_is_logged_in) {
              //do not show table of devices here this is 
              $this->showPageLoggedIn();
                $this->feedback .= "userAdmin is logged in and device is logged in ,
                probably here user should have an option to logout";

                }
                else{
                // not admin, not logged in, no path variable
$this->showPageLoginForm();
            }
            
            
        }
    }

    private function showPageAddToDevices()
    {
    //     CREATE TABLE IF NOT EXISTS device (
    //         'device_id' INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, 
    //         'device_name' TEXT NOT NULL,
    //         'device_description' TEXT,
    //         'device_ip' TEXT NOT NULL,
    //         'device_http_user_agent' TEXT NOT NULL, 
    //         'device_password' TEXT NOT NULL ,
    //         'device_status' TEXT,
    //         'time_set' INTEGER,
    //         'time_last_uppdated' DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    //                 );
    
    // CREATE UNIQUE INDEX `device_ip_UNIQUE` ON device ( `device_ip` ASC);
    // CREATE UNIQUE INDEX `device_name_UNIQUE` ON device ( `device_name` ASC);
        include("addToDevices.blade.php");
        }

    /**
     * Creates a PDO database connection (in this case to a SQLite flat-file database)
     * @return bool Database creation success status, false by default
     */
    private function createDatabaseConnection()
    {
        if (isset($this->db_connection)) {
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
            $this->feedback .="PDO database connection problem: " . $e->getMessage();
            echo "\nSorry Bo , opening db went wrong- " . $e->getMessage();
            file_put_contents('PDOErrors.txt', "\n" . $e->getMessage(), FILE_APPEND);
        } catch (Exception $e) {
            $this->feedback .="General problem: " . $e->getMessage();
            echo "\nSorry Bo , opening db went wrong- " . $e->getMessage();
            file_put_contents('PDOErrors.txt', "\n" . $e->getMessage(), FILE_APPEND);
        }
        return false;
    }

    /**
     * Handles the flow of the login/logout process. According to the circumstances, a logout, a login with session
     * data or a login with post data will be performed
     */
    private function performUserLoginAction()
    {
        if (isset($_GET["action"]) && $_GET["action"] == "logout") {
            $this->doLogout();
        } elseif (!empty($_SESSION['user_name']) && ($_SESSION['user_is_logged_in'])) {
            //this should result in a show all bombs table
            //because it means that Admin is still on his phone
            $this->doLoginWithSessionData();
            $this->user_logged_with = LOGGED_WITH_SESSION;
        } elseif (isset($_POST["login"])) {
            //this means that some valid admin logs in on a new device, maybe bomb
            $this->doLoginWithPostData();
            $this->user_logged_with = JUST_LOGGING_IN;
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
        $this->user_is_logged_in = true; // ?
    }

    /**
     * Process flow of login with POST data
     */
    private function doLoginWithPostData()
    {
        if ($this->checkLoginFormDataNotEmpty()) {
            if ($this->createDatabaseConnection()) {
                $this->checkPasswordCorrectnessAndLogin();
            }
        }
    }

    /**
     * Logs the user out
     */
    private function doLogout()
    {
        $_SESSION = array();
        session_destroy();
        $this->user_is_logged_in = false;
        $this->feedback .= "You were just logged out.\n";
    }

    /**
     * The registration flow
     * @return bool
     */
    private function doRegistration()
    {
        if ($this->checkRegistrationData()) {
            if ($this->createDatabaseConnection()) {
                $this->createNewUser();
            } else {
                $this->feedback .= "problem with db connection \n";
               }
        } else {
            $this->feedback .= "registration data not ok ? \n";
               
            return false;
        }
    }

    /**
     * The device registration flow
     * @return bool
     */
    private function doDeviceRegistration()
    {
        if ($this->checkDeviceRegistrationData()) {
            if ($this->createDatabaseConnection()) {
                $this->createNewDevice();// TODO implement
            } else {
                $this->feedback .= "problem with db connection \n";
               }
        } else {
            $this->feedback .= "registration data not ok ? \n";
               
            return false;
        }
    }

    /**
     * Validates the login form data, checks if username and password are provided
     * @return bool Login form data check success state
     */
    private function checkLoginFormDataNotEmpty()
    {
        if (!empty($_POST['user_name']) && !empty($_POST['user_password'])) {
            return true;
        } elseif (empty($_POST['user_name'])) {
            $this->feedback .="Username field was empty.";
        } elseif (empty($_POST['user_password'])) {
            $this->feedback .="Password field was empty.";
        }
        // default return
        return false;
    }

    /**
     * Checks if user exits, if so: check if provided password matches the one in the database
     * @return bool User login success status
     */
    private function checkPasswordCorrectnessAndLogin()
    {
        // remember: the user can log in with username or email address
        $sql = 'SELECT user_name, user_email, user_password_hash
                FROM user
                WHERE user_name = :user_name OR user_email = :user_name
                LIMIT 1';
        $query = $this->db_connection->prepare($sql);
        $query->bindValue(':user_name', $_POST['user_name']);
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
            if (password_verify($_POST['user_password'], $result_row->user_password_hash)) {
                // write user data into PHP SESSION [a file on your server]
                $_SESSION['user_name'] = $result_row->user_name;
                $_SESSION['user_email'] = $result_row->user_email;
                $_SESSION['user_is_logged_in'] = true;
                $this->user_is_logged_in = true;
                return true;
            } else {
                $this->feedback .="Wrong password.";
            }
        } else {
            $this->feedback .="This user does not exist.";
        }
        // default return
        return false;
    }

    /**
     * Validates the user's registration input
     * @return bool Success status of user's registration data validation
     */
    private function checkRegistrationData()
    {
        // if no registration form submitted: exit the method
        if (!isset($_POST["register"])) {
            return false;
        }

        // validating the input
        if (
            !empty($_POST['user_name'])
            && strlen($_POST['user_name']) <= 64
            && strlen($_POST['user_name']) >= 2
            && preg_match('/^[a-z\d]{2,64}$/i', $_POST['user_name'])
            && !empty($_POST['user_email'])
            && strlen($_POST['user_email']) <= 64
            && filter_var($_POST['user_email'], FILTER_VALIDATE_EMAIL)
            && !empty($_POST['user_password_new'])
            && strlen($_POST['user_password_new']) >= 6
            && !empty($_POST['user_password_repeat'])
            && ($_POST['user_password_new'] === $_POST['user_password_repeat'])
        ) {
            // only this case return true, only this case is valid
            return true;
        } elseif (empty($_POST['user_name'])) {
            $this->feedback .="Empty Username";
        } elseif (empty($_POST['user_password_new']) || empty($_POST['user_password_repeat'])) {
            $this->feedback .="Empty Password";
        } elseif ($_POST['user_password_new'] !== $_POST['user_password_repeat']) {
            $this->feedback .="Password and password repeat are not the same";
        } elseif (strlen($_POST['user_password_new']) < 6) {
            $this->feedback .="Password has a minimum length of 6 characters";
        } elseif (strlen($_POST['user_name']) > 64 || strlen($_POST['user_name']) < 2) {
            $this->feedback .="Username cannot be shorter than 2 or longer than 64 characters";
        } elseif (!preg_match('/^[a-z\d]{2,64}$/i', $_POST['user_name'])) {
            $this->feedback .="Username does not fit the name scheme: only a-Z and numbers are allowed, 2 to 64 characters";
        } elseif (empty($_POST['user_email'])) {
            $this->feedback .="Email cannot be empty";
        } elseif (strlen($_POST['user_email']) > 64) {
            $this->feedback .="Email cannot be longer than 64 characters";
        } elseif (!filter_var($_POST['user_email'], FILTER_VALIDATE_EMAIL)) {
            $this->feedback .="Your email address is not in a valid email format";
        } else {
            $this->feedback .="An unknown error occurred.";
        }

        // default return
        return false;
    }

      /**
     * Validates the device's registration input
     * @return bool Success status of device's registration data validation
     */
    private function checkDeviceRegistrationData()
    {
        // if no registration form submitted: exit the method
        if (!isset($_POST["register"])) {
            return false;
        }

        // validating the input
        if (
            !empty($_POST['device_name'])
            && strlen($_POST['device_name']) <= 24
            && strlen($_POST['device_name']) >= 2
            && preg_match('/^[a-z\d]{2,24}$/i', $_POST['device_name'])
            // && !empty($_POST['user_email'])
            // && strlen($_POST['user_email']) <= 64
            // && filter_var($_POST['user_email'], FILTER_VALIDATE_EMAIL)
            && !empty($_POST['device_password_new'])
            && strlen($_POST['device_password_new']) >= 4
            && strlen($_POST['device_password_new']) <= 24
            && !empty($_POST['device_password_repeat'])
            && ($_POST['device_password_new'] === $_POST['device_password_repeat'])

            && !empty($_POST['device_ip'])
            && !empty($_POST['time_set'])
           && strtotime($_POST['time_set']) > time() //!!!! check if works date time set is later than now
        
        ) {
            // only this case return true, only this case is valid
            return true;
                //     CREATE TABLE IF NOT EXISTS device (
                // -----------//         'device_id' INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, 
                // -----------//         'device_name' TEXT NOT NULL,
                // -----------//         'device_description' TEXT,
                // -----------//         'device_ip' TEXT NOT NULL,
                // //         'device_http_user_agent' TEXT NOT NULL, 
                // -----------//         'device_password' TEXT NOT NULL ,
                // -----------//         'device_status' TEXT,
                // //         'time_set' INTEGER,
                // -----------//         'time_last_uppdated' DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
                //                 );
                
                // CREATE UNIQUE INDEX `device_ip_UNIQUE` ON device ( `device_ip` ASC);
                // CREATE UNIQUE INDEX `device_name_UNIQUE` ON device ( `device_name` ASC);
        } elseif (empty($_POST['device_name'])) {
            $this->feedback .="Empty device name";
        } elseif (empty($_POST['device_password_new']) || empty($_POST['device_password_repeat'])) {
            $this->feedback .="Empty device Password";
        } elseif ($_POST['device_password_new'] !== $_POST['device_password_repeat']) {
            $this->feedback .="Password and password repeat are not the same";
        } elseif (strlen($_POST['device_password_new']) < 4) {
            $this->feedback .="Password has a minimum length of 6 characters";
        } elseif (!preg_match('/^[a-z\d]{4,24}$/', $_POST['device_password_new'])) {
            $this->feedback .="passwordd does not fit the scheme: only a-z and numbers are allowed, 4 to 24 characters";
        } elseif (strlen($_POST['device_name']) > 64 || strlen($_POST['device_name']) < 2) {
            $this->feedback .="devicename cannot be shorter than 2 or longer than 64 characters";
        } elseif (!preg_match('/^[a-z\d]{2,64}$/', $_POST['device_name'])) {
            $this->feedback .="device name does not fit the name scheme: only a-z and numbers are allowed, 2 to 64 characters";
        } elseif (empty($_POST['device_ip'])) {
            $this->feedback .="device ip cannot be empty";
        } elseif (empty($_POST['time_set'])) {
           $this->feedback .="time_set cannot be empty";
        } elseif (strtotime($_POST['time_set']) <= time()) {
           $this->feedback .="time_set cannot be in the past";
        } else {
            $this->feedback .="An unknown error occurred.";
        }
        $this->feedback .="\n";
        // default return
        return false;
    }



    /**
     * Creates a new user.
     * @return bool Success status of user registration
     */
    private function createNewUser()
    {
        // remove html code etc. from username and email
        $user_name = htmlentities($_POST['user_name'], ENT_QUOTES);
        $user_email = htmlentities($_POST['user_email'], ENT_QUOTES);
        $user_password = $_POST['user_password_new'];
        // crypt the user's password with the PHP 5.5's password_hash() function, results in a 60 char hash string.
        // the constant PASSWORD_DEFAULT comes from PHP 5.5 or the password_compatibility_library
        $user_password_hash = password_hash($user_password, PASSWORD_DEFAULT);

        $sql = 'SELECT * FROM user WHERE user_name = :user_name OR user_email = :user_email';
        $dbcon = $this->db_connection;
        $query = $dbcon->prepare($sql);
        $query->bindValue(':user_name', $user_name);
        $query->bindValue(':user_email', $user_email);
        $query->execute();

        // As there is no numRows() in SQLite/PDO (!!) we have to do it this way:
        // If you meet the inventor of PDO, punch him. Seriously.
        $result_row = $query->fetchObject();
        if ($result_row) {
            $this->feedback .="Sorry, that username / email is already taken. Please choose another one.";
        } else {
            $sql = 'INSERT INTO user (user_name, user_password_hash, user_email,user_ip, http_user_agent)
                    VALUES(:user_name, :user_password_hash, :user_email,:user_ip,:http_user_agent)';
            $query = $this->db_connection->prepare($sql);
            $query->bindValue(':user_name', $user_name);
            $query->bindValue(':user_password_hash', $user_password_hash);
            $query->bindValue(':user_email', $user_email);
            $query->bindValue(':http_user_agent', $_SERVER['HTTP_USER_AGENT']);
            $query->bindValue(':user_ip', $this->getIP(true)); // false means get real IP 

            // PDO's execute() gives back TRUE when successful, FALSE when not
            // @link http://stackoverflow.com/q/1661863/1114320
            $registration_success_state = $query->execute();

            if ($registration_success_state) {
                $this->feedback .="Your account has been created successfully. You can now log in.";
                return true;
            } else {
                $this->feedback .="Sorry, your registration failed. Please go back and try again.";
            }
        }
        // default return
        return false;
    }

private function createNewDevice()
    {
        // remove html code etc. from username and email
        $device_name = htmlentities($_POST['device_name'], ENT_QUOTES);
        $device_ip = htmlentities($_POST['device_ip'], ENT_QUOTES);
        $device_description = htmlentities($_POST['device_description'], ENT_QUOTES);
        $device_password = $_POST['device_password_new'];
        $device_http_user_agent = $_SERVER['HTTP_USER_AGENT'];
        $time_set = $_POST['time_set'];

        
        // crypt the user's password with the PHP 5.5's password_hash() function, results in a 60 char hash string.
        // the constant PASSWORD_DEFAULT comes from PHP 5.5 or the password_compatibility_library
        // maybe later do the hash  version
        //$user_password_hash = password_hash($user_password, PASSWORD_DEFAULT);

        $sql = 'SELECT * FROM device WHERE device_name = :device_name OR device_ip = :device_ip';
        $dbcon = $this->db_connection;
        $query = $dbcon->prepare($sql);
        $query->bindValue(':device_name', $device_name);
        $query->bindValue(':device_ip', $device_ip);
        $query->execute();

        // As there is no numRows() in SQLite/PDO (!!) we have to do it this way:
        // If you meet the inventor of PDO, punch him. Seriously.
        $result_row = $query->fetchObject();
        if ($result_row) {
            $this->feedback .="Sorry, that device name / ip is already taken. Please choose another one.";
        } else {
            // -----------//         'device_id' INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, 
                // -----------//         'device_name' TEXT NOT NULL,
                // -----------//         'device_description' TEXT,
                // -----------//         'device_ip' TEXT NOT NULL,
                // //         'device_http_user_agent' TEXT NOT NULL, 
                // -----------//         'device_password' TEXT NOT NULL ,
                // -----------//         'device_status' TEXT,
                // //         'time_set' INTEGER,
                // -----------//         'time_last_uppdated' DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
                
            $sql = 'INSERT INTO device 
            (device_id,device_name, device_password, 
             device_ip, device_http_user_agent
            device_description, device_status,time_set)
                    VALUES
             null ,:device_name, :device_password, 
             :device_ip, :device_http_user_agent
            :device_description, :device_status,:time_set)';
            $query = $this->db_connection->prepare($sql);
            $query->bindValue(':device_name', $device_name);
            $query->bindValue(':device_password', $device_password);
            $query->bindValue(':device_ip', $device_ip);
            $query->bindValue(':device_http_user_agent', $_SERVER['HTTP_USER_AGENT']);
            $query->bindValue(':device_description', $device_description);
            $query->bindValue(':device_status', 'active');
            $query->bindValue(':time_set', $time_set);
            
            
            
            // PDO's execute() gives back TRUE when successful, FALSE when not
            // @link http://stackoverflow.com/q/1661863/1114320
            $registration_success_state = $query->execute();

            if ($registration_success_state) {
                $this->feedback .="Device has been registered successfully. You can now request device page.";
                return true;
            } else {
                $this->feedback .="Sorry, your registration failed. Please go back and try again.";
            }
        }
        // default return
        return false;
    }

        /**
     * Simply returns the current status of the user's login
     * @return bool User's login status
     */
    public function getUserLoginStatus()
    {
        return $this->user_is_logged_in;
    }

    /**
     * Simple demo-"page" that will be shown when the user is logged in.
     * In a real application you would probably include an html-template here, but for this extremely simple
     * demo the "echo" statements are totally okay.
     */
    private function showPageLoggedIn()
    {
        if ($this->feedback) {
            
            echo "<pre>FEEDBACK : \n" . $this->feedback . "</pre><br/>";
        }

        echo 'Hello ' . $_SESSION['user_name'] . ', you are logged in.<br/><br/>';
        echo '<a href="' . $_SERVER['SCRIPT_NAME'] . '?action=logout">Log out</a>';


        include('showAllDEvices.blade.php');
    }

    /**
     * Simple demo-"page" with the login form.
     * In a real application you would probably include an html-template here, but for this extremely simple
     * demo the "echo" statements are totally okay.
     */
    private function showPageLoginForm()
    {
        if ($this->feedback) {
            echo $this->feedback . "<br/><br/>";
        }

        echo '<h2>Login</h2>';
        echo '<form method="post" action="' . $_SERVER['SCRIPT_NAME'] . '" name="loginform">';
        echo '<label for="login_input_username">Username (or email)</label> ';
        echo '<input id="login_input_username" type="text" name="user_name" required /> ';
        echo '<label for="login_input_password">Password</label> ';
        echo '<input id="login_input_password" type="password" name="user_password" required /> ';
        echo '<input type="submit"  name="login" value="Log in" />';
        echo '</form>';

        echo '<a href="' . $_SERVER['SCRIPT_NAME'] . '?action=register">Register new account</a>';
    }

    /**
     * Simple demo-"page" with the registration form.
     * In a real application you would probably include an html-template here, but for this extremely simple
     * demo the "echo" statements are totally okay.
     */
    private function showPageRegistration()
    {
        if ($this->feedback) {
            echo $this->feedback . "<br/><br/>";
        }

        echo '<h2>Registration</h2>';

        echo '<form method="post" action="' . $_SERVER['SCRIPT_NAME'] . '?action=register" name="registerform">';
        echo '<label for="login_input_username">Username (only letters and numbers, 2 to 64 characters)</label>';
        echo '<input id="login_input_username" type="text" pattern="[a-zA-Z0-9]{2,64}" name="user_name" required />';
        echo '<label for="login_input_email">User\'s email</label>';
        echo '<input id="login_input_email" type="email" name="user_email" required />';
        echo '<label for="login_input_password_new">Password (min. 6 characters)</label>';
        echo '<input id="login_input_password_new" class="login_input" type="password" name="user_password_new" pattern=".{6,}" required autocomplete="off" />';
        echo '<label for="login_input_password_repeat">Repeat password</label>';
        echo '<input id="login_input_password_repeat" class="login_input" type="password" name="user_password_repeat" pattern=".{6,}" required autocomplete="off" />';
        echo '<input type="submit" name="register" value="Register" />';
        echo '</form>';

        echo '<a href="' . $_SERVER['SCRIPT_NAME'] . '">Homepage</a>';
    }
}

// run the application
$application = new OneFileLoginApplication();
if ($application->performMinimumRequirementsCheck()) {
    $application->runApplication();
}
