<?php

function time_elapsed_string($format, $datetime, $timezone, $full = false)
{
    $now = new DateTime('now', $timezone);
    //$ago = new DateTime($datetime,$timezone);
    $ago = DateTime::createFromFormat($format, $datetime, $timezone);
    $diff = $now->diff($ago);
    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = array(
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    );
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}





$user_id = $_SESSION['user_id'];
$conn = $this->db_connection;
$sql = "SELECT * FROM device WHERE registered_by_user = :user_id";// WHERE class = '$class'"; later  -> WHERE user_creator_id = :logged_user_id

$query = $conn->prepare($sql);
$query->bindValue(':user_id',$user_id);
            

$query->setFetchMode(PDO::FETCH_ASSOC);
$query->execute();


$columns = array();
$resultset = array();
$column_name_prefix = "device_";
$nonEditables = array("device_id","registered_by_user","time_last_active");

# Set columns and results array
while($row = $query->fetch()) {
	if (empty($columns)) {
		$columns = array_keys($row);
	}
	$resultset[] = $row;
}


?>
   

<body>
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
<style>



.edit-td .edit-area {
  border: none;
  margin: 0;
  padding: 0;
  display: block;

  /* remove resizing handle in Firefox */
  resize: none;

  /* remove outline on focus in Chrome */
  outline: none;

  /* remove scrollbar in IE */
  overflow: auto;
}

.edit-controls {
  position: absolute;
}

.edit-td {
  position: relative;
  padding: 0;
}
</style>


<h1>All Devices Registered By You</h1>
<h3 >Click on field to Edit, press OK after editing a field, then Save to update the device in database. <br/>
      You can edit only one field at a time. If field is <span class="KBdisplay field-non-editable">greyed out</span> it is impossible to change the value. Delete the device and create new instead
</h3>
<table id="tableToEdit" class="table table-bordered">
	<thead>
		<tr class='info' ;>
			<?php foreach ($columns as $k => $column_name ) : ?>
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
			if (in_array($column_name,$nonEditables)){ ?>
			<td class="field-non-editable" data-column-name="<?php echo $column_name ?>">
			<?php 
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
      value="<?php echo $row["time_set"]; ?>">	
      Current:<br>
      <?php 
      }else{
        ?>
			<td class="field-editable" data-column-name="<?php echo $column_name ?>">	
      <?php }; echo $row[$column_name];
      //IF COLUMN IS TIME UPDATED DISPLAY ADDITIONALY TIME IN AGO FORMAT
      // echo $column_name;
      if($column_name === "time_last_active"){
        date_default_timezone_set(DEFAULT_TIMEZONE_NAME_LONDON);
        $time_last_active = $row[$column_name];
        $display_time_since = time_elapsed_string("Y-m-d\TH:i:s",$time_last_active,$this->timezone,true);
        echo "<br>(" . $display_time_since . ")";
     }
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

<button onclick="document.location.reload(true)">Refresh</button>

<?php 



}else{ ?>
<h4> You didn't add any devices yet - add one below (by default it's ip is this device ip -
<?php echo $this->getIP(DEBUG_MODE);
} 
?>
) </h4>

</div>
<script>
url = "<?php echo $_SERVER['SCRIPT_NAME'] ?>";
feedbackPRE = document.querySelector("#feedback");
function logfdb(msg){
  feedbackPRE.innerText += "\n" + msg
}
function DataField(name,value){
  this.name = name;
  this.value = value;
}


function sendUpdate(id, tr_row){
  var FD  = new FormData();
  let deviceRow = tableToEdit.querySelector("#r" + id);
  let editables = deviceRow.querySelectorAll(".field-editable");
  let fields = [];
  fields.push(new DataField("updatedevice",true));
  fields.push(new DataField("device_id",id));
  FD.append("updatedevice",true);
  FD.append("device_id",id);
   for (let idx = 0; idx < editables.length; idx++){
      val = editables[idx].innerText;
      if (!val) {
        val = editables[idx].value;
      }
      name = editables[idx].dataset.columnName;
      datafield = new DataField(name,val);
      fields.push(datafield)
      FD.append(name,val);
   }
  //  alert(JSON.stringify(fields));
  //  return;


   fetch(url, {
        method: 'POST', // *GET, POST, PUT, DELETE, etc.
        // mode: 'cors', // no-cors, cors, *same-origin
        // cache: 'no-cache', // *default, no-cache, reload, force-cache, only-if-cached
        credentials: 'include', // include, *same-origin, omit
        // headers: {
        //    // 'Content-Type': 'application/json',
        //      'Content-Type': 'application/x-www-form-urlencoded',
        // },
        // redirect: 'follow', // manual, *follow, error
        // referrer: 'no-referrer', // no-referrer, *client
        body: FD // body data type must match "Content-Type" header
    })
    .then(response => response.text())
    .then(t => {
      feedbackPRE = document.querySelector("#feedback");
      if (feedbackPRE){
        feedbackPRE.innerText = t;
      }
      else{
        alert(t)
      }
    });
    ; // parses JSON response into native JavaScript objects 

  console.log(fields)


	alert(id);
}


let table = document.getElementById('tableToEdit');

let editingTd;


if (table){
table.onclick = function(event) {

  // 3 possible targets
  let target = event.target.closest('.edit-cancel,.edit-ok,td');

  if (!table.contains(target)) return;

  if (target.className == 'edit-cancel') {
    finishTdEdit(editingTd.elem, false);
  } else if (target.className == 'edit-ok') {
    finishTdEdit(editingTd.elem, true);
  } else if (
	  target.nodeName === 'TD' && target.className==="field-editable" && !editingTd ) { //  not already editing
      makeTdEditable(target);
  }

};
}

function makeTdEditable(td) {
  editingTd = {
    elem: td,
    data: td.innerHTML
  };

  td.classList.add('edit-td'); // td is in edit state, CSS also styles the area inside

  let textArea = document.createElement('textarea');
  textArea.style.width = td.clientWidth + 'px';
  textArea.style.height = td.clientHeight + 'px';
  textArea.className = 'edit-area';

  textArea.value = td.innerText;
  td.innerHTML = '';
  td.appendChild(textArea);
  textArea.focus();

  td.insertAdjacentHTML("beforeEnd",
    '<div class="edit-controls"><button class="edit-ok">OK</button><button class="edit-cancel">CANCEL</button></div>'
  );
}

function finishTdEdit(td, isOk) {
  if (isOk) {
    td.innerHTML = td.firstChild.value;
  } else {
    td.innerHTML = editingTd.data;
  }
  td.classList.remove('edit-td');
  editingTd = null;
}
</script>