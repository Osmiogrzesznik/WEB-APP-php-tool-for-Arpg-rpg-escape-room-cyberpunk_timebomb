<?php
// it will be an array passed here no $this
$index_link = $_SERVER['SCRIPT_NAME'];
$connection_ip = $ip;//$this->getIP(DEBUG_MODE);
//$user_id = $this->user_id;
//$resultset = $this->resultset;
//$columns = $this->columns;
date_default_timezone_set($timezoneName);
$minimum_time_set_date = date(MY_DATE_FORMAT, time());
$default_time_set_date = date(MY_DATE_FORMAT, time()+60*30);//in 30 minutes
$column_name_prefix = "device_";
$nonVisibles = array("device_id","registered_by_user","device_session_id","device_http_user_agent");
$nonEditables = array("device_id","registered_by_user","time_last_active","device_location","device_session_id");

if (isset($_GET['all'])){
  $nonVisibles=array();
}
$devicesExist = (count($resultset) > 0);

?>

<script>
  //SETTINGS SCRIPT 
  tableData = <?= json_encode($resultset, JSON_PRETTY_PRINT); ?>;
  usr_map_srv = <?= isset($_SESSION["user_map_srv"]) ? $_SESSION["user_map_srv"] : 0 ?>;
  baseurl = "<?= $_SERVER['SCRIPT_NAME'] ?>";
  
  UpdateUrl = baseurl + "?action=updatedevice";
  newDeviceUrl = baseurl + "?action=registerdevice";
  say("settings script OK");
</script>

<div class="hud">
<a href="<?= $index_link ?>?action=logout"><button class="big">Log out</button></a>
<br>
<BR><BR>
<a href="<?= $index_link . '?action=deleteme' ?>"
 onclick="if(!confirm('are you sure? All your devices will be deleted too'))
  {event.stopPropagation();event.preventDefault()}else{}">
 <button>Delete Account</button></a>
<br>
<div id="tableWrapper">
<?php
# If records found
if( $devicesExist ) {
?>
<h1>All Devices Registered By You</h1>
<div >Click on field to Edit, press OK after editing a field, then Save to fetchToUpdate the device in database. <br/>
      You can edit only one field at a time. If field is <span class="KBdisplay field-non-editable">greyed out</span> it is impossible to change the value. Delete the device and create new instead
  </div>




<table id="tableToEdit" class="table table-bordered">
	<thead>
		<tr class='info' ;>
      <?php foreach ($columns as $k => $column_name ) : 
        if(in_array($column_name,$nonVisibles)){
          continue;
        }
        ?>
      
      <th> <?php 
      $column_wout_prefix = str_replace($column_name_prefix,"",$column_name);
      echo $column_wout_prefix;
      
      ?> </th>
			<?php endforeach; ?>
			<th>Save Changes</th>
			
		</tr>
	</thead>
	<tbody>
		
		

		<?php

				// output data of each row
				foreach($resultset as $index => $row) {
        $column_counter =0;
        
			?>
		<tr id="r<?= $row['device_id'] ?>">
      <?php for ($i=0; $i < count($columns); $i++):
      $column_name = $columns[$column_counter];
      if(in_array($column_name,$nonVisibles)){
        $column_counter++;
        continue;
      }

      if (in_array($column_name,$nonEditables)){ 
        if ($column_name == "time_last_active"){
        
        date_default_timezone_set(DEFAULT_TIMEZONE_NAME_LONDON);
        $time_last_active = $row[$column_name];

     ?>
        <td class="field-non-editable time_last_active" data-column-name="time_last_active">	
      <span id="r<?= $row['device_id'] . $column_name ?>" class="my_date_format"><?=$time_last_active ?></span>
<br>
      <span class="ago"></span>
      <?php
      }elseif($column_name == "device_location"){ // any other noneditable
        ?>
    <td id="r<?= $row['device_id'] . $column_name ?>" class="field-non-editable squeezed" data-column-name="<?= $column_name ?>">
     <?=$row[$column_name];?>
     <?php 
        }else{ // any other noneditable
          ?>
			<td id="r<?= $row['device_id'] . $column_name ?>" class="field-non-editable" data-column-name="<?= $column_name ?>">
			<?php  echo $row[$column_name];
        }
      //now outer if's elseif: editables
    
      }elseif ($column_name == "time_set") {
        ?>
      <td class="uuu" >
      <span id="smallcounter" class="digits digits-small">

<span id="counter_hour">00</span>
<span id="counter_colon1">:</span>
<span id="counter_min">00</span>
<span id="counter_colon2" class="flash">:</span>
<span id="counter_sec">00</span>
</span>
      <label for="time_set">New:</label>
      <input 
      name="time_set" 
      class="field-editable time_set" 
      data-column-name="time_set"
      value="<?= $row["time_set"]; ?>">	
      Current:<br><span id="r<?= $row['device_id'] . $column_name ?>" ><?= $row[$column_name] ?><span>
      <?php
      }else{
        ?>
			<td id="r<?= $row['device_id'] . $column_name ?>" class="field-editable" data-column-name="<?= $column_name ?>">	
      <?php
      echo $row[$column_name]; 
    }; 
      //IF COLUMN IS TIME UPDATED DISPLAY ADDITIONALY TIME IN AGO FORMAT
      // echo $column_name;
      ?>
			</td>
      <?php 
      $column_counter++;
      endfor;?>
			<td class="field-non-editable">
      <?= $row["device_id"] . " - " . $row["device_name"]; ?>
      <div class="flex-row">
				<button onclick="sendUpdate(this.dataset.id)" 
				data-id="<?= $row["device_id"]; ?>">Save</button>
        <a href="<?= $index_link ."?action=delete&id=". $row["device_id"]; ?>"
        onclick="if(!confirm('are you sure? deleting cannot be undone'))
  {event.stopPropagation();event.preventDefault()}else{}"  
        ><button>Delete</button></a>
      </div>
      </td>
		</tr>
		<?php 
	
	} ?>

	</tbody>
</table>
</div>
<a href="<?= $index_link ?>"><button onclick="">Refresh</button></a>
<button onclick="watchmode.toggle(this)">Turn Watch Mode on</button>
<br><br><br>
<button id="mapCHGbtn" onclick="mapChanger.nextMap(this)">change Map server:cartodb-basemaps DARK a</button>

<div id="mapDIV" class="" >

</div>
<audio id="popsound" src="sounds/pop.mp3">
    Sorry, sounds are not supported
</audio>

<?php 



}else{ ?>
<h4> You didn't add any devices yet - add one below (by default it's ip is this device ip -
<?= $connection_ip;
} 
?>
</div>
 </h4>


<script src="js/v5-3-0_build_ol_formatted.js" type="text/javascript"></script>
<div class="centerpanel">
    <h2>New Device Registration</h2>
    <style>
        
    </style>
  <!--  method="post" action="<?= $index_link ?>?action=registerdevice" -->
    <form id="new_device_form" name="registerform" onsubmit="return false">
        <label for="device_name">
            device_name:<span 
            class="more-info-btn" 
            onclick="this.classList.toggle('more-info-show')">
              <span class="more-info-content">
              unique name that will allow you to identify device on map<br>
              choose something simple and short.            
              </span></span>
        </label>
        <input id="device_name" type="text" pattern="^.{2,64}$" name="device_name" required />
        <span class="validity">* required</span>
        <label for="device_description">
            device_description:
        </label>
        <textarea id="device_description" name="device_description" rows = "5" cols = "60" name = "description" placeholder="...put something more in here in case device has no location services or anything that helps you identify it in table "></textarea><br>
        
        <label for="is_sending_device_location">
            track device location:<span 
            class="more-info-btn" 
            onclick="this.classList.toggle('more-info-show')">
              <span class="more-info-content">
              if you turn it on device will be tracked and shown on map<br>
               if it will be a device that is different than this one 
               <br>- you will need to confirm locating again.              
              </span></span>
        </label>
        <input id="is_sending_device_location" 
        type="checkbox" name="is_sending_device_location" 
        onclick="checkLocation(event)" />
        <label for="device_password_new">
            Device Password <span 
            class="more-info-btn" 
            onclick="this.classList.toggle('more-info-show')">
              <span class="more-info-content">
              (3-24 characters, a-z 0-9)
              case insensitive, no special characters 
              <br>stops/unlocks the device                
              </span></span>
        </label>
        <input id="device_password_new" class="login_input" type="password" name="device_password_new" 
        pattern="[a-z0-9]{3,32}" required autocomplete="off" />
        <span class="validity">* required </span>
        <label for="device_password_repeat">
            Repeat password
        </label>
        <input id="device_password_repeat" class="login_input" type="password" name="device_password_repeat" 
        pattern="[a-z0-9]{3,32}" required autocomplete="off" />
        <span class="validity">* required</span>
        <label for="device_ip">
            Device IP(default this one):<br>
            or pseudo ip for quick setup
            <span 
            class="more-info-btn" 
            onclick="this.classList.toggle('more-info-show')">
            Quick setup
              <span class="more-info-content">
                you can enter any combination of digits or letters, and later in field
                without login just assign device to this setup by entering it on login screen.<br>
                 Remember: this value will change to the actual device's IP after assignment.                
              </span></span>
            
        </label>
        <input 
        id="device_ip"
         type="text" 
         name="device_ip" 
         title="IP address of device" 
         required autocomplete="off" <?= 'value=' . $connection_ip . '' ?> />
        <span class="validity">* required</span>
        <label for="time_set">
            time_set(this is the time device counts down to):
        </label>
        <input 
        id="time_set" 
        class="time_set" 
        type="datetime-local" 
        name="time_set" 
        pattern="[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}"
        min="<?= $minimum_time_set_date ?>"
        value="<?= $default_time_set_date ?>"
        required>
                <span class="validity"></span>

        <!-- <input type="submit" name="register" value="Register" onclick="sendNewDevice()"/> -->
        <button onclick="return sendNewDevice()">Register</button>

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
    </form >


</div>

</div>
Minimal Date ( NOW ):
<pre id="time_setMIN" style="display:block">
<?= $minimum_time_set_date ?></pre>

</div>

<!-- all these needed anyway for form addnewdevice -->
<link rel="stylesheet" href="flatpickr.css">
<script type="text/javascript" src="flatpickr.js"></script>
<script type="text/javascript" src="clockController.js"></script>

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


Test
jesli nie ma jeszcze tabeli po co watchmode, mapmodule,  ? senddatamodule?

watchmode script
<?php if ($devicesExist){ ?>
<script src="js/watchModeModule.js"></script>
<script src="js/olMapModule.js"></script>
<script src="js/tableEditModule.js"></script>
<?php } ?>

<script src="js/devLocateModule.js"></script>
<script src="js/sendNewDeviceModule.js"></script>

