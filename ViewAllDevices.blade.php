<?php
$user_id = $_SESSION['user_id'];
$allDevices = $this->getAllDevices($_SESSION['user_id']);
$columns = $allDevices['columnNames'];
$resultset = $allDevices['rows'];
$column_name_prefix = "device_";
$nonEditables = array("device_id","registered_by_user","time_last_active","device_location","device_session_id");
?>

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
    <td id=<?php echo 'r' . $row['device_id'] . $column_name ?> class="field-non-editable" data-column-name="<?= $column_name ?>">
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
<a href="<?= $_SERVER['SCRIPT_NAME'] ?>"><button onclick="">Refresh</button></a>
<button onclick="watchmode.toggle()">Watch Mode(DO NOT edit table!)</button>

<div id="mapDIV" class="centerpanel">

</div>

<?php 



}else{ ?>
<h4> You didn't add any devices yet - add one below (by default it's ip is this device ip -
<?php echo $this->getIP(DEBUG_MODE);
} 
?>
) </h4>

</div>
<script src="v4-6-5_build_ol.js" type="text/javascript"></script>
<script>
tableData = <?= json_encode($resultset,JSON_PRETTY_PRINT); ?>;

var map;
var mapDefaultZoom = 10;
var vectorLayer;
var atomIcons = {};

        function NuMap(mapLat,mapLng,mapDefaultZoom) {
            return new ol.Map({
                target: "map",
                layers: [
                    new ol.layer.Tile({
                        source: new ol.source.OSM({
                            url:"https://cartodb-basemaps-a.global.ssl.fastly.net/dark_all/{z}/{x}/{y}.png",
                            urluuu: "https://a.tile.openstreetmap.org/{z}/{x}/{y}.png"
                        })
                    })
                ],
                view: new ol.View({
                    center: ol.proj.fromLonLat([parseFloat(mapLng), parseFloat(mapLat)]),
                    zoom: mapDefaultZoom
                })
            });
        }


    

        function NuLayer(arrayOfFeatures,style=null) {
            return new ol.layer.Vector({
                source: new ol.source.Vector({
                    features: arrayOfFeatures
                }),
                style: null
            });
        }



        function NuFeature(id,status, lat, lng) {
            let f = new ol.Feature({
                geometry: new ol.geom.Point(ol.proj.transform([parseFloat(lng),
                    parseFloat(lat)
                ], 'EPSG:4326', 'EPSG:3857')),
                id: id,
            });
            
            f.setStyle(NuStyle(id,status));
            return f;
        }

        function NuStyle(text,imagename){
            return new ol.style.Style({
                    image: atomIcons[imagename],
                    text: new ol.style.Text({
                        text:text,
                        offsetY: 20,
                        font: "bold 20px sans-serif",
                        stroke: new ol.style.Stroke({
                            width: 3,
                            color: "#ffffff"
                        })
                    })
                })
        }

        function NuIcon(imagename){
            return new ol.style.Icon({
                        anchor: [0.5, 0.5],
                        anchorXUnits: "fraction",
                        anchorYUnits: "fraction",
                        src: "img/" + imagename + ".png"
                    })
        }


        function showDevices(devices) {
          if (devices.length<1){
            return;
          }
          isThereAnyDeviceWithLocation = false;
          for(let i = 0; i<devices.length; i++){
            let dv = devices[i];
            //alert(dv.device_location);
            doesDvHaveLocation = ![null,"no location",undefined].includes(dv.device_location);
           isThereAnyDeviceWithLocation = isThereAnyDeviceWithLocation || doesDvHaveLocation;
            
          }
          if(!isThereAnyDeviceWithLocation){
            return; //dont make map
          }

          devicesWithLocation = [];
          for(let i = 0; i<devices.length; i++){
            let dv = devices[i];
            doesDvHaveLocation = ![null,"no location",undefined].includes(dv.device_location);
            
            if(doesDvHaveLocation){
              locOb = {};
                locArr = dv.device_location.split("/");
              locOb.latitude = locArr[0];
              locOb.longitude = locArr[1];
              dv.location = locOb; 
            devicesWithLocation.push(dv);
            }
          }




            atomIcons = {
                disarmed: NuIcon("disarmed"),
                created: NuIcon("created"),
                active: NuIcon("active")
            }

            window.map = NuMap(devicesWithLocation[0].location.latitude, devicesWithLocation[0].location.longitude,mapDefaultZoom);
            arrayOfFeaturesAll = devicesWithLocation.map(dv=>{
              return NuFeature(dv.device_name,dv.device_status,dv.location.latitude,dv.location.longitude)
            })

            allLayer = NuLayer(arrayOfFeaturesAll);
            map.addLayer(allLayer);
        }


        if (tableData.length>0 ){
          isThereAnyDeviceWithLocation = false;
          for(let i = 0; i<tableData.length; i++){
            let dv = tableData[i];
            //alert(dv.device_location);
            doesDvHaveLocation = ![null,"no location",undefined].includes(dv.device_location);
           isThereAnyDeviceWithLocation = isThereAnyDeviceWithLocation || doesDvHaveLocation;
          }

          if(isThereAnyDeviceWithLocation){

            document.querySelector("#mapDIV").innerHTML = '<div id="map" style="width: 70vw; height: 70vh;"></div>';
  window.addEventListener("load",function(){showDevices(tableData)});
          }

}


baseurl = "<?=$_SERVER['SCRIPT_NAME'] ?>";
UpdateUrl = "<?=$_SERVER['SCRIPT_NAME'] ?>?action=updatedevice";
feedbackPRE = document.querySelector("#feedback");
function logfdb(msg){
  feedbackPRE.innerText += "\n" + msg
}
function DataField(name,value){
  this.name = name;
  this.value = value;
}

timeoutID = null;
isAutoReloadOn = false;

function ToggleAutoReloading(turniton = false){
  
if(isAutoReloadOn && turniton){
  return;
}
  setTimeout(x=>document.location.reload(true),10000);

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


   fetch(UpdateUrl, {
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
       // setTimeout(x=>open(baseurl),1000)
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
function Watchmode(){
  this.interval = 5000;
  this.urlgetalldevices  =  "<?=$this->scriptName ?>?action=js_getalldevices";
  this.isOn = false;
  this.IID  =  null;
  this.toggle = function(){
    this.isOn = !this.isOn;
    return this.isOn ? this.start():this.stop();
  };
  this.start = function(){
    this.IID = setInterval(x=>this.fetchToUpdate(),this.interval);
    this.fetchToUpdate();
  };
  this.stop = function(){
    clearInterval(this.IID);
  };
  this.fetchToUpdate = function(){
    fetch(this.urlgetalldevices,{credentials:"include"})
    .then(x=>x.json())
    .then(data=>this.update(data))
  };
  this.update= function(data){
    console.log(data);
    cols = data.columnNames;
    rows = data.rows;

    for(let i = 0; i<rows.length; i++){
      let row = rows[i];
      tr = tableToEdit.querySelector("#r"+row.device_id);
      if (!tr){ // new Device was added that is not yet in table
        open(baseurl);//just open new window - to much hassle ?
        return;
      }
      // later in api you can make it so cols are not all send
      // so only important/changeable stuff is updated
      for(let ci= 0; ci<cols.length; ci++){
      col = cols[ci]
        field = tr.querySelector("#r"+row.device_id+col);
        field.innerText = row[col];
      }
    }
  };
}

watchmode = new Watchmode();


</script>