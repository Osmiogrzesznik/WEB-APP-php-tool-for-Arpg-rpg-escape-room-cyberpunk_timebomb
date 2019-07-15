<?php

// constants to determine if user just logged in or was active on this device
define("JUST_LOGGING_IN", 2);
define("LOGGED_WITH_SESSION", 3);
define("DEBUG_MODE", 1);
define("DEFAULT_TIMEZONE_NAME_LONDON", "Europe/London");
define("MY_DATE_FORMAT","Y-m-d\TH:i:s");
define("NO_COOKIE","NOCOOKIE_MEH");


function print_me($var,$return=false){
    if(!$return){
    echo "<pre>";
    print_r($var,$return);
    echo "</pre>";
    }
    else{
        return "<pre>".print_r($_var,true)."</pre>";
    }
}
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
    public $script_start_time = 0;
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
    public $user_logged_with = 0;//not used anymore
    public $scriptName = null;//start using it
    public $dev = null;// ????
    public $timezones = null;
    public $timezone = null;
    public $timezoneName = null;
    public $time_set_timestamp = null; // used only when device was chcked for being registered
	public $device_session_id = null; //used to identify device through cookie even if IP changed(updates IP )
	public $device_id = null; // used only when device was chcked for being registered
    /**
     * Does necessary checks for PHP version and PHP password compatibility library and runs the application
     */
    public function __construct()
    {
        ///removeXXX move to usercreation
        //$this->timezone = new DateTimeZone(DEFAULT_TIMEZONE_NAME_LONDON);//** ? */  
        
        $this->script_start_time= time();
        $this->scriptName = $_SERVER['SCRIPT_NAME'];//TODO: globalreplace to property scriptName
        $this->feedback = "";
    }

    public function addFeedback($msg)
    {
        $this->feedback .= $msg . "\n";
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
            $this->addFeedback("Sorry, Simple PHP Login does not run on a PHP version older than 5.3.7 !");
        } elseif (version_compare(PHP_VERSION, '5.5.0', '<')) {
            require_once("libraries/password_compatibility_library.php");
            return true;
        } elseif (version_compare(PHP_VERSION, '5.5.0', '>=')) {
            return true;
        }
        // default return
        include("ViewStartHTML.blade.php");
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



    /**
     * lazy getter , checks first value device_is_logged_in,
     * then $_SESSION, and finally Database. Sideeffect - updates the time_last_active
     *  
     *
     * @return bool is device currently connected a registered in db user device
     */
    public function IsRegisteredDevice()
    {
        if ($this->device_is_logged_in) {
            return true;
        }

        // had to remove it because when user deletes the device refreshing leads to device unaware of being deleted
        // BUT !!!!!!
        // MAYBE JUST SANITY CHECK : IF DEVICE HAS SESSION AND IS NOT PRESENT IN DB JUST DESTROY SESSION?
        // CHECK IF USABLE IN device_session_id process
        //elseif (isset($_SESSION['device_id'])) {
        //     $this->device_is_logged_in = true;
        //     return true;
        // }
        
        $ip = $this->getIP(DEBUG_MODE);

        //what if no cookie on the device but it is registered(e.g.different browser opened)
            // set cookie when retrieved device by using ip?
            if (isset($_COOKIE['device_session_id'])){
                $this->addFeedback("you either not have cookies enabled or your cookie expired?");
                $sess_token_from_cookie = $_COOKIE['device_session_id'];
            }
            else{
        $sess_token_from_cookie = "NOCOOKIE_MEH"; //WHAT ELSE SHOULD I DO ? dont want to match anything wrong in queries below
            }
            
        // you could check first for session vars here and for cookie, then  compare them against the db

        if ($this->createDatabaseConnection()) {
            $sql = 'SELECT 
            device_id,device_ip,device_name,device_status,
            device_password,time_set,user_timezone,device_session_id,device_location
            FROM device
            INNER jOIN user ON registered_by_user = user_id 
            WHERE device_ip = :connection_ip OR device_session_id = :sess_token_from_cookie
            LIMIT 1;';   
            
            /***bookmark
             * 
             * I was trying to add searching by device session id 
             * but i dont want doubled results
             * 
             * 
             * if no limit
             * then IF there are two rows returned 
             *          it immediately means that device had changed its ip
             *      ELSE 
             *          everything is fine
             * 
             *   */
            $query = $this->db_connection->prepare($sql);
            $query->bindValue(':connection_ip', $ip);
            $query->bindValue(':sess_token_from_cookie', $sess_token_from_cookie);
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
                $_SESSION['device_id'] = $result_row->device_id;
                $_SESSION['device_session_id'] = $result_row->device_session_id;
                $_SESSION['device_ip'] = $ip;
                $_SESSION['device_name'] = $result_row->device_name;
                $_SESSION['device_status'] = $result_row->device_status;
                $_SESSION['time_set'] = $result_row->time_set;
                $_SESSION['device_password'] = $result_row->device_password;
                $_SESSION['timezone'] = $result_row->user_timezone;

                $this->device_session_id = $result_row->device_session_id;
                $this->timezoneName = $result_row->user_timezone;
                $this->timezone = new DateTimeZone($this->timezoneName);
                $this->device_is_logged_in = true;
                $this->device_id = $result_row->device_id;
                $this->device_password = $result_row->device_password;
                $this->device_time_set = $result_row->time_set;
                $dateOFF = DateTime::createFromFormat(MY_DATE_FORMAT,$result_row->time_set,$this->timezone);
                $this->time_set_timestamp = $dateOFF->format('U'); 
                //REFRESH COOKIE   
                $COOKIESETTINGS =array(
                    "expires" => $this->time_set_timestamp,//instead this use one calculated from db
                    "httponly" => true
                );
                setcookie("device_session_id",
                        $this->device_session_id,$COOKIESETTINGS);    

                //UPDATE LOCATION IF Different and not no location       
                if(isset($_GET['latitude'],$_GET['longitude'])){
                    $location = $_GET['latitude'] . "/" . $_GET['longitude'];
                }else{
                    if($result_row->device_location === "no location"){
                    $location = "no location";
                    }
                    else{
                        $location = $result_row->device_location;
                    }
                }

                    $sql = 'UPDATE device
                    SET time_last_active = :date_now, 
                    device_status = :device_status, 
                    device_location = :device_location,
                    device_ip = :connection_ip
                    WHERE device_id = :device_id;
                    ';

                    date_default_timezone_set($this->timezoneName);
                    $date_now = date('Y-m-d\TH:i:s');
                    $query = $this->db_connection->prepare($sql);
                    $query->bindValue(':date_now', $date_now);
                    $query->bindValue(':device_status', 'active');
                    $query->bindValue(':device_id', $this->device_id);                    
                    $query->bindValue(':connection_ip', $ip);
                    $query->bindValue(':device_location', $location);
                    $query->execute();
                
                return true;
            } else {
                $this->addFeedback("($ip) is not registered yet in db.");
            }
            // default return
            return false;
        }
        return false; //connection not established
    }



    public function checkDevicePasswordCorrectness()
    {
        // you could check first for session vars here and for cookie, then  compare them against the db

        //IF ip is in database display bomb/device interface with time counting down

        //  should check first for session and cookie to not block db
        try {
            if ($this->IsRegisteredDevice()) {
                //if ($_SESSION['device_password'] === $_GET["password"]) {
                if ($_GET["password"] === $this->device_password) {
                    $this->addFeedback("password correct");
                    // TODO: should check if its too late , even when device already 
                    // detonated, just to make sure nobody will fool admins
                    if ($this->createDatabaseConnection()) {
                        $sql = 'UPDATE device
                            SET device_status = :new_status
                            WHERE device_id = :already_checked_and_found_id;
                            ';
                        $ip = $this->getIP(DEBUG_MODE);
                        $query = $this->db_connection->prepare($sql);
                        $query->bindValue(':new_status', "disarmed");
                        $query->bindValue(':already_checked_and_found_id', $this->device_id);
                        $query->execute();
                        $_SESSION['device_status'] = "disarmed";
                        $this->addFeedback("device disarmed");
                        return true;
                    }
                    $this->addFeedback("db CONNECTION PROBLEM");
                    return false;
                }
                $this->addFeedback("password incorrect");
                return false;
            }
            $this->addFeedback("dEVICE IS NOT REGISTERED?");
            return false;
        } catch (Exception $e) {
            $this->addFeedback($e->getMessage());
            return false;
        }
        $this->addFeedback("no error have no idea");
        return false;
    }
    /**
     * This is basically the controller that handles the entire flow of the application.
     */
    public function runApplication()
    {
        //first check ip


        $this->getIP(DEBUG_MODE);
        $this->http_user_agent = getenv('HTTP_USER_AGENT');
        // $this->info = $this->script_start_time. " IP: " . $this->ip . " USRAGT: " . $this->http_user_agent;
        // file_put_contents('visitors.txt', "\n" . $this->info, FILE_APPEND);


        $this->doStartSession();
        // print_me($_COOKIE);
        // exit;
        
        if (isset($_GET["action"])) {
            //both ofbthese cases check agst db for regstd dev
            //factor out isRegistteredDevice()
            switch ($_GET["action"]): 

                case ("superuser"):
                    $this->showPageLoginForm();
                    exit();
                    break;
                
                case ("getsettings"):
                    include("JSsettings.php");
                    exit();
                    break;

                case ("password"):
                    $password_ok = $this->checkDevicePasswordCorrectness();
                    $_ARR_response = array(
                        'password_ok' => $password_ok,
                        'feedback' => $this->feedback
                    );
                    echo json_encode($_ARR_response);
                    exit();

                    break;


                case ("registerForm"):

                    $this->showPageRegistration();
                    exit();
                    //$this->doRegistration();
                    break;

                case ("registerUser"):
                    if (isset($_POST["register"])){
                        // print_r($_POST);
                        // exit();        
                        $this->doRegistration();
                    }
                break;
 
            // dont show nothing yet, it will be taken care of later down in the code
            // if user is logged in and device was registered showpageloggedin will show all db
            endswitch;
        }

        //if device is not registered  
        // check for possible userADmin interactions (login with session/post data or logout)
        $this->performUserLoginAction();

        if ($this->getUserLoginStatus()) {

            if (isset($_POST["updatedevice"])) {
                $this->updateDevice();
                echo "updating device by POST feedback: " . $this->feedback;
                exit();
            } elseif (isset($_GET["action"])) {

                switch ($_GET["action"]): 

                    case ("registerDevice"):
                        $this->doDeviceRegistration();
                        break;

                    case ("delete"):
                        $this->deleteDevice();
                        $this->showPageLoggedIn();
                        $this->showPageAddToDevices();
                        //   include("JSsettings.php");
                        exit();
                        break;

                    case ("deleteme"):
                        $this->deleteMeUser();
                        $this->doLogout();
                        $this->showPageRegistration();
                        //   include("JSsettings.php");
                        exit();
                        break;
                endswitch;
            }


            if ($this->device_is_logged_in) {
                //this means usrr just added device 
                $this->addFeedback("userAdmin is logged in and device is logged in ,
                probably here user should have an option to logout");

                $this->showPageLoggedIn();
                $this->showPageAddToDevices(); //for Adding More? Maybe bomb interface instead?

            } elseif (!$this->device_is_logged_in) {
                //do not show table of devices here this is 
                $this->showPageLoggedIn();
                $this->showPageAddToDevices();
                // after all admin should use separate device to add it to db




            }
        } //end of if user is $user_is_logged_in

        else {

            //user not logged in and no interesting action get was provided(so it is browser request mainly)
            if ($this->IsRegisteredDevice()) {
                include("ViewBombInterface.html"); // no feedback
            }
            // below cannot register new user if user loggedout 
            //and device  is a bomb already to prevent circumventions
            
            else {
                $this->showPageLoginForm();
            }
        }

        //    echo "<br>reached end of logic on the server";

    }

    private function deleteMeUser(){
        if (!$this->user_is_logged_in ){
            // Hacker?
            $this->addFeedback("You need to be logged in in order to delete your account");
            return false;
        } else{
            if ($this->createDatabaseConnection()) {
                $sql = 'DELETE
                FROM user
                WHERE user_id = :user_id;
                ';
                $id = $_SESSION["user_id"];
                $query = $this->db_connection->prepare($sql);
                $query->bindValue(':user_id', $id);
                $query->execute();
                $amnt = $query->rowCount();
                if ($amnt > 0) {
                    $this->addFeedback("\n $amnt user deleted successfully");
                } else {
                    $this->addFeedback("Deleting user with id: $id impossible. No such device");
                }
            } else {
                $this->addFeedback("Could not establish db connection");
            }
        }
    }
    /**
     * Deletes device with GET id
     *
     * @return void
     */
    private function deleteDevice()
    {
        if (!$this->user_is_logged_in ){
            // Hacker?
            $this->addFeedback("You need to be logged in in order to delete your devices");
            return false;
        }

        if ($this->createDatabaseConnection()) {

            if (!isset($_GET["id"]) || empty($_GET["id"])) {
                $this->addFeedback(print_r($_GET, true) . "device id not chosen");
            } else {

                $sql = 'DELETE
                FROM device
                WHERE device_id = :selected_id;
                ';
                $id = $_GET["id"];
                $query = $this->db_connection->prepare($sql);
                $query->bindValue(':selected_id', $id);
                $query->execute();
                $amnt = $query->rowCount();
                if ($amnt > 0) {
                    $this->addFeedback("\n $amnt Device(s) deleted successfully");
                } else {
                    $this->addFeedback("Deleting device with id: $id impossible. No such device");
                }
            }
        } else {
            $this->addFeedback("Could not establish db connection");
        }
    }

    private function updateDevice()
    {
        if (!$this->checkDeviceUpdateData( )){
            echo "bad data : " . $this->feedback;
            print_r($_POST);
            exit();
        }
        if ($this->createDatabaseConnection()) {

            if (!isset($_POST["device_id"])) {
                $this->addFeedback("device id not chosen");
            } else {

                $sql = 'UPDATE
                device
                SET 
                device_name = :device_name,
                device_description = :device_description,
                device_http_user_agent = :device_http_user_agent,
                device_password = :device_password,
                device_status = :device_status,
                time_set = :time_set
                WHERE device_id = :selected_id;
                ';

                $selected_id = $_POST["device_id"];
                $device_name = $_POST["device_name"];
                $device_description = $_POST["device_description"];
                $device_http_user_agent = $_POST["device_http_user_agent"];
                $device_password = $_POST["device_password"];
                $device_status = $_POST["device_status"];
                $time_set = $_POST["time_set"];
                $query = $this->db_connection->prepare($sql);
                $query->bindValue(':selected_id', $selected_id);
                $query->bindValue(':device_name',$device_name);
                $query->bindValue(':device_description',$device_description);
                $query->bindValue(':device_http_user_agent',$device_http_user_agent);
                $query->bindValue(':device_password',$device_password);
                $query->bindValue(':device_status',$device_status);
                $query->bindValue(':time_set',$time_set);
                $query->execute();
                $amnt = $query->rowCount();
                if ($amnt > 0) {
                    $this->addFeedback("\n $amnt Device(s) updated successfully");
                } else {
                    $this->addFeedback("updating device with id: $selected_id impossible. No such device");
                }
            }
        } else {
            $this->addFeedback("Could not establish db connection");
        }
    }

    /**
     * Validates the device's registration input
     * @return bool Success status of device's registration data validation
     */
    private function checkDeviceUpdateData()
    {
        // TODO: set timezone for strtotime below
        // if no registration form submitted: exit the method
        
        //($_POST['time_set'], $this->timezone;
        if (isset($_POST["updatedevice"])) {
            // validating the input
            date_default_timezone_set($this->timezoneName);
            if(!empty($_POST['time_set'])){
            $dateOFF = DateTime::createFromFormat(MY_DATE_FORMAT,$_POST['time_set'],$this->timezone);
            $timestamp = $dateOFF->format('U');
            }
            else{
                $timestamp=0;
            }
            $this->time_set_timestamp = $timestamp;
            
            if (
                !empty($_POST['device_name'])
                && strlen($_POST['device_name']) <= 24
                && strlen($_POST['device_name']) >= 2
                && preg_match('/^[a-zA-Z\d]{2,24}$/i', $_POST['device_name'])
                && !empty($_POST['device_password'])
                && strlen($_POST['device_password']) >= 3
                && strlen($_POST['device_password']) <= 24
                && preg_match('/^[a-z\d]{3,24}$/i', $_POST['device_password'])
                && !empty($_POST['device_ip'])
                && !empty($_POST['time_set'])
                && $timestamp > time() //!!!! checks if works date time set is later than now
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
                // -----------//         'time_last_active' DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
                //                 );

                // CREATE UNIQUE INDEX `device_ip_UNIQUE` ON device ( `device_ip` ASC);
                // CREATE UNIQUE INDEX `device_name_UNIQUE` ON device ( `device_name` ASC);
            } elseif (empty($_POST['device_name'])) {
                $this->addFeedback("Empty device name");
            } elseif (empty($_POST['device_password' ])){
                $this->addFeedback("Empty device Password");
            } elseif (strlen($_POST['device_password']) < 3) {
                $this->addFeedback("Password has a minimum length of 4 characters");
            } elseif (!preg_match('/^[a-z\d]{3,24}$/', $_POST['device_password'])) {
                $this->addFeedback("password does not fit the scheme: only a-z and numbers are allowed, 4 to 24 characters");
            } elseif (strlen($_POST['device_name']) > 64 || strlen($_POST['device_name']) < 2) {
                $this->addFeedback("devicename cannot be shorter than 2 or longer than 64 characters");
            } elseif (!preg_match('/^[a-zA-Z\d]{2,64}$/', $_POST['device_name'])) {
                $this->addFeedback("device name does not fit the name scheme: only a-z and numbers are allowed, 2 to 64 characters");
            } elseif (empty($_POST['device_ip'])) {
                $this->addFeedback("device ip cannot be empty");
            } elseif (empty($_POST['time_set'])) {
                $this->addFeedback("time_set cannot be empty");
            } elseif ($timestamp <= time()) {
                $this->addFeedback("time_set cannot be in the past");
            } else {
                $this->addFeedback("An unknown error occurred.");
            }
        } else {
            $this->addFeedback("no updatedevice POST\n");

            $this->addFeedback("\n");
            // default return
            return false;
        }
    }

    private function showPageAddToDevices()
    {
        include_once("ViewStartHTML.blade.php");
        include("ViewAddToDevices.blade.php");
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
            $this->addFeedback("PDO database connection problem: " . $e->getMessage());
            file_put_contents('PDOErrors.txt', "\n" . $e->getMessage(), FILE_APPEND);
            return false;
        } catch (Exception $e) {
            $this->addFeedback("General problem: " . $e->getMessage());
            file_put_contents('PDOErrors.txt', "\n" . $e->getMessage(), FILE_APPEND);
            return false;
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
        $this->timezoneName = $_SESSION['user_timezone'];
        $this->timezone = new DateTimeZone($this->timezoneName);
        $this->user_is_logged_in = true;
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
        $this->addFeedback("You were just logged out.\n");
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
                $this->addFeedback("database connection ok ");
            } else {
                $this->addFeedback("problem with db connection \n");
            }
        } else {
            $this->addFeedback("registration data not ok ? \n");

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
                $this->createNewDevice();
            } else {
                $this->addFeedback("Problem with db connection \n");
            }
        } else {
            $this->addFeedback("Device registration data not ok ? \n");

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
            $this->addFeedback("Username field was empty.");
        } elseif (empty($_POST['user_password'])) {
            $this->addFeedback("Password field was empty.");
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
        $sql = 'SELECT user_id, user_name, user_password_hash, user_timezone
                FROM user
                WHERE user_name = :user_name
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
                $_SESSION['user_id'] = $result_row->user_id;
                $_SESSION['user_name'] = $result_row->user_name;
                $_SESSION['user_timezone'] = $result_row->user_timezone;
                $_SESSION['user_is_logged_in'] = true;
                $this->timezoneName = $result_row->user_timezone;
                $this->timezone = new DateTimeZone($this->timezoneName);
                $this->user_is_logged_in = true;
                return true;
            } else {
                $this->addFeedback("Wrong password.");
            }
        } else {
            $this->addFeedback("This user does not exist.");
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
            $this->addFeedback("\n no POST used");
            return false;
        }

        // validating the input
        if (
            !empty($_POST['user_name'])
            && strlen($_POST['user_name']) <= 64
            && strlen($_POST['user_name']) >= 2
            && preg_match('/^[a-z\d]{2,64}$/i', $_POST['user_name'])
            && !empty($_POST['user_password_new'])
            && strlen($_POST['user_password_new']) >= 6
            && !empty($_POST['user_password_repeat'])
            && ($_POST['user_password_new'] === $_POST['user_password_repeat'])
            && !empty($_POST['timezone'])
            && in_array($_POST['timezone'],timezone_identifiers_list())
        ) {
            // only this case return true, only this case is valid
            return true;
        } elseif (empty($_POST['user_name'])) {
            $this->addFeedback("Empty Username");
        } elseif (empty($_POST['user_password_new']) || empty($_POST['user_password_repeat'])) {
            $this->addFeedback("Empty Password");
        } elseif (empty($_POST['timezone'])) {
            $this->addFeedback("Empty timezone");
        } elseif (!in_array($_POST['timezone'],timezone_identifiers_list())) {
            $this->addFeedback("Timezone not valid");
        } elseif ($_POST['user_password_new'] !== $_POST['user_password_repeat']) {
            $this->addFeedback("Password and password repeat are not the same");
        } elseif (strlen($_POST['user_password_new']) < 6) {
            $this->addFeedback("Password has a minimum length of 6 characters");
        } elseif (strlen($_POST['user_name']) > 64 || strlen($_POST['user_name']) < 2) {
            $this->addFeedback("Username cannot be shorter than 2 or longer than 64 characters");
        } elseif (!preg_match('/^[a-z\d]{2,64}$/i', $_POST['user_name'])) {
            $this->addFeedback("Username does not fit the name scheme: only a-Z and numbers are allowed, 2 to 64 characters");
        } else {
            $this->addFeedback("An unknown error occurred.");
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
        // TODO: here as well timezone has to be set to use strtotime
        // if no registration form submitted: exit the method
        if (isset($_POST["register"])) {

                // validating the input
                date_default_timezone_set($this->timezoneName);
                if(!empty($_POST['time_set'])){
                $dateOFF = DateTime::createFromFormat(MY_DATE_FORMAT,$_POST['time_set'],$this->timezone);
                $timestamp = $dateOFF->format('U');
                }
                else{
                    $timestamp=0;
                }
                $this->time_set_timestamp = $timestamp;


            // validating the input
            if (
                !empty($_POST['device_name'])
                && strlen($_POST['device_name']) <= 24
                && strlen($_POST['device_name']) >= 2
                //&& preg_match('/^[a-z\d]{2,24}$/i', $_POST['device_name'])
                && !empty($_POST['device_password_new'])
                && strlen($_POST['device_password_new']) >= 3
                && strlen($_POST['device_password_new']) <= 24
                && !empty($_POST['device_password_repeat'])
                && ($_POST['device_password_new'] === $_POST['device_password_repeat'])

                && !empty($_POST['device_ip'])
                && !empty($_POST['time_set'])
                && $timestamp > time() //!!!! check if works date time set is later than now

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
                // -----------//         'time_last_active' DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
                //                 );

                // CREATE UNIQUE INDEX `device_ip_UNIQUE` ON device ( `device_ip` ASC);
                // CREATE UNIQUE INDEX `device_name_UNIQUE` ON device ( `device_name` ASC);
            } elseif (empty($_POST['device_name'])) {
                $this->addFeedback("Empty device name");
            } elseif (empty($_POST['device_password_new']) || empty($_POST['device_password_repeat'])) {
                $this->addFeedback("Empty device Password");
            } elseif ($_POST['device_password_new'] !== $_POST['device_password_repeat']) {
                $this->addFeedback("Password and password repeat are not the same");
            } elseif (strlen($_POST['device_password_new']) < 3) {
                $this->addFeedback("Password has a minimum length of 6 characters");
            } elseif (!preg_match('/^[a-z\d]{3,24}$/', $_POST['device_password_new'])) {
                $this->addFeedback("password does not fit the scheme: only a-z and numbers are allowed, 3 to 24 characters");
            } elseif (strlen($_POST['device_name']) > 64 || strlen($_POST['device_name']) < 2) {
                $this->addFeedback("devicename cannot be shorter than 2 or longer than 64 characters");
            // } elseif (!preg_match('/^{2,64}$/', $_POST['device_name'])) {
            //     $this->addFeedback("device name does not fit the name scheme: only a-z, A-Z  and numbers are allowed, 2 to 64 characters");
            } elseif (empty($_POST['device_ip'])) {
                $this->addFeedback("device ip cannot be empty");
            } elseif (empty($_POST['time_set'])) {
                $this->addFeedback("time_set cannot be empty");
            } elseif ($timestamp <= time()) {
                $this->addFeedback("time_set cannot be in the past");
            } else {
                $this->addFeedback("An unknown error occurred.");
            }
        } else {
            $this->addFeedback("no register POST or no update POST\n");

            $this->addFeedback("\n");
            // default return
            return false;
        }
    }



    /**
     * Creates a new user.
     * @return bool Success status of user registration
     */
    private function createNewUser()
    {
        // remove html code etc. from username and email
        $user_name = htmlentities($_POST['user_name'], ENT_QUOTES);
        $user_password = $_POST['user_password_new'];
        $user_ip = $this->getIP(DEBUG_MODE);
        $user_timezone = $_POST['timezone'];
        // crypt the user's password with the PHP 5.5's password_hash() function, results in a 60 char hash string.
        // the constant PASSWORD_DEFAULT comes from PHP 5.5 or the password_compatibility_library
        $user_password_hash = password_hash($user_password, PASSWORD_DEFAULT);

        $sql = 'SELECT user_name, device_ip FROM user
        cross join device on user_name = :user_name or device_ip = :user_ip;
        '; // check if current ip is registered in devices or name is registered in users

       
        $dbcon = $this->db_connection;
        $query = $dbcon->prepare($sql);
        $query->bindValue(':user_name', $user_name);
        $query->bindValue(':user_ip', $user_ip);
        $query->execute();

        // As there is no numRows() in SQLite/PDO (!!) we have to do it this way:
        // If you meet the inventor of PDO, punch him. Seriously.
        $result_row = $query->fetchObject();
        if ($result_row) {
            $this->addFeedback("Sorry, that username or there exists a user on this ip. only devices should be checked aginst the ip 
            and only user-device linked should be shown
            s already taken. Please choose another one.\n");
        } else {
             
            $sql = 'INSERT INTO user (user_name, user_password_hash ,user_ip, http_user_agent,user_timezone)
                    VALUES(:user_name, :user_password_hash,:user_ip,:http_user_agent,:user_timezone)';
            $query = $this->db_connection->prepare($sql);
            $query->bindValue(':user_name', $user_name);
            $query->bindValue(':user_password_hash', $user_password_hash);
            $query->bindValue(':http_user_agent', $_SERVER['HTTP_USER_AGENT']);
            $query->bindValue(':user_ip', $user_ip); 
            $query->bindValue(':user_timezone', $user_timezone);
            // PDO's execute() gives back TRUE when successful, FALSE when not
            // @link http://stackoverflow.com/q/1661863/1114320
            $registration_success_state = $query->execute();

            if ($registration_success_state) {
                $this->addFeedback("Your account has been created successfully. You can now log in.");
                return true;
            } else {
                $this->addFeedback("Sorry, your registration failed. Please go back and try again.");
            }
        }
        // default return
        $this->addFeedback("Sorry, your registration failed. I don't know why.");

        return false;
    }

    private function createNewDevice()
    {
        // remove html code etc. from username and email
        $device_name = htmlentities($_POST['device_name'], ENT_QUOTES);
        $device_ip = htmlentities($_POST['device_ip'], ENT_QUOTES);
        $device_description = htmlentities($_POST['device_description'], ENT_QUOTES);
        $device_password = htmlentities($_POST['device_password_new'], ENT_QUOTES);
        $device_http_user_agent = $_SERVER['HTTP_USER_AGENT'];
        $time_set = $_POST['time_set'];
        $device_session_id_from_logged_user_cookie_modified = $_COOKIE['PHPSESSID']."_device_name_".$device_name;
        // $this->addFeedback(
        //     print_r($this,true)
        // );
        date_default_timezone_set($this->timezoneName);
        $date_now = date('Y-m-d\TH:i:s'); // add seconds to datetime-locale provided value
        $registered_by_user = $_SESSION['user_id'];



        // crypt the user's password with the PHP 5.5's password_hash() function, results in a 60 char hash string.
        // the constant PASSWORD_DEFAULT comes from PHP 5.5 or the password_compatibility_library
        // maybe later do the hash  version
        //$user_password_hash = password_hash($user_password, PASSWORD_DEFAULT);

        $sql = 'SELECT device_session_id,device_ip,device_name FROM device
         WHERE device_name = :device_name 
         OR device_ip = :device_ip
         OR device_session_id = :device_session_id';
        $dbcon = $this->db_connection;
        $query = $dbcon->prepare($sql);
        $query->bindValue(':device_name', $device_name);
        $query->bindValue(':device_session_id', $device_session_id_from_logged_user_cookie_modified);
        $query->bindValue(':device_ip', $device_ip);
        $query->execute();

        // As there is no numRows() in SQLite/PDO (!!) we have to do it this way:
        // If you meet the inventor of PDO, punch him. Seriously.
        $result_row = $query->fetchObject();
        if ($result_row) {
            $this->addFeedback("Sorry, some property is already taken:
             \n\t$result_row->device_name as device name
             \n\t/ $result_row->$device_ip as ip (your ip = ".$this->getIP(DEBUG_MODE)."/
             \n\t  $result_row->device_session_id as session_id is already taken. (your sessid = $device_session_id_from_logged_user_cookie_modified)
             \n\t Please choose another one. ");
        } else {
            // -----------//         'device_id' INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, 
            // -----------//         'device_name' TEXT NOT NULL,
            // -----------//         'device_description' TEXT,
            // -----------//         'device_ip' TEXT NOT NULL,
            // //         'device_http_user_agent' TEXT NOT NULL, 
            // -----------//         'device_password' TEXT NOT NULL ,
            // -----------//         'device_status' TEXT,
            // //         'time_set' INTEGER,
            // -----------//         'time_last_active' DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP

            $sql = 'INSERT INTO device 
            (device_id,device_name, device_password, 
             device_ip, device_http_user_agent,
            device_description, device_status,time_set, 
            registered_by_user, time_last_active,
             device_session_id, device_location)
                    VALUES
             (null ,:device_name, :device_password, 
             :device_ip, :device_http_user_agent,
            :device_description, :device_status,:time_set,
            :registered_by_user, :time_last_active,
             :device_session_id, :device_location)';
            $query = $this->db_connection->prepare($sql);

            if(isset($_POST['latitude'],$_POST['longitude'])){
                $location = $_POST['latitude'] . "/" . $_POST['longitude'];
            }else{
                $location = "no location";
            }

            $query->bindValue(':device_location', $location);
            $query->bindValue(':device_name', $device_name);
            $query->bindValue(':device_password', $device_password);
            $query->bindValue(':device_ip', $device_ip);
            $query->bindValue(':device_http_user_agent', $_SERVER['HTTP_USER_AGENT']);
            $query->bindValue(':device_description', $device_description);
            $query->bindValue(':device_status', 'created');
            $query->bindValue(':time_set', $time_set);
            $query->bindValue(':registered_by_user', $registered_by_user);
            $query->bindValue(':time_last_active', $date_now);
            $query->bindValue(':device_session_id', $device_session_id_from_logged_user_cookie_modified);
            

            $_SESSION['device_session_id'] = $device_session_id_from_logged_user_cookie_modified;
            $COOKIESETTINGS =array(
                "expires" => $this->time_set_timestamp,
                "httponly" => true
            );
            setcookie("device_session_id",
                 $device_session_id_from_logged_user_cookie_modified,$COOKIESETTINGS);

            // PDO's execute() gives back TRUE when successful, FALSE when not
            // @link http://stackoverflow.com/q/1661863/1114320
            $registration_success_state = $query->execute();

            if ($registration_success_state) {
                $this->addFeedback("Device has been registered successfully. You can now log out to see device page.");
                return true;
            } else {
                $this->addFeedback("Sorry, your registration failed. Please go back and try again.");
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
     * Simply returns the current status of the device's login
     * @return bool device's login status
     */
    public function getDeviceLoginStatus()
    {
        return $this->device_is_logged_in;
    }

    /**
     * Simple demo-"page" that will be shown when the user is logged in.
     * In a real application you would probably include an html-template here, but for this extremely simple
     * demo the "echo" statements are totally okay.
     */
    private function showPageLoggedIn()
    {
        if ($this->createDatabaseConnection()) {

            include("ViewStartHTML.blade.php");
            echo 'Hello ' . $_SESSION['user_name'] . ', you are logged in.<br/><br/>';
            include('ViewAllDevices.blade.php');
        } else {
            $this->addFeedback("\nsorry cannot display all your devices due to db conn problem");
            include("ViewStartHTML.blade.php");
        }
    }

    /**
     * Simple demo-"page" with the login form.
     * In a real application you would probably include an html-template here, but for this extremely simple
     * demo the "echo" statements are totally okay.
     */
    private function showPageLoginForm()
    {
        include("ViewStartHTML.blade.php");
        include("ViewLoginForm.blade.php");
    }

    /**
     * Simple demo-"page" with the registration form.
     * In a real application you would probably include an html-template here, but for this extremely simple
     * demo the "echo" statements are totally okay.
     */
    private function showPageRegistration()
    {

        include("ViewStartHTML.blade.php");
        include("ViewRegisterUserForm.blade.php");
    }
}

// run the application
$application = new OneFileLoginApplication();
if ($application->performMinimumRequirementsCheck()) {
    $application->runApplication();
}
