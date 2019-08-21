<?php
define("DS", DIRECTORY_SEPARATOR);
define("PROJ", realpath(dirname(__FILE__)) . DS);

require_once(PROJ . "res" . DS . "utils.php");





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
  private $db = null;

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
  public $user_logged_with = 0; //not used anymore
  public $scriptName = null; //start using it
  public $dev = null; // ????
  public $timezones = null;
  public $timezone = null;
  public $timezoneName = null;
  public $columns = null;
  public $resultset = null;
  public $user_id = null;
  public $timebomb_time_set_timestamp = null; // used only when device was chcked for being registered
  public $device_session_id = null; //used to identify device through cookie even if IP changed(updates IP )
  public $device_id = null; // used only when device was chcked for being registered
  public $timebomb_status = null;
  public $timebomb_status_new = null;
  public $time_last_active = null;
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
    View("ViewStartHTML", $this);
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
   * queries the db for all devices created by @param $user_id, and returns 
   * easy to loop-through arrays
   *
   * @param [type] $user_id creatorOfDevices
   * @return array_assoc [columnNames=>[string],rows[values]]
   */
  public function getAllDevices($user_id, $neededColumns = "*") //$neededColumns)
  {
    //$neededColumns = array();
    //$neededColumnsString = join(",",$neededColumns);
    $conn = $this->db;
    //TODO all have to have proper tablename prefixes
    //TODO CONSIDER USING string instead of array, less writing, easier to copy from sqls
    //TODO UNIFOrm way of naming properties :
    //device_name, functionality_name could help to get interesting 
    // us colums across tables
    $sql = "SELECT $neededColumns FROM device
LEFT JOIN point 
ON device.device_fk_location_point  = point.point_id
LEFT JOIN functionality_device
ON functionality_device.device_id_fk = device.device_id
LEFT JOIN functionality
ON functionality_id = functionality_device.functionality_id_fk
LEFT JOIN mapentity_device
ON mapentity_device.device_id_fk = device.device_id
LEFT JOIN mapentity
ON mapentity.mapentity_id = mapentity_device.mapentity_id_fk
WHERE device.user_id_fk = :user_id"; // WHERE class = '$class'"; later  -> WHERE user_creator_id = :logged_user_id

    $query = $conn->prepare($sql);
    $query->bindValue(':user_id', $user_id);


    $query->setFetchMode(PDO::FETCH_ASSOC);
    $query->execute();


    $columnNames = array();
    $resultset = array();

    # Set columns and results array
    while ($row = $query->fetch()) {
      if (empty($columnNames)) {
        $columnNames = array_keys($row);
      }
      $resultset[] = $row;
    }

    $ret = array(
      "columnNames" => $columnNames,
      "rows" => $resultset
    );

    return $ret;
    //[columnNames=>[string],rows[values]]
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
    $sess_token_from_cookie = cook('device_session_id', false);
    if ($sess_token_from_cookie) {
      $this->addFeedback("cookie accepted");
      //$sess_token_from_cookie = $_COOKIE['device_session_id'];
    } else {
      $this->addFeedback("you either not have cookies enabled or your cookie expired?");
      // $sess_token_from_cookie = NO_COOKIE; //WHAT ELSE SHOULD I DO ? dont want to match anything wrong in queries below
    }


    // you could check first for session vars here and for cookie, then  compare them against the db

    if ($this->createDatabaseConnection()) {
      // $dto = new TableObject("device");
      // $this->deviceTable = $dto;
      /**
       * limit1:
       * then IF there are two rows returned 
       *          it immediately means that device had changed its ip
       *      ELSE 
       *          everything is fine
       * 
       *   */

      $sql = 'SELECT 
device_id,device_ip,device_name,timebomb.timebomb_status,
timebomb_password,timebomb_time_set,user_timezone,
device_session_id,device_location,
time_last_active, point_longitude, point_latitude
FROM device
LEFT jOIN point 
ON  point.point_id = device.device_fk_location_point
INNER jOIN user ON user_id_fk = user_id 
LEFT JOIN timebomb
ON timebomb_device_id_fk = device_id
WHERE device_ip = :connection_ip OR device_session_id = :sess_token_from_cookie
LIMIT 1;';

      $query = $this->db->prepare($sql);
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
        $resultArr = objectToArray($result_row);
        $_SESSION = array_merge($_SESSION, $resultArr);

        foreach ($resultArr as $k  => $v) {
          $this->$k = $v;
        }

        // $_SESSION['device_id'] = $result_row->device_id;
        // $_SESSION['device_session_id'] = $result_row->device_session_id;
        $_SESSION['device_ip'] = $ip;
        // $_SESSION['device_name'] = $result_row->device_name;
        // $_SESSION['timebomb_status'] = $result_row->timebomb_status;
        // $_SESSION['timebomb_time_set'] = $result_row->timebomb_time_set;
        // $_SESSION['timebomb_password'] = $result_row->timebomb_password;
        $_SESSION['timezone'] = $result_row->user_timezone;

        // $this->timebomb_status = $result_row->timebomb_status;
        //$this->device_session_id = $result_row->device_session_id;
        $this->timezoneName = $result_row->user_timezone;
        $this->timezone = new DateTimeZone($this->timezoneName);
        $this->device_is_logged_in = true;
        //$this->device_id = $result_row->device_id;
        //$this->timebomb_password = $result_row->timebomb_password;
        $this->device_timebomb_time_set = $result_row->timebomb_time_set;
        //$this->time_last_active = $result_row->time_last_active;
        $status_old = $result_row->timebomb_status;
        // $this->timebomb_status = $result_row->timebomb_status;

        $dateOFF = DateTime::createFromFormat(MY_DATE_FORMAT, $result_row->timebomb_time_set, $this->timezone);
        $this->timebomb_time_set_timestamp = $dateOFF->format('U');
        //REFRESH COOKIE   
        $expire = $this->timebomb_time_set_timestamp;
        setcookie(
          "device_session_id",
          $this->device_session_id,
          $expire,
          "",
          "",
          false,
          true
        );
        $this->addFeedback("re - set cookie");

        //-------------------------------------------------------------------------------------
        //START
        //-------------------------------------------------------------------------------------
        //-------------------------DEVICE TYPE SPECIFIC LOGIC----------------------------------------------------
        //-----------------------This should be included through device class type--------------------------------------------------------------
        //------------------------and follow device interface ---------------------------
        //----------------------even updating below should be performed by type rather than by this class-------------------------------------
        //-------------------------------------------------------------------------------------

        //UPDATE LOCATION IF Different and not no location  
        $location_old = $result_row->device_location;
        if (isset($_GET['latitude'], $_GET['longitude'])) {
          $location_new = $_GET['latitude'] . "/" . $_GET['longitude'];
          $this->addFeedback("got location GET params");
          if ($location_new != $location_old) {
            //update device location history
            $this->addFeedback("location is new, storing old in history");
            try {
              $sql = "INSERT INTO history_device_location (
        history_id,
        history_device_id,
        history_time_last_active,
        history_device_location
                      )
        VALUES (
        null,
        :device_id,
        :time_last_active,
        :device_location
                      );";
              $query = $this->db->prepare($sql);
              $query->bindValue(':device_location', $location_old); //old
              $query->bindValue(':device_id', $this->device_id); //unchanging
              $query->bindValue(':time_last_active', $this->time_last_active); //old
              $success = $query->execute();
            } catch (Exception $e) {
              $this->addFeedback("updating location history exception:" . $e->getMessage());
            }
            $this->addFeedback($success ? "updated history" : "not updated history");
          }
        } else { //location not provided
          $this->addFeedback("location not provided");
          //if last location was not provided and this one is still not provided
          if ($location_old === "no location") {
            $location_new = "no location";
          } else { //if location has not been provided but last location is valid 
            //just update it with old loc
            $location_new = $location_old;
            $this->addFeedback("but previously you were sending location");
          }
        }

        date_default_timezone_set($this->timezoneName);
        $status_new = "active"; //default new value on any request came
        if ($status_old == "disarmed") {
          $this->addFeedback("this device was already disarmed");
          $status_new = $status_old;
        } elseif ($status_old == "detonated") {
          $this->addFeedback("this device was already detonated");
          $status_new = $status_old;
        } elseif ($this->timebomb_time_set_timestamp <= time()) {
          $this->addFeedback("this device just detonated");
          $status_new = "detonated";
          $this->timebomb_status_new = $status_new; // used by JSsettings.php
        }
        $this->timebomb_status_new = $status_new;

        //-------------------------------------------------------------------------------------
        //END
        //-------------------------------------------------------------------------------------

        //everything should be updated here based on the _new variables provided by device_type class
        $sql = 'UPDATE device
SET time_last_active = :date_now, 
timebomb_status = :timebomb_status, 
device_location = :device_location,
device_ip = :connection_ip
WHERE device_id = :device_id;
';
        date_default_timezone_set($this->timezoneName);
        $date_now = date('Y-m-d\TH:i:s');
        $query = $this->db->prepare($sql);
        $query->bindValue(':date_now', $date_now);
        $query->bindValue(':timebomb_status', $status_new);
        $query->bindValue(':device_id', $this->device_id);
        $query->bindValue(':connection_ip', $ip);
        $query->bindValue(':device_location', $location_new);
        $query->execute();

        return true;
      } else { //no device registered
        if ($sess_token_from_cookie) { //not registered but some cookie from deleted device
          setcookie(
            "device_session_id",
            cook('device_session_id'),
            1,
            "",
            "",
            false,
            true
          );
          $this->addFeedback("re - set cookie to zero it belonged to deleted device");
        }
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
        //if ($_SESSION['timebomb_password'] === $_GET["password"]) {
        if (strtolower($_GET["password"]) === strtolower($this->timebomb_password)) {
          $this->addFeedback("password correct");
          // TODO: should check if its too late , even when device already 
          // detonated, just to make sure nobody will fool admins
          if ($this->timebomb_status_new == "detonated") {
            $this->addFeedback("however, sorry it is too late device is detonated already");
            return false;
          }
          if ($this->createDatabaseConnection()) {
            $sql = 'UPDATE device
        SET timebomb_status = :new_status
        WHERE device_id = :already_checked_and_found_id;
        ';
            $query = $this->db->prepare($sql);
            $query->bindValue(':new_status', "disarmed");
            $query->bindValue(':already_checked_and_found_id', $this->device_id);
            $query->execute();
            $this->timebomb_status = "disarmed";
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
    $action = get("action");
    $this->action = $action;
    $this->getIP(DEBUG_MODE);
    $this->doStartSession();

    if ($action) {
      $this->routeUserNotLoggedInActions($action);
    }
    //if device is not registered 
    // check for possible userADmin interactions (login with session/post data or logout)
    $this->performUserLoginAction();
    if ($this->getUserLoginStatus()) { //if user is logged in
      if ($action) {
        $this->routeUserLoggedInActions($action);
      }
      $this->showPageLoggedIn();
    }
    //end of if user is $user_is_logged_in
    else {
      //user not logged in and no interesting action get was provided(so it is browser request mainly)
      if ($this->IsRegisteredDevice()) {
        $this->routeDeviceFeatures();
        // no feedback
      }
      // below cannot register new user if user loggedout 
      //and device is a bomb already to prevent circumventions

      else {
        $this->showPageLoginForm();
      }
    }

    // echo "<br>reached end of logic on the server";

  }


  public function routeDeviceFeatures()
  {
    $this->addFeedback("not routing device features yet- not implemented");
    include("functionalities/timebomb/ViewBombInterface.html");

    exit(); //this bomb is not modular maybe different version can be
    $this->addFeedback("not routing device features yet- not implemented");


    switch (get("type")):
        //change switch to ForEach of device functionalities (from DB) 
        //stored in an array of filepaths to folders in type directory
      case ("bomb"):
        include("functionalities/timebomb/ViewBombInterface.html");
        exit(); //this bomb is not modular maybe different version can be
        break;

      case ("radar"):
        include("functionalities/radar/ViewRadarMODULE.html");
        break;

      case ("geiger"):
        include("functionalities/geiger/ViewGeigerMODULE.html");
        break;

    endswitch;
  }



  public function routeUserNotLoggedInActions($action)
  {

    switch ($action): case ("superuser"):
        $this->doLogout();
        //destroy cookie
        setcookie(
          "device_session_id",
          null,
          1,
          "",
          "",
          false,
          true
        );
        $this->showPageLoginForm();
        exit();
        break;

      case ("getsettings"):
        include("JSsettings.php");
        //*** change to View?
        exit();
        break;

      case ("locate"):
        if ($this->IsRegisteredDevice()) {
          $_ARR_response = array(
            'timebomb_status' => $this->timebomb_status_new,
            'timebomb_time_set' => $this->device_timebomb_time_set,
            'feedback' => $this->feedback
          );
        } else {
          $_ARR_response = array(
            'feedback' => $this->feedback
          );
        }
        echo json_encode($_ARR_response);
        exit();
        break;

      case ("password"):
        $password_ok = $this->checkDevicePasswordCorrectness();
        $_ARR_response = array(
          'timebomb_status' => $this->timebomb_status_new,
          'timebomb_time_set' => $this->device_timebomb_time_set,
          'password_ok' => $password_ok,
          'feedback' => $this->feedback
        );
        echo json_encode($_ARR_response);

        exit();
        break;


      case ("registerForm"):

        $this->showPageRegistration();
        //$this->doRegistration();

        exit();
        break;

      case ("registerUser"):
        if (isset($_POST["register"])) {

          $this->doRegistration();
        }
        $this->performUserLoginAction();

        $this->showPageLoggedIn();
        exit();
        break;

    // dont show nothing yet, it will be taken care of later down in the code
    // if user is logged in and device was registered showpageloggedin will show all db
    endswitch;
  }

  public function getAllFeaturesMock($user_id)
  {
    $fs = file_get_contents("aaaa.json");
    //$fs = file_get_contents("php://input");
    $fsphp = json_decode($fs);
    $this->addFeedback("loading features from db:");
    $this->addFeedback(print_me($fsphp, true));

    return $fsphp;
  }

  public function saveAllFeaturesMock($user_id)
  {
    $input = "php://input";
    //$input="aaaa.json";
    $fs = file_get_contents($input);
    $fsphp = json_decode($fs);
    $this->addFeedback("received features:");
    $this->addFeedback(print_me($fsphp, true));
    $fsnice = json_encode($fsphp, JSON_PRETTY_PRINT);
    $succ = file_put_contents("aaaa.json", $fsnice);
    return $succ;

    $tb = new TableObject("mapentity");
    foreach ($fsphp as $feature) { }
  }

  public function saveAllFeatures($user_id)
  {
    $input = "php://input";
    //$input="aaaa.json";
    $fs = file_get_contents($input);
    $fsphp = json_decode($fs,JSON_OBJECT_AS_ARRAY);
    $this->addFeedback("received features:");
    $this->addFeedback(print_me($fsphp, true));
    $fsnice = json_encode($fsphp, JSON_PRETTY_PRINT);
    $succ = file_put_contents("lastreceivedfeatures.json", $fsnice);

    $tb = new MapEntity();
    return $tb->saveAllFeatures($fsphp,$this->user_id);
  }

  public function routeUserLoggedInActions($action)
  {

    /*CREATE TABLE mapentity (
        mapentity_id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
        user_id_fk integer not null,
        mapentity_name TEXT NOT NULL,
        mapentity_description TEXT,
        mapentity_style text,
        mapentity_effect_id_fk integer not null,
        mapentity_effect_ison BOOLEAN,
        mapentity_json text,
        CONSTRAINT fk_user
        FOREIGN KEY(user_id_fk) 
        REFERENCES user (user_id)
        ON DELETE CASCADE
        CONSTRAINT fk_effect
        FOREIGN KEY(mapentity_effect_id_fk) 
        REFERENCES effect (effect_id)
        ON DELETE CASCADE
                      );*/
    switch ($action): case ("js_loadfeatures"):
        $mapentTbl = new TableObject("mapentity");
        $allByUsr = $mapentTbl->getAllByUser($this->user_id)->toTableReadyArray();

        if ($this->createDatabaseConnection()) {

          $allFeatures = $this->getAllFeaturesMock($this->user_id);
          // echo "registering device by POST feedback: \n " . $this->feedback;
          $this->addFeedback("not fully implemented feature load");
          $rsp = array(
            "ok" => true,
            "feedback" => $this->feedback,
            "features" => $allFeatures
          );
          echo json_encode($rsp);
        } else {
          $this->addFeedback("db connection could not be open ");
          $rsp = array(
            "ok" => false,
            "feedback" => $this->feedback,
            "POST" => $_POST
          );
          echo json_encode($rsp, JSON_PRETTY_PRINT);
        }
        exit();
        break;

      case ("js_savefeatures"):
        $successStatuses = $this->saveAllFeatures($this->user_id);
        $rsp = array(
          "ok" => true,
          "successStatuses" => $successStatuses,
          "feedback" => $this->feedback,

        );
        echo json_encode($rsp, JSON_PRETTY_PRINT);
        exit();
        break;


      case ("js_getalldevices"):
        // if (isset($_POST["js_getalldevices"])) {
        if ($this->createDatabaseConnection()) {
          $allDevices = $this->getAllDevices($this->user_id);
          // echo "registering device by POST feedback: \n " . $this->feedback;
          $allDevices['feedback'] = $this->feedback;
          echo json_encode($allDevices, JSON_PRETTY_PRINT);
        } else {
          $this->addFeedback("db connection could not be open ");
          $rsp = array(
            "feedback" => $this->feedback,
            "POST" => $_POST
          );
          echo json_encode($rsp, JSON_PRETTY_PRINT);
        }
        exit();
        break;



      case ("updatedevice"):
        if (post("updatedevice")) {
          $this->updateDevice();
        } else {
          $this->addFeedback("no updatedevice form data entry - no POST param");
        }
        $_ARR_response = array(
          'feedback' => "updating device by POST feedback: " . $this->feedback
        );
        echo json_encode($_ARR_response);
        exit();
        break;

      case ("registerdevice"):
        /**** */
        $success = false;
        if (post("registerdevice")) {
          $success = $this->doDeviceRegistration();
        } else {
          $success = false;
          $this->addFeedback("missing updatedevice form data entry - no POST param");
          $this->addFeedback(print_me($_POST, 1));
        }
        $_ARR_response = array(
          'ok' => $success,
          'feedback' => "registering device by POST feedback: \n " . $this->feedback
        );
        echo json_encode($_ARR_response);
        exit();
        break;

      case ("savepreferences"):
        $user_map_srv = req("user_map_srv", 0);
        $user_green_filter = req("user_green_filter", 1);
        $user_image_filter = req("user_image_filter", 0);
        if ($this->createDatabaseConnection()) {
          $sql = "UPDATE user 
SET 
user_map_srv = :user_map_srv,
user_green_filter = :user_green_filter,
user_image_filter = :user_image_filter
WHERE user_id = :id";
          $query = $this->db->prepare($sql);
          $query->bindValue(':id', $_SESSION["user_id"]);
          $query->bindValue(':user_map_srv', $user_map_srv);
          $query->bindValue(':user_green_filter', $user_green_filter);
          $query->bindValue(':user_image_filter', $user_image_filter);
          $query->execute();
        }
        $_SESSION['user_map_srv'] = $_GET["map"];
        echo "user_MAP_srv changed";
        exit();
        break;



      case ("delete"):
        $this->deleteDevice();
        $this->showPageLoggedIn();
        // $this->showPageAddToDevices();
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

  private function deleteMeUser()
  {
    if (!$this->user_is_logged_in) {
      // Hacker?
      $this->addFeedback("You need to be logged in in order to delete your account");
      return false;
    } else {
      if ($this->createDatabaseConnection()) {
        $sql = 'DELETE
FROM user
WHERE user_id = :user_id;
';
        $id = $this->user_id;
        $query = $this->db->prepare($sql);
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
    if (!$this->user_is_logged_in) {
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
        $query = $this->db->prepare($sql);
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
    if (!$this->checkDeviceUpdateData()) {
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
timebomb_password = :timebomb_password,
timebomb_status = :timebomb_status,
timebomb_time_set = :timebomb_time_set
WHERE device_id = :selected_id;
';
        // device_http_user_agent = :device_http_user_agent,

        $selected_id = $_POST["device_id"];
        $device_name = $_POST["device_name"];
        $device_description = $_POST["device_description"];
        //$device_http_user_agent = $_POST["device_http_user_agent"];
        $timebomb_password = $_POST["timebomb_password"];
        $timebomb_status = $_POST["timebomb_status"];
        $timebomb_time_set = $_POST["timebomb_time_set"];
        $query = $this->db->prepare($sql);
        $query->bindValue(':selected_id', $selected_id);
        $query->bindValue(':device_name', $device_name);
        $query->bindValue(':device_description', $device_description);
        //$query->bindValue(':device_http_user_agent', $device_http_user_agent);
        $query->bindValue(':timebomb_password', $timebomb_password);
        $query->bindValue(':timebomb_status', $timebomb_status);
        $query->bindValue(':timebomb_time_set', $timebomb_time_set);
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

    //($_POST['timebomb_time_set'], $this->timezone;
    if (isset($_POST["updatedevice"])) {
      // validating the input
      date_default_timezone_set($this->timezoneName);
      if (!empty($_POST['timebomb_time_set'])) {
        $dateOFF = DateTime::createFromFormat(MY_DATE_FORMAT, $_POST['timebomb_time_set'], $this->timezone);
        $timestamp = $dateOFF->format('U');
      } else {
        $timestamp = 0;
      }
      $this->timebomb_time_set_timestamp = $timestamp;

      if (
        !empty($_POST['device_name'])
        && strlen($_POST['device_name']) <= 24
        && strlen($_POST['device_name']) >= 2
        && preg_match('/^[a-zA-Z\d]{2,24}$/i', $_POST['device_name'])
        && !empty($_POST['timebomb_password'])
        && strlen($_POST['timebomb_password']) >= 3
        && strlen($_POST['timebomb_password']) <= 24
        && preg_match('/^[a-z\d]{3,24}$/i', $_POST['timebomb_password'])
        && !empty($_POST['device_ip'])
        && !empty($_POST['timebomb_time_set'])
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
        // -----------//         'timebomb_password' TEXT NOT NULL ,
        // -----------//         'timebomb_status' TEXT,
        // //         'timebomb_time_set' INTEGER,
        // -----------//         'time_last_active' DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        //                 );

        // CREATE UNIQUE INDEX `device_ip_UNIQUE` ON device ( `device_ip` ASC);
        // CREATE UNIQUE INDEX `device_name_UNIQUE` ON device ( `device_name` ASC);
      } elseif (empty($_POST['device_name'])) {
        $this->addFeedback("Empty device name");
      } elseif (empty($_POST['timebomb_password'])) {
        $this->addFeedback("Empty device Password");
      } elseif (strlen($_POST['timebomb_password']) < 3) {
        $this->addFeedback("Password has a minimum length of 4 characters");
      } elseif (!preg_match('/^[a-z\d]{3,24}$/', $_POST['timebomb_password'])) {
        $this->addFeedback("password does not fit the scheme: only a-z and numbers are allowed, 4 to 24 characters");
      } elseif (strlen($_POST['device_name']) > 64 || strlen($_POST['device_name']) < 2) {
        $this->addFeedback("devicename cannot be shorter than 2 or longer than 64 characters");
      } elseif (!preg_match('/^[a-zA-Z\d]{2,64}$/', $_POST['device_name'])) {
        $this->addFeedback("device name does not fit the name scheme: only a-z and numbers are allowed, 2 to 64 characters");
      } elseif (empty($_POST['device_ip'])) {
        $this->addFeedback("device ip cannot be empty");
      } elseif (empty($_POST['timebomb_time_set'])) {
        $this->addFeedback("timebomb_time_set cannot be empty");
      } elseif ($timestamp <= time()) {
        $this->addFeedback("timebomb_time_set cannot be in the past");
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



  /**
   * Creates a PDO database connection (in this case to a SQLite flat-file database)
   * @return bool Database creation success status, false by default
   */
  private function createDatabaseConnection()
  {
    if (isset($this->db)) {
      return true;
    }

    try {
      $this->db = getGLOB_DatabaseConnection();
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
    } elseif (!empty($_SESSION['user_id']) && ($_SESSION['user_is_logged_in'])) {
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
    $this->user_id = $_SESSION['user_id'];
    $this->user_name = $_SESSION['user_name'];
    $this->user_map_srv = $_SESSION['user_map_srv'];
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
        return $this->createNewDevice();
      } else {
        $this->addFeedback("Problem with db connection \n");
        return false;
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
    $sql = 'SELECT user_id, user_name, user_password_hash, user_timezone, user_map_srv
FROM user
WHERE user_name = :user_name
LIMIT 1';
    $query = $this->db->prepare($sql);
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
        $this->user_map_srv = $result_row->user_map_srv;
        $_SESSION['user_map_srv'] = $result_row->user_map_srv;
        $this->user_id = $result_row->user_id;
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
      && in_array($_POST['timezone'], timezone_identifiers_list())
    ) {
      // only this case return true, only this case is valid
      return true;
    } elseif (empty($_POST['user_name'])) {
      $this->addFeedback("Empty Username");
    } elseif (empty($_POST['user_password_new']) || empty($_POST['user_password_repeat'])) {
      $this->addFeedback("Empty Password");
    } elseif (empty($_POST['timezone'])) {
      $this->addFeedback("Empty timezone");
    } elseif (!in_array($_POST['timezone'], timezone_identifiers_list())) {
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
    if (isset($_POST["registerdevice"])) {
      // validating the device input
      if (
        !empty($_POST['device_name'])
        && strlen($_POST['device_name']) <= 24
        && strlen($_POST['device_name']) >= 2
        && !empty($_POST['device_ip'])
        && !empty($_POST['timebomb_time_set'])
        && $this->validateFunctionalitiesRegistration($_POST)
      ) {
        /*//$fncltyTbl = new TableObject("functionality");
	//$fncltyNms = $fncltyTbl->setUpColumnNamesFromDB();	
///functionaliy->runValidation())
foreach($fncltyNms as $fnm){
		$fncltyTbl->validate($fnm);p
		} 
	*/
        // only this case return true, only this case is valid
        return true;
      } elseif (empty($_POST['device_name'])) {
        $this->addFeedback("Empty device name");
      } elseif (strlen($_POST['device_name']) > 64 || strlen($_POST['device_name']) < 2) {
        $this->addFeedback("devicename cannot be shorter than 2 or longer than 64 characters");
        // } elseif (!preg_match('/^{2,64}$/', $_POST['device_name'])) {
        //     $this->addFeedback("device name does not fit the name scheme: only a-z, A-Z  and numbers are allowed, 2 to 64 characters");
      } elseif (empty($_POST['device_ip'])) {
        $this->addFeedback("device ip cannot be empty");
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
   * 
   * @return bool Success status of registration
   */
  public function validateFunctionalitiesRegistration($_DATA)
  {
    date_default_timezone_set($this->timezoneName);
    if (!empty($_DATA['timebomb_time_set'])) {
      $dateOFF = DateTime::createFromFormat(MY_DATE_FORMAT, $_DATA['timebomb_time_set'], $this->timezone);
      $timestamp = $dateOFF->format('U');
    } else {
      $timestamp = 0;
    }
    $this->timebomb_time_set_timestamp = $timestamp;
    //!!!! check if works date time set is later than now
    $valid = true;
    if (in_($_DATA, "timebomb")) {
      $valid = $valid
        && !empty($_DATA['timebomb_password_new'])
        && strlen($_DATA['timebomb_password_new']) >= 3
        && strlen($_DATA['timebomb_password_new']) <= 24
        && !empty($_DATA['timebomb_password_repeat'])
        && ($_DATA['timebomb_password_new'] === $_DATA['timebomb_password_repeat'])
        && !empty($_DATA['timebomb_time_set'])
        && $timestamp > time();
    } elseif ($timestamp <= time()) {
      $this->addFeedback("timebomb_time_set cannot be in the past");
    } elseif (empty($_DATA['timebomb_password_new']) || empty($_DATA['timebomb_password_repeat'])) {
      $this->addFeedback("Empty device Password");
    } elseif ($_DATA['timebomb_password_new'] !== $_DATA['timebomb_password_repeat']) {
      $this->addFeedback("Password and password repeat are not the same");
    } elseif (strlen($_DATA['timebomb_password_new']) < 3) {
      $this->addFeedback("Password has a minimum length of 3 characters");
    } elseif (!preg_match('/^[a-z\d]{3,24}$/', $_DATA['timebomb_password_new'])) {
      $this->addFeedback("password does not fit the scheme: only a-z and numbers are allowed, 3 to 24 characters");
    } elseif (empty($_DATA['timebomb_time_set'])) {
      $this->addFeedback("timebomb_time_set cannot be empty");
    }
    if (post("timebomb_explosion_radius")) { }
    if (post("timebomb_explosion_effect")) { }


    if (post("geiger")) { }
    if (post("inventory")) { }

    if (post("radar")) { }
    return $valid;
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

    $sql = 'SELECT user_id FROM user where user_name = :user_name;
'; // check if current ip is registered in devices or name is registered in users


    $dbcon = $this->db;
    $query = $dbcon->prepare($sql);
    $query->bindValue(':user_name', $user_name);
    // $query->bindValue(':user_ip', $user_ip);
    $query->execute();

    // As there is no numRows() in SQLite/PDO (!!) we have to do it this way:
    // If you meet the inventor of PDO, punch him. Seriously.
    $result_row = $query->fetchObject();
    if ($result_row) {
      $this->addFeedback("Sorry, that username is already taken. Please choose another one.\n");
    } else {

      $sql = 'INSERT INTO user (user_name, user_password_hash ,user_ip,user_timezone)
VALUES(:user_name, :user_password_hash,:user_ip,:user_timezone)';
      $query = $this->db->prepare($sql);
      $query->bindValue(':user_name', $user_name);
      $query->bindValue(':user_password_hash', $user_password_hash);
      $query->bindValue(':user_ip', $user_ip);
      $query->bindValue(':user_timezone', $user_timezone);
      // PDO's execute() gives back TRUE when successful, FALSE when not
      // @link http://stackoverflow.com/q/1661863/1114320
      $registration_success_state = $query->execute();

      if ($registration_success_state) {
        $sql = 'SELECT user_id FROM user where user_name = :user_name;
'; // check if current ip is registered in devices or name is registered in users


        $dbcon = $this->db;
        $query = $dbcon->prepare($sql);
        $query->bindValue(':user_name', $user_name);
        // $query->bindValue(':user_ip', $user_ip);
        $query->execute();
        $result_row = $query->fetchObject();


        $this->addFeedback("Your account has been created successfully. You can now log in.");
        $_SESSION['user_id'] = $result_row->user_id;;
        $_SESSION['user_name'] = $user_name;
        $_SESSION['user_timezone'] = $user_timezone;
        $_SESSION['user_is_logged_in'] = true;
        $this->user_map_srv = 0;
        $_SESSION['user_map_srv'] = 0;
        $this->user_id = $result_row->user_id;
        $this->timezoneName = $user_timezone;
        $this->timezone = new DateTimeZone($this->timezoneName);
        $this->user_is_logged_in = true;
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
    $device_name = htmlentities($_POST['device_name'], ENT_QUOTES);
    $device_ip = htmlentities($_POST['device_ip'], ENT_QUOTES);
    $device_description = htmlentities($_POST['device_description'], ENT_QUOTES);
    // $device_functionalities = ?????
    $device_session_id_from_logged_user_cookie_modified = $_COOKIE['PHPSESSID'] . "_device_name_" . $device_name;
    // $this->addFeedback(
    //     print_r($this,true)
    // );
    date_default_timezone_set($this->timezoneName);
    $date_now = date('Y-m-d\TH:i:s'); // add seconds to datetime-locale provided value
    $user_id_fk = $this->user_id;

    //FUNCTIONALITY DEPENDANT-------------------------------------------------------
    $timebomb_password = htmlentities($_POST['timebomb_password_new'], ENT_QUOTES);
    $timebomb_time_set = $_POST['timebomb_time_set'];
    //----------end ---------------- FUNCTIONALITY DEPENDANT-------------------------------------------------------

    $sql = 'SELECT device_session_id,device_ip,device_name FROM device
WHERE device_name = :device_name 
OR device_ip = :device_ip
OR device_session_id = :device_session_id';
    $dbcon = $this->db;
    $query = $dbcon->prepare($sql);
    $query->bindValue(':device_name', $device_name);
    $query->bindValue(':device_session_id', $device_session_id_from_logged_user_cookie_modified);
    $query->bindValue(':device_ip', $device_ip);
    $query->execute();

    // As there is no numRows() in SQLite/PDO (!!) we have to do it this way:
    // If you meet the inventor of PDO, punch him. Seriously.
    $result_row = $query->fetchObject();
    if ($result_row) {
      $given = array(
        'device_name' => $device_name,
        'device_ip' => $device_ip,
        'device_session_id' =>  $device_session_id_from_logged_user_cookie_modified
      );
      $a = get_object_vars($result_row);
      foreach ($a as $k => $v) {
        if ($v == $given[$k]) {
          $this->addFeedback("$k : $v is already taken. Please choose another one.");
        }
      }
    } else {
      if (isset($_POST['latitude'], $_POST['longitude'])) {
        $location = $_POST['latitude'] . "/" . $_POST['longitude'];
      } else {
        $location = "no location";
      }

      $latitude = post("latitude", "no location", "no location");
      $longitude = post("longitude", "no location", "no location");

      //insert point
      $sql = 'INSERT INTO point(point_longitude,point_latitude)
VALUES (:longitude,:latitude);'; //***
      $query = $this->db->prepare($sql);
      $query->bindValue(":longitude", $longitude);
      $query->bindValue(":latitude", $latitude);
      $query->execute();

      //   INSERT INTO <tablename> (<column1>, <column2>, ..., <columnN>)
      //SELECT <value1>, <value2>, ..., <valueN> FROM ...

      $sql = 'INSERT INTO device 
(device_id,device_name, 
device_ip,
device_description, 
user_id_fk, time_last_active,
device_session_id, device_location,device_fk_location_point)
VALUES
(null ,:device_name, 
:device_ip,
:device_description,
:user_id_fk, :time_last_active,
:device_session_id, :device_location,last_insert_rowid())';
      $query = $this->db->prepare($sql);



      $query->bindValue(':device_location', $location);
      $query->bindValue(':device_name', $device_name);

      $query->bindValue(':device_ip', $device_ip);
      $query->bindValue(':device_description', $device_description);

      $query->bindValue(':user_id_fk', $user_id_fk);
      $query->bindValue(':time_last_active', $date_now);
      $query->bindValue(':device_session_id', $device_session_id_from_logged_user_cookie_modified);
      $registration_success_state = $query->execute();


      //----------------Timebomb
      $timebomb_mapentity_id_fk = 1; //musisz insertnac najpierw map entity
      $sql = 'INSERT INTO timebomb
(timebomb_device_id_fk,
  timebomb_status ,
  timebomb_time_set ,
  timebomb_mapentity_id_fk ,
  timebomb_password)
VALUES
(
last_insert_rowid(),
 :timebomb_status ,
 :timebomb_time_set ,
 :timebomb_mapentity_id_fk ,
 :timebomb_password);';
      $query = $this->db->prepare($sql);

      $query->bindValue(':timebomb_password', $timebomb_password);
      $query->bindValue(':timebomb_status', 'created');
      $query->bindValue(':timebomb_time_set', $timebomb_time_set);
      $query->bindValue(':timebomb_mapentity_id_fk', $timebomb_mapentity_id_fk);


      //dodgy
      $_SESSION['device_session_id'] = $device_session_id_from_logged_user_cookie_modified;
      $expire = $this->timebomb_time_set_timestamp;
      setcookie(
        "device_session_id",
        $this->device_session_id,
        $expire,
        "",
        "",
        false,
        true
      );

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
      //$this->user_id = $this->user_id; // ???????????
      $allDevices = $this->getAllDevices($this->user_id);

      $this->columns = $allDevices['columnNames'];
      $this->resultset = $allDevices['rows'];
      View("ViewStartHTML", $this);

      View('ViewControlPanel', $this);
      View('ViewCPscripts', $this);
    } else {
      $this->addFeedback("\nsorry cannot display all your devices due to db conn problem");
      View("ViewStartHTML", $this);
    }
  }

  /**
   * Simple demo-"page" with the login form.
   * In a real application you would probably include an html-template here, but for this extremely simple
   * demo the "echo" statements are totally okay.
   */
  private function showPageLoginForm()
  {
    View("ViewStartHTML", $this);
    View("ViewLoginForm", $this);
  }

  /**
   * Simple demo-"page" with the registration form.
   * In a real application you would probably include an html-template here, but for this extremely simple
   * demo the "echo" statements are totally okay.
   */
  private function showPageRegistration()
  {

    View("ViewStartHTML", $this);
    View("ViewRegisterUserForm", $this);
  }
}

// run the application
$application = new OneFileLoginApplication();
if ($application->performMinimumRequirementsCheck()) {
  $application->runApplication();
}
