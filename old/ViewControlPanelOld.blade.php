<?php
//needed visible
//name 	description  ip 	time_last_active 	location  functionalities	 / 	password  status 	timebomb_time_set 
      //                                                    |
                                                    //separate loop or query?


// it will be an array passed here no $this
$index_link = $_SERVER['SCRIPT_NAME'];
$connection_ip = $ip; //$this->getIP(DEBUG_MODE);
//$user_id = $this->user_id;
//$resultset = $this->resultset;
//$displayedColumns = $this->columns;
date_default_timezone_set($timezoneName);
$minimum_timebomb_time_set_date = date(MY_DATE_FORMAT, time());
$default_timebomb_time_set_date = date(MY_DATE_FORMAT, time() + 60 * 30); //in 30 minutes
$column_name_prefix = "device_";
$nonDisplayed = array("device_id", "registered_by_user", "device_session_id", "device_http_user_agent", "point_longitude", "point_latitude","fk_location_point","point_id");
$nonEditables = array("device_id", "registered_by_user", "time_last_active", "device_location", "device_session_id");


//TODO 10: 
//loop through $visibleColumns = array_diff($displayedColumns,$nonDisplayed) instead checking on each iteration
// possibly save for each column a parameter shoul it be displayable in DATABASE already

if (isset($_GET['all'])) {
  $nonDisplayed = array();
}else{
  $displayedColumns = array_values(array_diff($columns,$nonDisplayed));
  //print_me($displayedColumns);exit;
}
$devicesExist = (count($resultset) > 0);

?>

<script>
  //SETTINGS SCRIPT 
  user = {
    prefs: {
      user_map_srv: <?= isset($_SESSION["user_map_srv"]) ? $_SESSION["user_map_srv"] : 0 ?>,
      user_green_filter: <?= isset($_SESSION["green_filter"]) ? $_SESSION["green_filter"] : 1 ?>,
      user_image_filter: <?= isset($SESSION["image_filter"]) ? $_SESSION["image_filter"] : 0 ?>,
      user_map_default_zoom: <?= isset($SESSION["user_map_default_zoom"]) ? $_SESSION["user_map_default_zoom"] : 15 ?>
    },
    savePreferences() {
      getparams = "?action=savepreferences";
      Object.keys(this.prefs).forEach(prefname => {
        getparams += "&" + prefname + "=" + this.prefs[prefname];
      });
      fetch(baseurl + getparams)
        .then(x => x.text())
        .then(t => say(t));
    }
  }

  tableData = <?= json_encode($resultset, JSON_PRETTY_PRINT); ?>;

  baseurl = "<?= $_SERVER['SCRIPT_NAME'] ?>";

  UpdateUrl = baseurl + "?action=updatedevice";
  newDeviceUrl = baseurl + "?action=registerdevice";
  say("settings script OK");
</script>
<a href="<?= $index_link ?>?action=logout"><button>Log out</button></a>
  <a href="<?= $index_link . '?action=deleteme' ?>" onclick="if(!confirm('are you sure? All your devices will be deleted too'))
  {event.stopPropagation();event.preventDefault()}else{}">
    <button>Delete Account</button></a>

  <button onclick="user.savePreferences();">Save Preferences <span class="more-info-btn" onclick="this.classList.toggle('more-info-show')">
      <span class="more-info-content">
        save preferences such as green filter , map server/style
      </span></span></button>
<div class="hud">
  

  <br>
  <div id="tableWrapper">
    <?php
    # If records found
    if ($devicesExist) {
      ?>
      <h1>All Devices Registered By You</h1>
      <div>Click on field to Edit, press OK after editing a field, then Save to fetchToUpdate the device in database. <br />
        You can edit only one field at a time. If field is <span class="KBdisplay field-non-editable">greyed out</span> it is impossible to change the value. Delete the device and create new instead
      </div>

      <!-- DYNAMICALLY creates css for mobile devices to represent rows as chunks/cards -->

      <?php
      echo "<style>@media only screen and (max-width: 760px),
            (min-device-width: 768px) and (max-device-width: 1024px) {
            ";
            $visible_idx = 0;//index only for visibles 
      for($idx = 0 ; $idx < count($displayedColumns);$idx++){
        $column_name = $displayedColumns[$idx];
        if (in_array($column_name, $nonDisplayed)) {
          //do not increase the counter
          continue;
        }
        
        $column_wout_prefix = str_replace($column_name_prefix, "", $column_name);
        $cssColNo = $visible_idx+1;
        echo "td:nth-of-type($cssColNo):before {
              content: '$column_wout_prefix';}";
          $visible_idx++;
        }
      $cssColNo++;
      echo "td:nth-of-type($cssColNo):before {
              content: 'OPTIONS';}}</style>";
      ?>




      <table id="tableToEdit" class="table table-bordered">
        <thead>
          <tr class='info'>
            <?php foreach ($displayedColumns as $k => $column_name) :
              if (in_array($column_name, $nonDisplayed)) {
                continue;
              }
              ?>

              <th> <?php
                    $column_wout_prefix = str_replace($column_name_prefix, "", $column_name);
                    echo $column_wout_prefix;

                    ?> </th>
            <?php endforeach; ?>
            <th>Save Changes</th>

          </tr>
        </thead>
        <tbody>



          <?php

          // output data of each row
          foreach ($resultset as $index => $row) {
            $column_counter = 0;

            include("DeviceAsTableRow.blade.php");
          } ?>

        </tbody>
      </table>
    </div>
    <a href="<?= $index_link ?>"><button onclick="">Refresh</button></a>
    <button onclick="watchmode.toggle(this)">Turn Watch Mode on</button>
    <br><br><br>

    <div id="mapDIV" class="">

    </div>
    <button id="mapCHGbtn">change Map server:cartodb-basemaps DARK a</button>
    <select id="mapCHGselect"></select>
    <button id="FilterTogglerBtn">Green Filter:On</button>
    <!-- <select id="kernel" name="kernel">
        <option>none</option>
        <option selected>sharpen</option>
        <option value="sharpenless">sharpen less</option>
        <option>blur</option>
        <option>shadow</option>
        <option>emboss</option>
        <option value="edge">edge detect</option>
      </select> -->
    <audio id="popsound" src="sounds/pop.mp3">
      Sorry, sounds are not supported
    </audio>

  <?php



  } else { ?>
    <h4> You didn't add any devices yet - add one below (by default it's ip is this device ip -
      <?= $connection_ip;
    }
    ?>
</div>
</h4>

<!-- <script src="js/v5-3-0_build_ol_formatted.js" type="text/javascript"></script> -->
<div class="centerpanel">
  <h2>New Device Registration</h2>
  <style>

  </style>
  <!--  method="post" action="<?= $index_link ?>?action=registerdevice" -->
  <form id="new_device_form" name="registerform" onsubmit="return false">
    <label for="device_name">
      device_name:<span class="more-info-btn" onclick="this.classList.toggle('more-info-show')">
        <span class="more-info-content">
          unique name that will allow you to identify device on map<br>
          choose something simple and short.
        </span></span>
    </label>
    <input id="device_name" type="text" pattern="^.{2,64}$" name="device_name"  value="qqq" required />
    <span class="validity">* required</span>
    <label for="device_description">
      device_description:
    </label>
    <textarea id="device_description" name="device_description" rows="5" cols="60" name="description" placeholder="...put something more in here in case device has no location services or anything that helps you identify it in table "></textarea><br>

    <label for="is_sending_device_location">
      track device location:<span class="more-info-btn" onclick="this.classList.toggle('more-info-show')">
        <span class="more-info-content">
          if you turn it on device will be tracked and shown on map<br>
          if it will be a device that is different than this one
          <br>- you will need to confirm locating again.
        </span></span>
    </label>
    <input id="is_sending_device_location" type="checkbox" name="is_sending_device_location" onclick="checkLocation(event)" />
    
    <label for="device_ip">
      Device IP(default this one):<br>
      or pseudo ip for quick setup
      <span class="more-info-btn" onclick="this.classList.toggle('more-info-show')">
        Quick setup
        <span class="more-info-content">
          you can enter any combination of digits or letters, and later in field
          without login just assign device to this setup by entering it on login screen.<br>
          Remember: this value will change to the actual device's IP after assignment.
        </span></span>

  
</label>  
    <input id="device_ip" type="text" name="device_ip" title="IP address of device" required autocomplete="off" <?= 'value=' . $connection_ip . '' ?> />
 
   <span class="validity">* required</span>
 
  
 <style>
 
  .flex-row>label{
    display: flex;
    flex-direction: column;
    align-items: center;
  }
    /* HIDE RADIO */
    .functionality-checkbox {
      position: absolute;
      opacity: 0;
      width: 0;
      height: 0;
      margin: 10px;
    }

    /* IMAGE STYLES */
    .functionality-checkbox+img {
      opacity: 0.4;
    cursor: pointer;
    border-color: black;
    /* margin: 5px; */
    border: 3px solid #80808036;
    border-style: outset;
    /* border-radius: 16%; */
    background: #000000;
    }

    /* CHECKED STYLES */
     .functionality-checkbox:checked+img {
	outline-color:green;
	outline-style:  solid;
	outline-width: 1px;
      opacity: 1;
    cursor: pointer;
    border-color: green;
    /* margin: 5px; */
    border: 3px solid green;
    border-style: outset;

    /* border-radius: 16%; */
    background: #000000;
    }

    .btn-checkbox-img {
      width: 40px;
    /* border: 2px solid; */
    margin: 5px;
    display: block;
    }
  </style>
onclick display apprpopriate functionality settings
effects may be fubctionalities with accompanying mapentity radius
input for radius of effect or assign draw area where effect takes place
functionality type :inventory for items or selling
<div class="flex-row">
      <label>
        <input type="checkbox" class="functionality-checkbox" name="geiger" value="true" checked></input>
        <img class="btn-checkbox-img" src="img/geiger.png">

   <span class="more-info-btn" onclick="this.classList.toggle('more-info-show')">
        Geiger Counter
        <span class="more-info-content">
Geiger counter gets the location of radioactive map entities and by calculating
 distance to their area indicates the radiation level. Needs MAP and ol modules for calculations
        </span></span>

  </label>

      <label>
        <input type="checkbox" class="functionality-checkbox" name="radar" value="true"></input>
        <img class="btn-checkbox-img" src="img/radar.png">
<span class="more-info-btn" onclick="this.classList.toggle('more-info-show')">
        Radar
        <span class="more-info-content">
Radar gets the location of any registered devices and  shows them on map. Radars may have different ranges set below. Needs MAP and ol modules for calculations
        </span></span>
      </label>

<label>
        <input type="checkbox" class="functionality-checkbox" name="timebomb" value="true"></input>
        <img class="btn-checkbox-img" src="img/timebomb.png">
<span class="more-info-btn" onclick="this.classList.toggle('more-info-show')">
        Timebomb
        <span class="more-info-content">
<pre>
Timebomb - not visible on Radars .
Options
May be nuclear , dirty or simple .
 Creates radioactive map entity around after explosion. 
Destroys devices or inventory around . 
</pre>
        </span></span>
      </label>

<label>
        <input type="checkbox" class="functionality-checkbox" name="inventory" value="true"></input>
        <img class="btn-checkbox-img" src="img/inventory.png">
<span class="more-info-btn" onclick="this.classList.toggle('more-info-show')">
        Inventory
        <span class="more-info-content">
Inventory . There's option to extend it to Shop.
        </span></span>
      </label>


</div>



<h2>Functionality Settings:</h2>

<div id="RadarFunctionalitySettingsDIV" class="centerpanel" style="display:flex">

<label for="radar_radius">
      Radar radius<span  class="more-info-btn" onclick="this.classList.toggle('more-info-show')">
        <span class="more-info-content">
          (3-24 characters, a-z 0-9)
          case insensitive, no special characters
          <br>stops/unlocks the device
        </span></span>
    </label>
<input id="radar_radius" name="radar_radius" placeholder="1-10" value="1" title="how far extends the radar cone" ></input>

</div>


<div id="timebombFunctionalitySettingsDIV" class="centerpanel" style="display:flex">
<label for="timebomb_password_new">
      Device Password <span  class="more-info-btn" onclick="this.classList.toggle('more-info-show')">
        <span class="more-info-content">
          (3-24 characters, a-z 0-9)
          case insensitive, no special characters
          <br>stops/unlocks the device
        </span></span>
    </label>
    <input value="123" id="timebomb_password_new" class="login_input" type="password" name="timebomb_password_new" pattern="[a-z0-9]{3,32}" required autocomplete="off" />
    <span class="validity">* required </span>
    <label for="timebomb_password_repeat">
      Repeat password
    </label>
    <input value="123" id="timebomb_password_repeat" class="login_input" type="password" name="timebomb_password_repeat" pattern="[a-z0-9]{3,32}" required autocomplete="off" />
    <span class="validity">* required</span>

<div id="counterCNT" class="counterCNT">
      <div id="counterMeas" class="counter">
        <span id="counter" class="digits">

          <span id="counter_hour">00</span>
          <span id="counter_colon1">:</span>
          <span id="counter_min">00</span>
          <span id="counter_colon2" class="flash">:</span>
          <span id="counter_sec">00</span>
        </span>
      </div>
<label for="timebomb_time_set">
      timebomb_time_set(this is the time device counts down to):
    </label>

<input id="timebomb_time_set" class="timebomb_time_set" type="datetime-local" name="timebomb_time_set" pattern="[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}" min="<?= $minimum_timebomb_time_set_date ?>" value="<?= $default_timebomb_time_set_date ?>" required>
            <span class="validity"></span>

<label for="effectintensity">
      Effect Intensity
<span  class="more-info-btn" onclick="this.classList.toggle('more-info-show')">
        <span class="more-info-content">
        press button to  mark the expl. In case of radiation
radius represents maximum level of rafiation level and geiger counters will react before the boundary

        </span></span>
    </label>

<input id="effectradius" class="" type="telephone" name="effect_radius" >
            <span class="validity"></span>

<label>


<label for="effectradius">
      Effect Radius
<span  class="more-info-btn" onclick="this.classList.toggle('more-info-show')">
        <span class="more-info-content">
        press button to  mark the explosion radius on the map. In case of radiation
radius represents area of set effect intensity maximum level of rafiation level and geiger counters will react before the boundary

        </span></span>
    </label>

<input id="effectradius" class="" type="telephone" name="effect_radius" >
            <span class="validity"></span>

<label>
        <input type="checkbox" class="image-checkbox" name="effect" value="Radiation"></input>
        <img class="btn-checkbox-img" src="img/radar.png">
<span class="more-info-btn" onclick="this.classList.toggle('more-info-show')">
        Radiation Effect
        <span class="more-info-content">
radiation level in proximity increases 
        </span></span>
      </label>

</div>
 
    <!-- <input type="submit" name="register" value="Register" onclick="sendNewDevice()"/> -->
    <button onclick="return sendNewDevice()">Register</button>

    
  </form>


</div>

</div>
Minimal Date ( NOW ):
<pre id="timebomb_time_setMIN" style="display:block">
<?= $minimum_timebomb_time_set_date ?></pre>

</div>

<!-- all these needed anyway for form addnewdevice -->
<link rel="stylesheet" href="flatpickr.css">
<script type="text/javascript" src="js/flatpickr.js"></script>
<script type="text/javascript" src="js/clockController.js"></script>

<script>
  function jsonTextErr(x) {
    try {
      return x.json();
    } catch (e) {
      say(e.stack);
      say(x.text());
      return x.text();
    }
  }
  say("utils ok")
</script>

  <script src="js/v5-3-0_build_ol.js"></script>
  <link rel="stylesheet" href="ol.css">
  <script src="js/focus_preventscroll_polyfill.js"></script>
  <script src="js/watchModeModule.js"></script>
  <script src="js/olMapModule.js"></script>
  <script src="js/tableEditModule.js"></script>

<script src="js/devLocateModule.js"></script>
<script src="js/sendNewDeviceModule.js"></script>