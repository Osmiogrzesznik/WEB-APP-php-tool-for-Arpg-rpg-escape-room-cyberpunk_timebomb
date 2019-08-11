//----------------------------------------------------------------------------------------------------
//----------------------------------------------------------------------------------------------------
//----------------------------------------------------------------------------------------------------
//----------------------------------------------------------------------------------------------------
//--------------------------MAP MODULE--------------------------------------------------------------------------
//----------------------------------------------------------------------------------------------------
//----------------------------------------------------------------------------------------------------



var map;
var mapDefaultZoom = user.prefs.user_map_default_zoom;
var atomIcons = {};

TILE_SERVERS = [{
    name: "cartodb-basemaps DARK a",
    url: "https://cartodb-basemaps-a.global.ssl.fastly.net/dark_all/{z}/{x}/{y}.png"
  },
  {
    name: "cartodb-basemaps DARK b",
    url: "https://cartodb-basemaps-b.global.ssl.fastly.net/dark_all/{z}/{x}/{y}.png"
  },
  {
    name: "cartodb-basemaps DARK c",
    url: "https://cartodb-basemaps-c.global.ssl.fastly.net/dark_all/{z}/{x}/{y}.png"
  },
  {
    name: "cartodb-basemaps light a",
    url: "https://cartodb-basemaps-a.global.ssl.fastly.net/light_all/{z}/{x}/{y}.png"
  },
  {
    name: "cartodb-basemaps light b",
    url: "https://cartodb-basemaps-b.global.ssl.fastly.net/light_all/{z}/{x}/{y}.png"
  },
  {
    name: "cartodb-basemaps light c",
    url: "https://cartodb-basemaps-c.global.ssl.fastly.net/light_all/{z}/{x}/{y}.png"
  },
  {
    name: "stamen watercolor",
    url: "http://c.tile.stamen.com/watercolor/{z}/{x}/{y}.jpg"
  },
  {
    name: "stamen toner",
    url: "http://a.tile.stamen.com/toner/{z}/{x}/{y}.png"
  },
  {
    name: "OSM a",
    url: "https://a.tile.openstreetmap.org/{z}/{x}/{y}.png"
  },
  {
    name: "OSM b",
    url: "https://b.tile.openstreetmap.org/{z}/{x}/{y}.png"
  },
  {
    name: "OSM c",
    url: "https://c.tile.openstreetmap.org/{z}/{x}/{y}.png"
  },
  {
    name: "OSM.de a",
    url: "https://a.tile.openstreetmap.de/{z}/{x}/{y}.png"
  },
  {
    name: "OSM.de b",
    url: "https://b.tile.openstreetmap.de/{z}/{x}/{y}.png"
  },
  {
    name: "OSM.de c",
    url: "https://c.tile.openstreetmap.de/{z}/{x}/{y}.png"
  },
  {
    name: "wikimedia",
    url: "https://maps.wikimedia.org/osm-intl/{z}/{x}/{y}.png"
  },
  {
    name: "OSM.fr hot a",
    url: "http://a.tile.openstreetmap.fr/hot/{z}/{x}/{y}.png "
  },
  {
    name: "OSM.fr hot b",
    url: "http://b.tile.openstreetmap.fr/hot/{z}/{x}/{y}.png"
  },
  {
    name: "OSM.fr osmfr a",
    url: "http://a.tile.openstreetmap.fr/osmfr/{z}/{x}/{y}.png "
  },
  {
    name: "OSM.fr osmfr b",
    url: "http://b.tile.openstreetmap.fr/osmfr/{z}/{x}/{y}.png "
  },
  {
    name: "OSM.fr osmfr c",
    url: "http://c.tile.openstreetmap.fr/osmfr/{z}/{x}/{y}.png"
  },
  {
    name: "opentopomap a",
    url: "https://a.tile.opentopomap.org/{z}/{x}/{y}.png"
  },
  {
    name: "opentopomap b",
    url: "https://b.tile.opentopomap.org/{z}/{x}/{y}.png"
  },
  {
    name: "opentopomap c",
    url: "https://c.tile.opentopomap.org/{z}/{x}/{y}.png"
  }
];

SELECTED_TILE_SERVER = TILE_SERVERS[user.prefs.user_map_srv].url;
if (!document.querySelector("#mapCHGbtn")) {
  say("no devices yet")
} else {

  document.querySelector("#mapCHGbtn").innerText = "change Map server:" + TILE_SERVERS[user.prefs.user_map_srv].name;

}

TILE_SERVERS.forEach((tsob, idx) => {
  tsoption = document.createElement("option");
  tsoption.innerText = tsob.name;
  tsoption.value = idx;
  if (user.prefs.user_map_srv == idx) {
    tsoption.selected = true;
  }
  mapCHGselect.append(tsoption);
})


GreenFilter = {
  brightness: user.prefs.user_map_srv < 3 ? 4 : 1,
  rM: 0.5 * (user.prefs.user_map_srv < 3 ? 4 : 1),
  gM: 1 * (user.prefs.user_map_srv < 3 ? 4 : 1),
  bM: 0.5 * (user.prefs.user_map_srv < 3 ? 4 : 1),
  isOn: user.prefs.user_green_filter,
  defs: {
    On: {
      rM: 0.5,
      gM: 1,
      bM: 0.5,
    },
    Off: {
      rM: 1,
      gM: 1,
      bM: 1,
    },
  },
  toggle(btn) {
    this.isOn = !this.isOn;
    btn.innerText = "Green Filter:" + (this.isOn ? "On" : "Off");
    this.updateValues();
  },
  updateValues(){
    if (this.isOn) {
      this.brightness = user.prefs.user_map_srv < 3 ? 4 : 1;
      this.applyFilter = potentially__increaseCanvasBrightness2;
      this.rM = this.defs.On.rM * this.brightness;
      this.gM = this.defs.On.gM * this.brightness;
      this.bM = this.defs.On.bM * this.brightness;
    } else {
      // filter is off , so if map is not dark, 
      //chnage applyFilter into no_op function to not take cpu on canvas calc
      this.applyFilter = user.prefs.user_map_srv < 3 ? potentially__increaseCanvasBrightness2 : NO_OP;
      this.brightness = user.prefs.user_map_srv < 3 ? 4 : 1;
      this.rM = this.defs.Off.rM * this.brightness;
      this.gM = this.defs.Off.gM * this.brightness;
      this.bM = this.defs.Off.bM * this.brightness;
    } 
  },
  applyFilter(context)
  {
   //this is initial only , will be overwritten if anything changing map will call updatevalues on Green_filter 
   return user.prefs.user_map_srv < 3 ? potentially__increaseCanvasBrightness2(context) : 0; 
  }
}

MapChanger = {
  idx: user.prefs.user_map_srv,
  maps: TILE_SERVERS,
  lnt: TILE_SERVERS.length,
  curMap: TILE_SERVERS[user.prefs.user_map_srv],
  nextMap(btn) {

    this.idx = (this.idx + 1 < this.lnt) ? this.idx + 1 : 0;
    this.curMap = this.maps[this.idx];
    btn.innerText = "change Map server:" + this.curMap.name;
    this.updateDependants();
  },
  updateDependants() {
    user.prefs.user_map_srv = this.idx;
    GreenFilter.updateValues(); // if map is not dark removes brightening 
    //brightness = user.prefs.user_map_srv < 3 ? 4 : 1;
    changeMapUrl(this.curMap.url);

  },
  setMap(idx) {
    this.idx = idx;
    this.curMap = this.maps[this.idx];
    this.updateDependants();
  }

}

function NO_OP(){};


function potentially__increaseCanvasBrightness2(context) {
  let canvas = context.canvas;
  let width = canvas.width;
  let height = canvas.height;
  rM = GreenFilter.rM;
  gM = GreenFilter.gM;
  bM = GreenFilter.bM;
  let inputData = context.getImageData(0, 0, width, height).data;
  let output = context.createImageData(width, height);
  let outputData = output.data;

  for (let pixelY = 0; pixelY < height; ++pixelY) { //every row  y
    let pixelsAbove = pixelY * width;
    for (let pixelX = 0; pixelX < width; ++pixelX) { //every pixel x
      let r = 0,
        g = 0,
        b = 0,
        a = 0;
      // for (let kernelY = 0; kernelY < size; ++kernelY) {//every neighbour
      //   for (let kernelX = 0; kernelX < size; ++kernelX) {
      //     let weight = kernel[kernelY * size + kernelX];
      //     let neighborY = Math.min(
      //       height - 1, Math.max(0, pixelY + kernelY - half));
      //     let neighborX = Math.min(
      //       width - 1, Math.max(0, pixelX + kernelX - half));

      //     let inputIndex = (neighborY * width + neighborX) * 4;
      weight = 1;
      inputIndex = (pixelsAbove + pixelX) * 4;
      r += inputData[inputIndex] * weight * rM;
      g += inputData[inputIndex + 1] * weight * gM;
      b += inputData[inputIndex + 2] * weight * bM;
      a += inputData[inputIndex + 3] * weight;
      //   }
      // }
      let outputIndex = (pixelsAbove + pixelX) * 4;
      //brightness 

      outputData[outputIndex] = r; //> 255 ? 255: r;
      outputData[outputIndex + 1] = g; //> 255 ? 255: g;
      outputData[outputIndex + 2] = b; //> 255 ? 255: b;
      outputData[outputIndex + 3] = a;// kernel.normalized ? a : 255;
    }
  }
  context.putImageData(output, 0, 0);
}





function NuMap(mapLat, mapLng, mapDefaultZoom, tileLayer) {
  return new ol.Map({
    target: "map",
    layers: [
      tileLayer
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
    geometry: new ol.geom.Point(ol.proj.transform([parseFloat(lng), parseFloat(lat)], 'EPSG:4326', 'EPSG:3857')),
    id: id,
  });

  f.setStyle(NuStyle(id + "\n" + status, status));
  return f;
}

function NuStyle(text, imagename) {
  return new ol.style.Style({
    image: atomIcons[imagename],
    text: new ol.style.Text({
      text: text,
      offsetY: 30,
      font: "12px joystix",
      stroke: new ol.style.Stroke({
        width: 10,
        color: "#000000"
      }),
      fill: new ol.style.Fill({
        color: '#20f86c'
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

function NuTileLayer(url) {
  return new ol.layer.Tile({
    source: new ol.source.OSM({
      url: url
    })
  });
}

function changeMapUrl(url) {
  if (!tileLayer) {
    say("no devices to show on map, so no map:P")
    return;
  }
  tileLayer.setSource(new ol.source.OSM({
    url: url
  }));
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
    active: NuIcon("active"),
    detonated: NuIcon("detonated")
  }
  tileLayer = NuTileLayer(SELECTED_TILE_SERVER);




allLayer = NuLayer([]);
  //musisz policzyc srednia albo znalesc na necie position map to see all markers
  window.map = NuMap(devicesWithLocation[0].location.latitude,
    devicesWithLocation[0].location.longitude,
    mapDefaultZoom, tileLayer);
  window.allFeaturesCollection = {};
  // convert locateable devices to features
  window.arrayOfFeaturesAll = devicesWithLocation.map(dv => {
    feature = NuFeature(dv.device_name, dv.device_status, dv.location.latitude, dv.location.longitude)
    allFeaturesCollection[dv.device_id] = feature;
    allLayer.getSource().addFeature(feature);
    return feature;
  });
  //allLayer = NuLayer(arrayOfFeaturesAll);
  map.addLayer(allLayer);
  tileLayer.on('postcompose', function (event) {
    GreenFilter.applyFilter(event.context);
  }, {
    passive: true
  });

//   var extent = ol.extent.createEmpty();
// allLayer.forEach(function(layer) {
//   ol.extent.extend(extent, layer.getSource().getExtent());
// });
map.getView().fit(allLayer.getSource().getExtent(), map.getSize());
//map.getView().fit(map.getView().calculateExtent(),map.getSize());

  return "map creation in progress";//no use just for me to indicate that it is async logic
}




// iiiii = 0;

// function fakeUpdate() {
//   iiiii++;
//   if (iiiii > 10) {
//     return;
//   }
//   window.devicesWithLocation.forEach(dv => {

//     f = allFeaturesCollection[dv.device_id];
//     //.setGeometry(new ol.geom.Point(pos));
//     coords = f.getGeometry().getCoordinates();
//     coords = ol.proj.transform(coords, 'EPSG:3857', 'EPSG:4326');
//     //say(JSON.stringify(coord))
//     // console.log(coords);
//     console.log(coords);
//     coords[0] += (Math.random() > 0.5 ? 1 : -1) * 0.001;
//     coords[1] += (Math.random() > 0.5 ? 1 : -1) * 0.001;
//     console.log(coords);
//     freshValue = coords.reverse().join("/");
//     console.log(freshValue);
//     updateMarkerLocation({}, freshValue, dv, "device_location");
//     //works weirdly slowly
//     say("moved geometry")
//   })
//   return;
//   //stage1 just features

//   //updateMarkerLocation(notthis,fv,dv,col);
// }


watchmode.onUpdate = updateMarkerLocation;

//  window.setInterval(x=>{
// fakeUpdate();

// say("updated");
// },1000);



// this has to be run on user changing the table and saving !!!!
//** */
function updateMarkerLocation(watchmodeinstance, freshValue, device, columnName) {
  alert(columnName)
  switch (columnName) {
    case "device_location":
      updateMarkerLocation2(freshValue, device)
      break;

    case "device_status":
      updateMarkerStatus(freshValue, device)
      break;

    default:
      return;
      break;
  }
}



function updateMarkerStatus(freshValue, device) {
  updatedFeature = allFeaturesCollection[device.device_id];
  if (!updatedFeature) {
    // because it has no location
    return;
  }
  hasIcon = Object.keys(atomIcons).includes(freshValue);
  updatedFeature.getStyle().getText().setText(device.device_name + "\n" + freshValue);
  //if has Icon update icon and text else update only text
  //*** */
  if (hasIcon) {
    updatedFeature.getStyle().setImage(atomIcons[freshValue]);
  }
  updatedFeature.changed();
}

function updateMarkerLocation2(freshValue, device) {
  locArr = freshValue.split("/");
  locOb.latitude = locArr[0];
  locOb.longitude = locArr[1];
  locArr[0] = parseFloat(locArr[0]);
  locArr[1] = parseFloat(locArr[1]);
  updatedFeature = allFeaturesCollection[device.device_id];
  //  updatedFeature.setGeometry(new ol.geom.Point(locArr));
  updatedFeature.set('geometry', new ol.geom.Point(ol.proj.fromLonLat([locArr[1], locArr[0]])));
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
        '<div id="map" tabindex="-1"></div>';
      window.addEventListener("load", function () {
        showDevices(tableData);

      });
    } else {
      say("no devices that provide location coords yet" +
        +" \n not showing the map");
    }


  }
}

mapCHGselect.onchange = function () {
  MapChanger.setMap(mapCHGselect.value);
};

mapCHGbtn.onclick = function (ev) {
  MapChanger.nextMap(ev.target);
  map.render();
};

FilterTogglerBtn.onclick = function (ev) {
  GreenFilter.toggle(ev.target);
  map.render();
}

startMap();

//MapChanger.setMap(user.prefs.user_map_srv);








say("map module ok");