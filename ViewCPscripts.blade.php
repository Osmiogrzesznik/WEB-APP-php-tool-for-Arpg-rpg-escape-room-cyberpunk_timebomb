<script>
  //SETTINGS SCRIPT 
  tableData = <?php echo json_encode($this->resultset, JSON_PRETTY_PRINT); ?>;
  baseurl = "<?= $_SERVER['SCRIPT_NAME'] ?>";
</script>
<script>
UpdateUrl = baseurl + "?action=updatedevice";
  newDeviceUrl = baseurl + "?action=registerdevice";

  function Watchmode() {
    this.interval = 5000;
    this.urlgetalldevices = baseurl + "?action=js_getalldevices";
    this.isOn = false;
    this.IID = null;

    this.btnOrigbg = "";
    this.firstTime = true;
    this.toggle = function(btn) {
      this.isOn = !this.isOn;
      btn.innerText = this.isOn ? "WATCHMODE ON - turn off to EDIT" : "EDIT MODE ON - CLICK TO SEE CHANGES LIVE";
      btn.style.border = this.isOn ? "4px solid yellow" : "";
      btn.style.backgroundColor = this.isOn ? "yellow" : "";
      btn.style.color = this.isOn ? "black" : "";
      popsound ? popsound.play() : 0; //play the sound if exists
      return this.isOn ? this.start() : this.stop();
    };
    this.start = function() {
      tableToEdit.classList.add("nonclickable");
      this.IID = setInterval(x => this.fetchToUpdate(), this.interval);
      this.fetchToUpdate();
    };
    this.stop = function() {
      tableToEdit.classList.remove("nonclickable");
      clearInterval(this.IID);
    };
    this.fetchToUpdate = function() {
      fetch(this.urlgetalldevices, {
          credentials: "include"
        })
        .then(x => x.json())
        .then(data => this.update(data))
        .catch(err=> say(err.stack));
    };
    this.update = function(data) {
      console.log(data);
      say("fetchOK", 1);
      cols = data.columnNames;
      rows = data.rows;

      for (let i = 0; i < rows.length; i++) {
        let row = rows[i];
        tr = tableToEdit.querySelector("#r" + row.device_id);
        if (!tr) { // new Device was added that is not yet in table
          // trTMPL = tableToEdit.querySelector("#r")*****
          this.stop();
          let msg = "TODO: cloneNode the row and fill it in with data "+
          "\nyou need to refresh, there is a new device registered on your account"

          say(msg);
          say(msg);
          open(baseurl); //just open new window - to much hassle ?
          return;
        }
        // later in api you can make it so cols are not all send
        // so only important/changeable stuff is updated
        for (let ci = 0; ci < cols.length; ci++) {
          col = cols[ci];
          field = tr.querySelector("#r" + row.device_id + col);
          if(!field){ continue;}
          let old = field.innerText
          let anew = row[col];
          if (old != anew) {
            field.innerText = anew;
            field.classList.add("updatedAnim");
            field.classList.add("changed");
            popsound ? popsound.play() : 0; //play the sound if exists
            window.fieldonlater = field;
            setTimeout(x => {
              //say(fieldonlater.classList)
              fieldonlater.classList.remove("updatedAnim")
            }, 1000);

            this.onUpdate(this,anew,row,col);

          } // end of if
        } //end of for

      } //end of for
      
      
    },
  

 //end of update
    this.onUpdate = function(watchmodeinstance,freshValue,device,columnName){};
  } //end of watchmode func
  watchmode = new Watchmode();

  say("watchmode module loaded ok");
</script>
<script>
 //----------------------------------------------------------------------------------------------------
  //----------------------------------------------------------------------------------------------------
  //----------------------------------------------------------------------------------------------------
  //----------------------------------------------------------------------------------------------------
  //--------------------------MAP MODULE--------------------------------------------------------------------------
  //----------------------------------------------------------------------------------------------------
  //----------------------------------------------------------------------------------------------------
 


  var map;
  var mapDefaultZoom = 10;
  var vectorLayer;
  var atomIcons = {};

  function NuMap(mapLat, mapLng, mapDefaultZoom) {
    return new ol.Map({
      target: "map",
      layers: [
        new ol.layer.Tile({
          source: new ol.source.OSM({
            url: "https://cartodb-basemaps-a.global.ssl.fastly.net/dark_all/{z}/{x}/{y}.png",
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




  function NuLayer(arrayOfFeatures, style = null) {
    return new ol.layer.Vector({
      source: new ol.source.Vector({
        features: arrayOfFeatures
      }),
      style: null
    });
  }



  function NuFeature(id, status, lat, lng) {
    let f = new ol.Feature({
      geometry: new ol.geom.Point(ol.proj.transform( [parseFloat(lng),parseFloat(lat)] , 'EPSG:4326', 'EPSG:3857')),
      id: id,
    });

    f.setStyle(NuStyle(id, status));
    return f;
  }

  function NuStyle(text, imagename) {
    return new ol.style.Style({
      image: atomIcons[imagename],
      text: new ol.style.Text({
        text: text,
        offsetY: 20,
        font: "bold 20px sans-serif",
        stroke: new ol.style.Stroke({
          width: 3,
          color: "#ffffff"
        })
      })
    })
  }

  function NuIcon(imagename) {
    return new ol.style.Icon({
      anchor: [0.5, 0.5],
      anchorXUnits: "fraction",
      anchorYUnits: "fraction",
      src: "img/" + imagename + ".png"
    })
  }


  function showDevices(devices) {
    if (devices.length < 1) {
      return;
    }

    isThereAnyDeviceWithLocation = false;
    for (let i = 0; i < devices.length; i++) {
      let dv = devices[i];
      //say(dv.device_location);
      doesDvHaveLocation = ![null, "no location", undefined].includes(dv.device_location);
      isThereAnyDeviceWithLocation = isThereAnyDeviceWithLocation || doesDvHaveLocation;

    }
    if (!isThereAnyDeviceWithLocation) {
      return; //dont make map
    }

    window.devicesWithLocation = [];
    for (let i = 0; i < devices.length; i++) {
      let dv = devices[i];
      doesDvHaveLocation = ![null, "no location", undefined].includes(dv.device_location);

      if (doesDvHaveLocation) {
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
    //musisz policzyc srednia albo znalesc na necie position map to see all markers
    window.map = NuMap(devicesWithLocation[0].location.latitude, devicesWithLocation[0].location.longitude, mapDefaultZoom);
    window.allFeaturesCollection = {};
    window.arrayOfFeaturesAll = devicesWithLocation.map(dv => {
      feature = NuFeature(dv.device_name, dv.device_status, dv.location.latitude, dv.location.longitude)
      allFeaturesCollection[dv.device_id] = feature;
      return feature;
    });
    allLayer = NuLayer(arrayOfFeaturesAll);
    map.addLayer(allLayer);
  }
iiiii = 0;
  function fakeUpdate(){
    iiiii++;
    if (iiiii>10){
      return;
    }
	window.devicesWithLocation.forEach(dv=>{
   
    f = allFeaturesCollection[dv.device_id];
		//.setGeometry(new ol.geom.Point(pos));
		coords = f.getGeometry().getCoordinates();
    coords = ol.proj.transform(coords, 'EPSG:3857', 'EPSG:4326');
    //say(JSON.stringify(coord))
   // console.log(coords);
   console.log(coords);
    coords[0] +=  (Math.random() > 0.5 ? 1:-1)* 0.001;
    coords[1] +=  (Math.random() > 0.5 ? 1:-1)* 0.001;
    console.log(coords);
    freshValue = coords.reverse().join("/");
    console.log(freshValue);
    updateMarkerLocation({},freshValue,dv,"device_location");
//works weirdly slowly
		say("moved geometry")
		})
		return;
	//stage1 just features
	
	//updateMarkerLocation(notthis,fv,dv,col);
}
  watchmode.onUpdate=updateMarkerLocation;
  
//  window.setInterval(x=>{
// fakeUpdate();

// say("updated");
// },1000);



	
 function updateMarkerLocation(watchmodeinstance,freshValue,device,columnName){
    if (columnName != "device_location"){
      return;
    }

    locArr = freshValue.split("/");
        locOb.latitude = locArr[0];
        locOb.longitude = locArr[1];
        locArr[0] = parseFloat(locArr[0]);
        locArr[1] = parseFloat(locArr[1]);
    updatedFeature = allFeaturesCollection[device.device_id];
  //  updatedFeature.setGeometry(new ol.geom.Point(locArr));
    updatedFeature.set('geometry', new ol.geom.Point(ol.proj.fromLonLat([locArr[1],locArr[0]])));
        //bookmark *** SEE if it works
}

  function startMap() {
    // this happens at the start
    // simply quickly copy pasted just for checking isThere any device with location 
    // to avoid showing map if no such devices possible
    if (tableData.length > 0) {
      isThereAnyDeviceWithLocation = false;
      for (let i = 0; i < tableData.length; i++) {
        let dv = tableData[i];
        //say(dv.device_location);

        doesDvHaveLocation = ![null, "no location", undefined].includes(dv.device_location);
        isThereAnyDeviceWithLocation = isThereAnyDeviceWithLocation || doesDvHaveLocation;
      }


      if (isThereAnyDeviceWithLocation) {
        // show map only if there are any located devices
        document.querySelector("#mapDIV").innerHTML =
         '<div id="map"></div>';
        window.addEventListener("load", function() {
          showDevices(tableData);

        });
        
      }else{
        say("no devices that provide location coords yet"+
       +" \n not showing the map");
      }


    }
  }


  
startMap();
say("map module ok");
</script>
<script>

  //----------------------------------------------------------------------------------------------------
  //----------------------------------------------------------------------------------------------------
  //----------------------------------------------------------------------------------------------------
  //----------------------------------------------------------------------------------------------------
  //--------------------------SEND DATA MODULE--------------------------------------------------------------------------
  //----------------------------------------------------------------------------------------------------
  //----------------------------------------------------------------------------------------------------
  function DataField(name, value) {
    this.name = name;
    this.value = value;
  }


  function sendUpdate(id, tr_row) {
    var FD = new FormData();
    let deviceRow = tableToEdit.querySelector("#r" + id);
    let editables = deviceRow.querySelectorAll(".field-editable");
    let fields = [];
    fields.push(new DataField("updatedevice", true));
    fields.push(new DataField("device_id", id));
    FD.append("updatedevice", true);
    FD.append("device_id", id);
    for (let idx = 0; idx < editables.length; idx++) {
      val = editables[idx].innerText;
      if (!val) {
        val = editables[idx].value;
        val = !val ? "" : val; //replace nullish values with empty string
      }
      name = editables[idx].dataset.columnName;
      datafield = new DataField(name, val);
      fields.push(datafield)
      FD.append(name, val);
    }


    //  say(JSON.stringify(fields));
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
        say(t);
      });; // parses JSON response into native JavaScript objects 

    console.log(fields)


    say(id);
  }


  let table = document.getElementById('tableToEdit');

  let editingTd;


  if (table) {
    table.onclick = function(event) {

      // 3 possible targets
      let target = event.target.closest('.edit-cancel,.edit-ok,td');

      if (!table.contains(target)) return;

      if (target.className == 'edit-cancel') {
        finishTdEdit(editingTd.elem, false);
      } else if (target.className == 'edit-ok') {
        finishTdEdit(editingTd.elem, true);
      } else if (
        target.nodeName === 'TD' && target.className === "field-editable" && !editingTd) { //  not already editing
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





  //----------------------------------------------------------------------------------------------------
  //----------------------------------------------------------------------------------------------------
  //----------------------------------------------------------------------------------------------------
  //----------------------------------------------------------------------------------------------------
  //----------------------------------------------------------------------------------------------------
  //----------------------------------------------------------------------------------------------------
  //----------------------------------------------------------------------------------------------------
  //----------------------------------------------------------------------------------------------------
</script>



<script>
  //----------------------------------------------------------------------------------------------------
  //----------------------------------------------------------------------------------------------------
  //----------------------------------------------------------------------------------------------------
  //----------------------------------------------------------------------------------------------------
  //----------------------------------------------------------------------------------------------------
  //----------------------------------------------------------------------------------------------------
  //----------------------------------------------------------------------------------------------------
  //----------------------------------------------------------------------------------------------------
  //----------------------------------------------------------------------------------------------------
  //----------------------------------------------------------------------------------------------------
  //----------------------------------------------------------------------------------------------------
  //----------------------------------------------------------------------------------------------------
  //----------------------------------------------------------------------------------------------------
  //----------------------------------------------------------------------------------------------------
  //----------------------------------------------------------------------------------------------------
  //----------------------------------------------------------------------------------------------------
  //----------------------------------------------------------------------------------------------------

  // function checkEnter(e){
  //  e = e || event;
  //  var CHK = ["checkbox"].includes((e.target || e.srcElement).type);
  //  return CHK || (e.keyCode || e.which || e.charCode || 0) !== 13;
  // }
  // document.querySelector('form').onkeypress = checkEnter;


  devLocate = {
    lastError: "",
    wasError: false,
    wasUpdated: false,
    approved: false,
    pos_status: "",
    position: {
      latitude: "",
      longitude: ""
    },
    setApproved(appr) {
      this.approved = appr;
    },
    isOKtoSend() {
      return !this.wasError && this.wasUpdated && this.approved;
    },
    updatePosition(latitude, longitude) {
      this.wasUpdated = true;
      this.position.latitude = latitude;
      this.position.longitude = longitude;

    },
    getLocationSuffix() {
      suffix = this.isOKtoSend() ?
        ("&latitude=" + this.position.latitude +
          "&longitude=" + this.position.longitude) :
        "";
      return suffix;
    },
    getLocationObject() {
      if (!this.isOKtoSend()) {
        return {
          latitude: "",
          longitude: ""
        };
      }
      return {
        latitude: this.position.latitude,
        longitude: this.position.longitude
      };
    },
    onUpdate() {
      say("latitude=" + this.position.latitude +
        " longitude=" + this.position.longitude)
    }
  }

  function locationSuccess(position) {
    devLocate.updatePosition(position.coords.latitude, position.coords.longitude)
    devLocate.onUpdate();
  }

  function locationError(error) {
    devLocate.wasError = true;
    devLocate.lastError = error.code + ' : ' + error.message;
    devLocate.pos_status.textContent = 'Unable to retrieve your location';
    devLocate.onUpdate();
  }

  function locate() {
    if (devLocate.wasError) {
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


  function checkLocation(ev) {

    if (ev.target.checked) {
      // Checkbox is checked..
      locate();
      devLocate.setApproved(true)
    } else {
      // Checkbox is not checked..
      devLocate.setApproved(false)
    }


  }

  function sendNewDevice() {
    var FD = new FormData(document.querySelector("#new_device_form"));
    let fields = [];
    DEV_LOCATION = devLocate.getLocationObject();
    //say(JSON.stringify(DEV_LOCATION));
    FD.append("latitude", DEV_LOCATION.latitude + "");
    FD.append("longitude", DEV_LOCATION.longitude + "");
    FD.append("registerdevice", "true");
say(DEV_LOCATION);
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
	feedback.innerText = "New device feedback:";
	say(t);
        confirm("if device added succesfully , click ok to refresh window"+t)?
        window.open(baseurl,"_self"):0; //try to display modal else say
        
      });; // parses JSON response into native JavaScript objects 
    return true; //false;//return false to prevent form from reloading the page   
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
      //say(event.target.value);
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



  stngs = {
    enableTime: true,
    enableSeconds: true,
    dateFormat: "Y-m-d\TH:i:S",
    allowInput: true,
    appendTo: tableWrapper
    //defaultDate: new Date()
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
    'second'
  ];

  //prepare for refreshing here to not burden cpu
  time_last_active_tds = document.querySelectorAll(".time_last_active");
  tlaObjs = [];
  for (let i = 0; i < time_last_active_tds.length; i++) {
    let ob = {};
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
    tlaObjs.forEach(ob => {
      ob.agospan.innerText = time_ago(ob.datesrv);
    })
    //if there is many counters fps would be an array 
    //otherwise (when user didn't add any devices yet) its one flatpicker
    if (is_many_flatpickers) {
      fps.forEach(fp => {
        fp.config.minDate = dstr;
      })
    } else {
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