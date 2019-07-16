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
<script>
// function checkEnter(e){
//  e = e || event;
//  var CHK = ["checkbox"].includes((e.target || e.srcElement).type);
//  return CHK || (e.keyCode || e.which || e.charCode || 0) !== 13;
// }
// document.querySelector('form').onkeypress = checkEnter;

newDeviceUrl = "<?php echo $_SERVER['SCRIPT_NAME'] ?>?action=registerdevice";

devLocate={
        lastError:"",
        wasError: false,
        wasUpdated:false,
        approved:false,
        pos_status: "",
        position:{
            latitude:"",
            longitude:""
        },
        setApproved(appr){
          this.approved = appr;
        },
        isOKtoSend(){
            return !this.wasError && this.wasUpdated && this.approved;
        },
        updatePosition(latitude,longitude){
            this.wasUpdated = true;
            this.position.latitude = latitude;
            this.position.longitude = longitude;
            
        },
        getLocationSuffix(){
            suffix = this.isOKtoSend() ? 
            ("&latitude="+this.position.latitude+
            "&longitude="+this.position.longitude) 
            : "";
            return suffix;
        },
        getLocationObject(){
            if(!this.isOKtoSend()){
              return {latitude:"",longitude:""};
            } 
            return {latitude:this.position.latitude,longitude:this.position.longitude};
        },
        onUpdate(){
          alert("latitude="+this.position.latitude+
            " longitude="+this.position.longitude)
        }
    }

    function locationSuccess(position) {
        devLocate.updatePosition(position.coords.latitude,position.coords.longitude)
        devLocate.onUpdate();
      }
    
      function locationError(error) {
          devLocate.wasError = true;
        devLocate.lastError = error.code + ' : ' + error.message;
        devLocate.pos_status.textContent = 'Unable to retrieve your location';
        devLocate.onUpdate();
      }
    
    function locate(){
        if(devLocate.wasError){
            return;
        }
      if (!navigator.geolocation) {
          devLocate.wasError = true;
        devLocate.pos_status.textContent = 'Geolocation is not supported by your browser';
      } else {
        devLocate.pos_status.textContent = 'Locating…';
        navigator.geolocation.getCurrentPosition(locationSuccess, locationError);
      }
    }


function checkLocation(ev){
  
    if(ev.target.checked) {
        // Checkbox is checked..
        locate();
        devLocate.setApproved(true)
    } else {
        // Checkbox is not checked..
        devLocate.setApproved(false)
    }

  
}

function sendNewDevice(id, tr_row){
  var FD  = new FormData(document.querySelector("#new_device_form"));
  let fields = [];
DEV_LOCATION = devLocate.getLocationObject();
alert(JSON.stringify(DEV_LOCATION));
FD.append("latitude",DEV_LOCATION.latitude+"");
FD.append("longitude",DEV_LOCATION.longitude+"");
FD.append("registerdevice","true");

   fetch(newDeviceUrl, {
        method: 'POST', // *GET, POST, PUT, DELETE, etc.
        // mode: 'cors', // no-cors, cors, *same-origin
        // cache: 'no-cache', // *default, no-cache, reload, force-cache, only-if-cached
        credentials: 'include', // include, *same-origin, omit
        // headers: {
        // //    // 'Content-Type': 'application/json',
        //       'Content-Type': 'application/x-www-form-urlencoded'
        // },
         //redirect: 'manual', // manual, *follow, error
        // referrer: 'no-referrer', // no-referrer, *client
        body: FD // body data type must match "Content-Type" header
    })
    .then(response => response.text())
    .then(t => {
      feedbackPRE = document.querySelector("#feedback");
      if (feedbackPRE){
        alert(t)
        feedbackPRE.innerText += t;
       // setTimeout(x=>document.location.reload(true),1000);
      }
      else{
        alert(t)
      }
    });
    ; // parses JSON response into native JavaScript objects 
 return true;//false;//return false to prevent form from reloading the page   
}












    stngs = {
        enableTime: true,
        enableSeconds: true,
        dateFormat: "Y-m-d\TH:i:S",
        allowInput: true,
        //defaultDate: new Date()
    }



    time_setsNL = document.querySelectorAll(".time_set");
    cxs = [];
    counters = [];
    for (let idx = 0; idx < time_setsNL.length; idx++) {
        let time_set = time_setsNL[idx];
        let counter = time_set.parentElement.querySelector(".digits");
        counters.push(counter);
        let cx = new ClockController(counter);
        cxs.push(cx);
        cx.setTimestampEndfromString(time_set.value);
        cx.start();
        time_set.addEventListener('change', (ev) => {
            cx.reset();
            //alert(event.target.value);
            cx.setTimestampEndfromString(ev.target.value);
            cx.start();
        }, false)
    }

    
    


    function pad(number) {
        if (number < 10) {
            return '0' + number;
        }
        return number;
    }

    function toISOStrCut(date) {
        return date.getUTCFullYear() +
            '-' + pad(date.getUTCMonth() + 1) +
            '-' + pad(date.getUTCDate()) +
            'T' + pad(date.getUTCHours()) +
            ':' + pad(date.getUTCMinutes()) +
            ':' + pad(date.getUTCSeconds())
    };


    options = {

    }

    //if there is many counters fps would be an array 
    //otherwise (when user didn't add any devices yet) its one flatpicker
    fps = flatpickr(time_setsNL, stngs);
    is_many_flatpickers = fps.hasOwnProperty("length");
    units = [
     'year',
     'month',
     'week',
     'day',
     'hour',
     'minute',
     'second'];
    
    //prepare for refreshing here to not burden cpu
    time_last_active_tds = document.querySelectorAll(".time_last_active");
    tlaObjs = [];
    for (let i= 0;i<time_last_active_tds.length;i++){
          let ob={};
          ob.td = time_last_active_tds[i];
          ob.datesrv = ob.td.querySelector(".my_date_format").innerText;
          ob.agospan = ob.td.querySelector(".ago");
          tlaObjs.push(ob);
    }
//     time_last_active.forEach(te=>{
//         datasetvalues = units.map(unitname=>{
//             ob={};
//             ob[unitname]=te.dataset[unitname];
//             return ob;
//             });//object with propertiies names and values
//         console.log(datasetvalues);

// });
    //this interval is only for minimum and displaying ago's so can be refreshed each 5s
    setInterval(x => {
        timeZoneOffset = new Date().getTimezoneOffset() * 60000;
        timestampNOWwOffs = Date.now() - timeZoneOffset;
        dateNOWwOffs = new Date(timestampNOWwOffs);
        let dstr = toISOStrCut(dateNOWwOffs);
        //refresh time ago's
        tlaObjs.forEach(ob=>{
          ob.agospan.innerText = time_ago(ob.datesrv);
        })
        //if there is many counters fps would be an array 
        //otherwise (when user didn't add any devices yet) its one flatpicker
        if(is_many_flatpickers){
            fps.forEach(fp => {
            fp.config.minDate = dstr;
        })
        }else{
            fps.config.minDate = dstr;
        }
        // time_setMIN.valueAsNumber = ~~(timestampNOWwOffs / 60000) * 60000;
        // time_setMINtext.value = dstr;
        // time_set.min = dstr;
    }, 5000);






function time_ago(time) {

switch (typeof time) {
  case 'number':
    break;
  case 'string':
    time = +new Date(time);
    break;
  case 'object':
    if (time.constructor === Date) time = time.getTime();
    break;
  default:
    time = +new Date();
}
var time_formats = [
  [60, 'seconds', 1], // 60
  [120, '1 minute ago', '1 minute from now'], // 60*2
  [3600, 'minutes', 60], // 60*60, 60
  [7200, '1 hour ago', '1 hour from now'], // 60*60*2
  [86400, 'hours', 3600], // 60*60*24, 60*60
  [172800, 'Yesterday', 'Tomorrow'], // 60*60*24*2
  [604800, 'days', 86400], // 60*60*24*7, 60*60*24
  [1209600, 'Last week', 'Next week'], // 60*60*24*7*4*2
  [2419200, 'weeks', 604800], // 60*60*24*7*4, 60*60*24*7
  [4838400, 'Last month', 'Next month'], // 60*60*24*7*4*2
  [29030400, 'months', 2419200], // 60*60*24*7*4*12, 60*60*24*7*4
  [58060800, 'Last year', 'Next year'], // 60*60*24*7*4*12*2
  [2903040000, 'years', 29030400], // 60*60*24*7*4*12*100, 60*60*24*7*4*12
  [5806080000, 'Last century', 'Next century'], // 60*60*24*7*4*12*100*2
  [58060800000, 'centuries', 2903040000] // 60*60*24*7*4*12*100*20, 60*60*24*7*4*12*100
];

var seconds = ~~(+new Date() - time) / 1000,
  token = 'ago',
  list_choice = 1;

if (seconds == 0) {
  return 'Just now'
}
if (seconds < 0) {
  seconds = Math.abs(seconds);
  token = 'from now';
  list_choice = 2;
}
var i = 0,
  format;
while (format = time_formats[i++])
  if (seconds < format[0]) {
    if (typeof format[2] == 'string')
      return format[list_choice];
    else
      return ~~(seconds / format[2]) + ' ' + format[1] + ' ' + token;
  }
return time;
}



    
</script>
</body>

</html>