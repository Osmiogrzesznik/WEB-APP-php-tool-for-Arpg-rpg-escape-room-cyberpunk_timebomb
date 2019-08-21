<?php

function runApplicationzarys()
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
      include("res/views/ViewBombInterface.html");
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

function routeUserNotLoggedInActions($action){
  
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
    break;

  case ("getsettings"):
    include("JSsettings.php");
    //*** change to View?
    break;

  case ("locate"):
    if ($this->IsRegisteredDevice()) {
      $_ARR_response = array(
        'timebomb_status' => $this->timebomb_status_new,
        'timebomb_time_set' => $this->device_timebomb_time_set,
        'feedback' => getGlobalFeedback()
      );
    } else {
      $_ARR_response = array(
        'feedback' => getGlobalFeedback()
      );
    }
    echo json_encode($_ARR_response);
    break;

  case ("password"):
    $password_ok = $this->checkDevicePasswordCorrectness();
    $_ARR_response = array(
      'timebomb_status' => $this->timebomb_status_new,
      'timebomb_time_set' => $this->device_timebomb_time_set,
      'password_ok' => $password_ok,
      'feedback' => getGlobalFeedback()
    );
    echo json_encode($_ARR_response);

    break;


  case ("registerForm"):

    $this->showPageRegistration();
    //$this->doRegistration();
    break;

  case ("registerUser"):
    if (isset($_POST["register"])) {

      $this->doRegistration();
    }
    $this->performUserLoginAction();

    $this->showPageLoggedIn();
    break;

// dont show nothing yet, it will be taken care of later down in the code
// if user is logged in and device was registered showpageloggedin will show all db
endswitch;
exit();//for now theres no need to do anything else all actions are invoked in the background
}




function routeUserLoggedInActions($action)
{
  switch ($action): case ("updatedevice"):
      if (post("updatedevice")) {
        $this->updateDevice();
      } else {
        addFeedback("no updatedevice form data entry - no POST param");
      }
      $_ARR_response = array(
        'feedback' => "updating device by POST feedback: " . getGlobalFeedback()
      );
      echo json_encode($_ARR_response);
      exit();
      break;

    case ("registerdevice"):
      $success = false;
      if (post("registerdevice")) {
        $success = $this->doDeviceRegistration();
      } else {
        $success = false;
        addFeedback("missing updatedevice form data entry - no POST param");
        addFeedback(print_me($_POST, 1));
      }
      $_ARR_response = array(
        'ok' => $success,
        'feedback' => "registering device by POST feedback: \n " . getGlobalFeedback()
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
        $query = $this->db_connection->prepare($sql);
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

    case ("js_getalldevices"):
      // if (isset($_POST["js_getalldevices"])) {
      if ($this->createDatabaseConnection()) {
        $allDevices = $this->getAllDevices($this->user_id);
        // echo "registering device by POST feedback: \n " . getGlobalFeedback();
        $allDevices['feedback'] = getGlobalFeedback();
        echo json_encode($allDevices, JSON_PRETTY_PRINT);
      } else {
        addFeedback("db connection could not be open ");
        $rsp = array(
          "feedback" => getGlobalFeedback(),
          "POST" => $_POST
        );
        echo json_encode($rsp, JSON_PRETTY_PRINT);
      }
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
