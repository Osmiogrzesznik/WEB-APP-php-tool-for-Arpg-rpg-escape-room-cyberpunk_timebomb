<?php
$user_id = $this->user_id;
$resultset = $this->resultset;
$columns = $this->columns;
$column_name_prefix = "device_";
$nonVisibles = array("device_id","registered_by_user","device_session_id","device_http_user_agent");
$nonEditables = array("device_id","registered_by_user","time_last_active","device_location","device_session_id");

if (isset($_GET['all'])){
  $nonVisibles=array();
}


?>
	ver3
	alert on start watchmode

<div class="hud">
<a href="<?php echo $_SERVER['SCRIPT_NAME'] ?>?action=logout"><button class="big">Log out</button></a>
<br>
<BR><BR>
<a href="<?php echo $_SERVER['SCRIPT_NAME'] . '?action=deleteme' ?>"
 onclick="if(!confirm('are you sure? All your devices will be deleted too'))
  {event.stopPropagation();event.preventDefault()}else{}">
 <button>Delete Account</button></a>
<br>

<?php
# If records found
if( count($resultset) > 0 ) {
?>



<h1>All Devices Registered By You</h1>
<h3 >Click on field to Edit, press OK after editing a field, then Save to fetchToUpdate the device in database. <br/>
      You can edit only one field at a time. If field is <span class="KBdisplay field-non-editable">greyed out</span> it is impossible to change the value. Delete the device and create new instead
</h3>
<div id="tableWrapper">
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
		<tr id="<?php echo 'r' . $row['device_id'] ?>">
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

        // $time_elapsed_span = time_elapsed_HTMLelement("Y-m-d\TH:i:s",$time_last_active,$this->timezone,true);
        // echo "<br>(" . $time_elapsed_span . ")";
     ?>
        <td class="field-non-editable time_last_active" data-column-name="time_last_active">	
      <span id=<?php echo 'r' . $row['device_id'] . $column_name ?> class="my_date_format"><?=$time_last_active ?></span>
<br>
      <span class="ago"></span>
      <?php
      }elseif($column_name == "device_location"){ // any other noneditable
        ?>
    <td id=<?php echo 'r' . $row['device_id'] . $column_name ?> class="field-non-editable squeezed" data-column-name="<?= $column_name ?>">
     <?=$row[$column_name];?>
     <?php 
        }else{ // any other noneditable
          ?>
			<td id=<?php echo 'r' . $row['device_id'] . $column_name ?> class="field-non-editable" data-column-name="<?= $column_name ?>">
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
      Current:<br><span id=<?php echo 'r' . $row['device_id'] . $column_name ?> ><?= $row[$column_name] ?><span>
      <?php
      }else{
        ?>
			<td id=<?php echo 'r' . $row['device_id'] . $column_name ?> class="field-editable" data-column-name="<?php echo $column_name ?>">	
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
      <?php echo $row["device_id"] . " - " . $row["device_name"]; ?>
      <div class="flex-row">
				<button onclick="sendUpdate(this.dataset.id)" 
				data-id="<?php echo $row["device_id"]; ?>">Save</button>
        <a href="<?php echo $_SERVER['SCRIPT_NAME'] ."?action=delete&id=". $row["device_id"]; ?>"
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
<a href="<?= $_SERVER['SCRIPT_NAME'] ?>"><button onclick="">Refresh</button></a>
<button onclick="watchmode.toggle(this)">Watch Mode(DO NOT edit table!)</button>

<div id="mapDIV" class="centerpanel" >

</div>
<audio id="popsound" src="sounds/pop.mp3">
    Sorry, sounds are not supported
</audio>
<?php 



}else{ ?>
<h4> You didn't add any devices yet - add one below (by default it's ip is this device ip -
<?php echo $this->getIP(DEBUG_MODE);
} 
?>
 </h4>

</div>
<script src="v4-6-5_build_ol.js" type="text/javascript"></script>
<div class="centerpanel">
    <h2>New Device Registration</h2>
    <style>
        
    </style>
  <!--  method="post" action="<?php echo $_SERVER['SCRIPT_NAME'] ?>?action=registerDevice" -->
    <form method="post" action="<?php echo $_SERVER['SCRIPT_NAME'] ?>" id="new_device_form" name="registerform">
        <label for="device_name">
            device_name:
        </label>
        <input id="device_name" type="text" pattern="^.{2,64}$" name="device_name" required />
        <span class="validity">* required</span>
        <label for="device_description">
            device_description:
        </label>
        <input id="device_description" type="text" name="device_description" />
        <label for="is_sending_device_location">
            track this device location:
        </label>
        <input id="is_sending_device_location" 
        type="checkbox" name="is_sending_device_location" 
        onclick="checkLocation(event)" />
        <label for="device_password_new">
            Device Password (3-24 characters, a-z 0-9) stops/unlocks the device
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
            Device IP(default this one):
        </label>
        <input id="device_ip" type="text" name="device_ip" title="IP address of device" required autocomplete="off" <?php echo 'value=' . $this->getIP(DEBUG_MODE) . '' ?> />
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
        <?php
        date_default_timezone_set($this->timezoneName);
$datenow = date('Y-m-d\TH:i:s', time()+60*30);//in 30 minutes
echo 'min="' . $datenow . '" value="' . $datenow . '"' ?> required>
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
<?= date(MY_DATE_FORMAT, time());?></pre>

</div>

<link rel="stylesheet" href="flatpickr.css">
<script type="text/javascript" src="flatpickr.js"></script>
<script type="text/javascript" src="clockController.js"></script>
<script type="text/javascript" src="touchKeyboard.js"></script>
<!-- <script type="text/javascript" src="timebomb.js"></script> -->