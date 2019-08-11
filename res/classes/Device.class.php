<?php

class Device extends TableObject{
    public $device_is_logged_in = false;
    public $tablename = "device";

    public function __construct(PDO $db,array $columnNames)
    {
        parent::__construct($db,"device",$columnNames);
        $this->tablename = "device";
    }

    

    public function getAllByUser(int $user_id)
    {
        $conn = $this->db;
        $sql = "SELECT * FROM device
                INNER JOIN point 
                ON device.device_fk_location_point  = point.point_id
                WHERE registered_by_user = :user_id"; // WHERE class = '$class'"; later  -> WHERE user_creator_id = :logged_user_id

        $query = $conn->prepare($sql);
        $query->bindValue(':user_id', $user_id);

        $query->setFetchMode(PDO::FETCH_ASSOC);
        $query->execute();
        
        return $this::toTableReadyArray($query);
    }


  public function IsRegisteredDevice($ip,$sess_token_from_cookie= NO_COOKIE)
  {
    if ($this->device_is_logged_in) {//TODO:CHANGE TO IS LOADED OR IS REGISTERED
      return true;
    }
    //what if no cookie on the device but it is registered(e.g.different browser opened)
    // set cookie when retrieved device by using ip?



    // you could check first for session vars here and for cookie, then  compare them against the db

   $impcols = array(
        'device_id',
        'device_ip',
        'device_name',
        'device_status',
        'device_password',
        'time_set',
        'user_timezone',
        'device_session_id',
        'device_location',
        'time_last_active',
        'point_longitude',
        'point_latitude',
      );
    
      $sql = 'SELECT
device_id,device_ip,device_name,device_status,
device_password,time_set,user_timezone,
device_session_id,device_location,
time_last_active, point_longitude, point_latitude
FROM device
INNER jOIN point 
ON  point.point_id = device.device_fk_location_point
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
        // $_SESSION['device_status'] = $result_row->device_status;
        // $_SESSION['time_set'] = $result_row->time_set;
        // $_SESSION['device_password'] = $result_row->device_password;
        $_SESSION['timezone'] = $result_row->user_timezone;

        // $this->device_status = $result_row->device_status;
        //$this->device_session_id = $result_row->device_session_id;
        $this->timezoneName = $result_row->user_timezone;
        $this->timezone = new DateTimeZone($this->timezoneName);
        $this->device_is_logged_in = true;
        //$this->device_id = $result_row->device_id;
        //$this->device_password = $result_row->device_password;
        $this->device_time_set = $result_row->time_set;
        //$this->time_last_active = $result_row->time_last_active;
        $status_old = $result_row->device_status;
        // $this->device_status = $result_row->device_status;

        $dateOFF = DateTime::createFromFormat(MY_DATE_FORMAT, $result_row->time_set, $this->timezone);
        $this->time_set_timestamp = $dateOFF->format('U');
        //REFRESH COOKIE   
        $expire = $this->time_set_timestamp;
        setcookie(
          "device_session_id",
          $this->device_session_id,
          $expire,
          "",
          "",
          false,
          true
        );

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
              $query = $this->db_connection->prepare($sql);
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
        } elseif ($this->time_set_timestamp <= time()) {
          $this->addFeedback("this device just detonated");
          $status_new = "detonated";
          $this->device_status_new = $status_new; // used by JSsettings.php
        }
        $this->device_status_new = $status_new;

        //-------------------------------------------------------------------------------------
        //END
        //-------------------------------------------------------------------------------------

        //everything should be updated here based on the _new variables provided by device_type class
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
        $query->bindValue(':device_status', $status_new);
        $query->bindValue(':device_id', $this->device_id);
        $query->bindValue(':connection_ip', $ip);
        $query->bindValue(':device_location', $location_new);
        $query->execute();

        return true;
      } else {
        $this->addFeedback("($ip) is not registered yet in db.");
      }
      // default return
      return false;
    }


}

?>