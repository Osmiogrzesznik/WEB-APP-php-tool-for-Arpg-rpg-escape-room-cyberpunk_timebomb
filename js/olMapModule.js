//----------------------------------------------------------------------------------------------------
//----------------------------------------------------------------------------------------------------
//----------------------------------------------------------------------------------------------------
//----------------------------------------------------------------------------------------------------
//--------------------------MAP MODULE--------------------------------------------------------------------------
//----------------------------------------------------------------------------------------------------
//----------------------------------------------------------------------------------------------------

//--
loadDrawnFeaturesUrl = "?action=js_loadfeatures";
saveDrawnFeaturesUrl = "?action=js_savefeatures";
deleteFeaturesURL = "?action=js_deletefeatures";
function lll(...msg) {
  console.log(msg);
  say(msg, 1)
}

var doNotUpdateLastFeatureColor = false;
var featureCounter = 0;
var lastFeature;
//   import Map from 'ol/Map.js';
//   import View from 'ol/View.js';
//   import Draw from 'ol/interaction/Draw.js';
//   import {Tile as TileLayer, Vector as VectorLayer} from 'ol/layer.js';
//   import {OSM, Vector as VectorSource} from 'ol/source.js';
var defaultStroke = new ol.style.Stroke({
  color: [128, 255, 128],
  width: 2
});
var DEFAULT_STROKE_WIDTH = 2;
var STROKE_WIDTH_SELECTED = 4;
var DEFAULT_FILL_OPACITY = 0.2;
var DEFAULT_STROKE_COLOR = [128, 255, 128];

function NuDefaultPointCircleImage(color) {
  return new ol.style.Circle({
    radius: 7,
    stroke: new ol.style.Stroke({
      color: color,
      width: DEFAULT_STROKE_WIDTH
    }),
    fill: new ol.style.Fill({
      color: color.concat(DEFAULT_FILL_OPACITY)
    }),
  })
}

var defaultDrawnTextStyle = new ol.style.Text({
  text: "Untitled MapObject",
  font: " 9px joystix",
  stroke: new ol.style.Stroke({
    width: DEFAULT_STROKE_WIDTH,
    color: [0, 0, 0, 0.8],
  }),
  fill: new ol.style.Fill({
    color: DEFAULT_STROKE_COLOR
  })
});
var defaultDrawnStyleOptions = {
  text: defaultDrawnTextStyle,
  image: new ol.style.Circle({
    radius: 7,
    stroke: new ol.style.Stroke({
      color: DEFAULT_STROKE_COLOR,
      width: DEFAULT_STROKE_WIDTH
    }),
    fill: new ol.style.Fill({
      color: DEFAULT_STROKE_COLOR.concat(DEFAULT_FILL_OPACITY)
    }),
  }),
  stroke: new ol.style.Stroke({
    color: DEFAULT_STROKE_COLOR,
    width: DEFAULT_STROKE_WIDTH
  }),
  fill: new ol.style.Fill({
    color: DEFAULT_STROKE_COLOR.concat(DEFAULT_FILL_OPACITY)
  }),
}
var defaultDrawnPointImage = new ol.style.Circle({
  radius: 7,
  stroke: new ol.style.Stroke({
    color: DEFAULT_STROKE_COLOR,
    width: DEFAULT_STROKE_WIDTH
  }),
  fill: new ol.style.Fill({
    color: DEFAULT_STROKE_COLOR.concat(DEFAULT_FILL_OPACITY)
  }),
})

var defaultStyle = new ol.style.Style(defaultDrawnStyleOptions);
var lastStyle = defaultStyle.clone();
var allPreviouslyDrawnFeaturesArray = [];
var allDrawnFeaturesCollection = new ol.Collection(allPreviouslyDrawnFeaturesArray, {
  unique: true
})

var drawnFeaturesSource = new ol.source.Vector({
  features: allDrawnFeaturesCollection,
  wrapX: false
});

var drawnVectorLayer = new ol.layer.Vector({
  source: drawnFeaturesSource
});
drawnVectorLayer.set("isDrawingLayer", true);

// ----------------------------------
/**
 * interactions
 * */

snap = new ol.interaction.Snap({
  source: drawnFeaturesSource,
  //features: allDrawnFeaturesCollection
});

var selectToDeleteInteraction = new ol.interaction.Select({
  source: drawnFeaturesSource,
  layers: x => x.get('isDrawingLayer'), //filter function - selecting only from drawing layer
  //features: allDrawnFeaturesCollection
});

var selectToEditInteraction = new ol.interaction.Select({
  // source: drawnFeaturesSource,
  layers: x => x.get('isDrawingLayer'), //filter function - selecting only from drawing layer
  //features: allDrawnFeaturesCollection
});


/**
 * SELECTTOEDTI HADLER
 * 
 * */
selectToEditInteraction.on("select", ev => {
  //--SETS THE INPUT VALUES TO THE 
  //STYLE OF CURRENTLY SELECTED FEATURE OR lAST EDITED fEATURE 
  //todo: enclose in separate function
  //-----------------------------------------------------------
  if (ev.selected.length) {
    lastFeature = ev.selected[
      0]; //IF USER SELECTs NEW FEATURE IT WILL BECOME TARGET OF STYLE INTEREST
  }
  let lfs = lastFeature.getStyle() || defaultStyle;
  ccc = lfs.getStroke().getColor() || DEFAULT_STROKE_COLOR;
  console.log(ccc)
  jscolorInput.jscolor.fromRGB(ccc[0], ccc[1], ccc[2]);
  featureNameInput.value = lfs.getText().getText() || "";
  //----------------------------------------------------------------
  ed = ev;
  edel = selectToEditInteraction
    .getFeatures()
    .getArray()
    .map(x => x.getProperties().id)

  lll("selectToEditInteraction.getFeatures() ids:" + edel
    .join(",")
  )
  //       lll("selection event")	
  ev.deselected.forEach(feat => { //this works slow
    //let feat = ev.deselected[0];
    //you can stylize differently last selected item
    feat.getStyle().getStroke().setWidth(DEFAULT_STROKE_WIDTH);
    feat.getStyle().getStroke().setLineDash([]);
    feat.changed();
    lll("deselected no " + feat.get('id'))
  });

  if (ev.selected.length > 1) {
    lll('multiple selected');
  }

  ev.selected.forEach(feat => {
    feat.getStyle().getStroke().setLineDash([5, 5]);
    feat.getStyle().getStroke().setWidth(STROKE_WIDTH_SELECTED);
    feat.changed();
    lll("selected no " + feat.get('id'));
  })

  //change bar name to something that indicates there is 
  //selection going on
})

function updateLastFeatureColor(jscolorpicker) {
  lll("updating color style" + jscolorpicker.toHEXString());
  let selectedFeatures = selectToEditInteraction.getFeatures().getArray()
  if (!selectedFeatures.length) {
    // if no features selected apply changes to array made of last edited element
    selectedFeatures = lastFeature ? [lastFeature] : [];
  }

  if (window.lastFeature && lastFeature.getStyle()) {
    lastStyle = lastFeature.getStyle().clone();
  } else if (!window.lastStyle) {
    lastStyle = defaultStyle.clone();
  }

  c = jscolorpicker.rgb;
  lastStyle.getStroke().setColor(c);
  lastStyle.getFill().setColor([c[0], c[1], c[2], DEFAULT_FILL_OPACITY]);
  // lastStyle.getText().getStroke().setColor("black")
  lastStyle.getText().getFill().setColor(c);
  lastStyle.getStroke().setWidth(DEFAULT_STROKE_WIDTH);

  selectedFeatures.forEach(feat => {
    if (feat.getGeometry().getType() === "Point") {
      circleImage = NuDefaultPointCircleImage(c);
      lastStyle.setImage(circleImage);
    }

    lastFeature.setStyle(lastStyle.clone());
    lastFeature.set("color", c)
  })

  jscolorpicker.hide();
  map.getTargetElement().focus({
    preventScroll: true
  }); // cant do this - moves the brwoser view
}


function updateLastFeatureEffect(effect_id) {
  let selectedFeatures = selectToEditInteraction.getFeatures().getArray()
  if (!selectedFeatures.length) {
    // if no features selected apply changes to array made of last edited element
    selectedFeatures = lastFeature ? [lastFeature] : [];
  }

  selectedFeatures.forEach(feat => {
    lastFeature.set("effectd_id_fk", effect_id);
  });

  map.getTargetElement().focus({
    preventScroll: true
  }); // cant do this - moves the brwoser view
}

function updateLastFeatureName(input) {
  lll("updating text style")
  niuname = input.value;

  //this part is the same  factor it out ?
  let selectedFeatures = selectToEditInteraction.getFeatures().getArray()
  if (!selectedFeatures.length) {
    // if no features selected apply changes to array made of last edited element
    selectedFeatures = lastFeature ? [lastFeature] : [];
  }

  if (window.lastFeature && lastFeature.getStyle()) {
    lastStyle = lastFeature.getStyle().clone();
  } else if (!window.lastStyle) {
    lastStyle = defaultStyle.clone();
  }


  var textStyle = defaultDrawnTextStyle.clone();
  textStyle.setText(niuname);
  textStyle.getFill().setColor(lastStyle.getStroke().getColor());
  lastStyle.setText(textStyle.clone()); // will all features below have the same object as their text?
  ///check!!! should you use smth like 
  //let feattextstyle = textStyle.clone(); 
  lastStyle.getStroke().setWidth(DEFAULT_STROKE_WIDTH);

  selectedFeatures.forEach(feat => {
    feat.set("name", niuname);
    feat.setStyle(lastStyle.clone());
  })


  input.blur();
  map.getTargetElement().focus({
    preventScroll: true
  });
}

var modifyInteraction = new ol.interaction.Modify({
  features: selectToEditInteraction.getFeatures(),
});

modifyInteraction.on("modifystart", ev => {
  ed = ev;
  console.log("starting modification")
})
modifyInteraction.on("modifyend", ev => {
  ed = ev;
  console.log("ending modification")
});

var typeSelect = document.getElementById('type');

// map.addInteraction(modifyInteraction);

var draw; // global so we can remove it later



function addInteractions(newMode) {
  map.set("current_mode", newMode);
  lastStyle = window.lastFeature ? lastFeature.getStyle().clone() : defaultStyle.clone();
  lastStyle.getStroke().setWidth(DEFAULT_STROKE_WIDTH);
  lastStyle.getStroke().setLineDash([]);
  window.lastFeature ? lastFeature.setStyle(lastStyle) : 0; //prevents dash line remain
  map.getTargetElement().focus({
    preventScroll: true
  });;
  map.removeInteraction(snap);
  selectToEditInteraction.getFeatures().clear();
  selectToDeleteInteraction.getFeatures().clear();


  settings = {
    source: drawnFeaturesSource,
    //features: allDrawnFeaturesCollection,
    type: newMode,
  }

  switch (newMode) {
    case 'SelectAndDelete':
      // do nothing 
      map.addInteraction(snap)
      map.addInteraction(selectToDeleteInteraction);
      selectToDeleteInteraction.on("select", deleteFeature);
      break;

    case 'SelectAndEdit':
      // do nothing 
      map.addInteraction(selectToEditInteraction);
      map.addInteraction(modifyInteraction);

      //selectInteraction.getFeatures().on("add",x=>{lastFeature=x.element})

      break;

    case 'Point':
      settings.style = null;
      //continues to default
    default:
      draw = new ol.interaction.Draw(settings);
      map.addInteraction(draw);
      /*     snap = new ol.interaction.Snap({
                            source: drawnFeaturesSource,
                            features: allDrawnFeaturesCollection
                        });
*/

      draw.on("drawstart", ev => {
        lastStyle.getStroke().setWidth(DEFAULT_STROKE_WIDTH);
        lastStyle.getStroke().setLineDash([]);
        console.log("starting drawing")

      })
      draw.on("drawend", (event) => {
        console.log("ending drawing")
        //rememberLastEditedFeature(event)
        featureCounter++;
        lastFeature = event.feature;
        currentStyle = window.lastStyle ? lastStyle.clone() : defaultStyle.clone();
        color = currentStyle.getStroke().getColor();
        featureID = "tempId" + featureCounter;
        name = featureNameInput.value || "Untitled " + featureID;
        event.feature.setProperties({
          'id': featureID,
          'name': name,
          'color': color,
          'effect_id_fk':1,
          'effect_on':false,
          'description':'empty desc'
        });
        event.feature.setId(featureID);
        currentStyle.getText().setText(name); //this
        lastFeature.setStyle(currentStyle);
        lastFeature.getStyle().getText().setText(name); //and this is redundant ?
      })

      map.addInteraction(snap);
      break;

  }



  //map.addInteraction(selectInteraction);

}

function selectAll(ev, kbl) {
  lll("selecting all");
  return true;
}



var KeyboardListeners = {
  keyUp(event) {
    changed = this.setKeyVal(event.key, false);
    lll('kEYuP' + (changed ? " changed " : "left ") + event.key + " being" + this.isPressed(event
      .key));
    if (changed && this.onKeyUpHandlers.hasOwnProperty(event.key)) {
      lll("trying to run handler")
      handlerRan = this.onKeyUpHandlers[event.key](event, this);
      lll("handlerRan" + handlerRan);
    }
  },
  keyDown(event) {
    this.setKeyVal(event.key, true);
    lll('KEYDOWN' + (changed ? " changed " : "left ") + event.key + " being" + this.isPressed(event
      .key));
    if (changed && this.onKeyDownHandlers.hasOwnProperty(event.key)) {
      lll("trying to run handler")
      handlerRan = this.onKeyDownHandlers[event.key](event, this);
      lll("handlerRan" + handlerRan);
    }
  },
  addOnKeysDown(combinationArray, listenerFunc) {
    throw new Error("not implemented yet");
    // combinationArray.forEach(keyname=>{
    //     this.setKeyVal(keyName,false); // prepare keys to be listened for(not exactly needed as they are added automatically)

    // });
    // let lastkey = combinationArray[combinationArray.length-1];
    // this.onKeyDow
    // throw new Error("not implemented yet");
    // map.getTargetElement().addEventListener("keydown", keyDownListener);
    // map.getTargetElement().addEventListener("keyup", keyUpListener);
  },
  addOnKeysUp(combinationArray, listenerFunc) {
    throw new Error("not implemented yet");
  },
  onKeyUpHandlers: {},
  onKeyDownHandlers: {
    Delete: (e, t) => deleteFeature(e, t),
    a: (e, t) => {
      e.preventDefault();
      return t.isPressed("Control") && selectAll(e, t);
    },
    Escape: (e, t) => {
      if (map.get("current_mode") === "Circle") {
        draw.setActive(false);
        draw.setActive(true);
      }
      draw.removeLastPoint();
    }
  },
  setKeyVal(key, booly) {
    changed = (this.keyVals[key] !== booly)
    this.keyVals[key] = booly;
    return changed;
  },
  isPressed(key) {
    return this.keyVals[key];
  },
  keyVals: {}, //this is flags container , it will fill with key:boolean pairs indicating whether button is pressed or not
}

function keyDownListener(event) {
  KeyboardListeners.keyDown(event);
  //console.log(event.key + " DOWN " + event.keyCode);
}

function keyUpListener(event) {

  KeyboardListeners.keyUp(event);
  //console.log(event.key + " up " + event.keyCode);
}

function deleteFeature(event) {
  ed = event;
  let featuresToDelete = [];
  switch (event.type) {
    case "select":
      say("selection event", 1);
      if (!event.selected.length) {
        alert("deselected");
        return;
      }
      featuresToDelete = event.selected;
      break;
    case "add":
      featuresToDelete = [event.element];
      break;
    case "keydown":
      //check if there are some features selected
      if (selectToEditInteraction.getFeatures().getLength()) {
        featuresToDelete =
          selectToEditInteraction.getFeatures();
      } else if (window.lastFeature) {
        //else target of deletion will be last clicked/drawn feature
        featuresToDelete = [lastFeature];
      } else {
        //else delete nothing
        featuresToDelete = [];
      }
      default:
        break;
  }
  let idsForDBDeletion = [];
  featuresToDelete.forEach(feat => {
    let id = feat.getId();
    if (!(id+"").startsWith("tempId")){
      idsForDBDeletion.push(id);
    }
     
    console.log("deleting no" + feat.getId() + " name:" + feat.get("name"));
    drawnFeaturesSource.removeFeature(feat);
    allDrawnFeaturesCollection.remove(feat);
  })
  selectToEditInteraction.getFeatures().clear();
  selectToDeleteInteraction.getFeatures().clear();
  if (idsForDBDeletion.length){
    let jsonids = JSON.stringify(idsForDBDeletion)
    alert("deleting from DB"+jsonids)
    fetch(deleteFeaturesURL, {
      method: 'POST', // *GET, POST, PUT, DELETE, etc.
      // mode: 'cors', // no-cors, cors, *same-origin
      // cache: 'no-cache', // *default, no-cache, reload, force-cache, only-if-cached
      credentials: 'include', // include, *same-origin, omit
      // headers: {
      'Content-Type': 'application/json',
      //       'Content-Type': 'application/x-www-form-urlencoded'
      // },
      //redirect: 'manual', // manual, *follow, error
      // referrer: 'no-referrer', // no-referrer, *client
      body: jsonids // body data type must match "Content-Type" header
    })
    .then(response => response.text())
    .then(t => {
      try {
        say("deletion response came");
        say(t);
        let j = JSON.parse(t);
        window.j = j;
        say(j.successStatuses);
      } catch (err) {
        say(err);
        say(t);
        console.log("hahahah")
        console.log(feedback.innerText);
      }
      // confirm("if device added succesfully , click ok to refresh window"+t)?
      // window.open(baseurl,"_self"):0; //try to display modal else say

    })
    .catch(err => say(err));
  }
}


/**
 * Handle change event.
 */
function drawingModeChange(newDrawingModeName) {
  map.removeInteraction(draw);
  map.removeInteraction(snap);
  map.removeInteraction(modifyInteraction)
  map.removeInteraction(selectToEditInteraction);
  map.removeInteraction(selectToDeleteInteraction);
  addInteractions(newDrawingModeName);

  map.getTargetElement().focus({
    preventScroll: true
  });
};


function saveDrawnFeatures() {
  feats = drawnFeaturesSource.getFeatures(); //.getArray();
  g = new ol.format.GeoJSON(); //undefined???
  ret = [];
  feats.forEach(feat => {
    let o = {};
    o.color = feat.get("color").map(x => ~~x);
    o.name = feat.get("name");
    o.id = feat.get("id");
    o.effect_id = feat.get("effect_id");
    o.effect_on = feat.get("effect_on");
    o.description = feat.get("description");
    if (feat.getGeometry().getType() === "Circle") {
      o.type = "Circle";
      let gm = feat.getGeometry();
      o.radius = gm.getRadius(); //remeber to use setRadius() not use options radius?
      o.center = gm.getCenter();
    }
    feat.setProperties(o);
    jsonifiedfeat = g.writeFeature(feat);

    if (feat.getGeometry().getType() === "Circle") {
      jsoncircle = JSON.parse(jsonifiedfeat);
      gmt = {
        radius: o.radius,
        center: o.center,
        type: "Circle"
      };
      jsoncircle.geometry = gmt;
      jsonifiedfeat = JSON.stringify(jsoncircle);
    }
    ret.push(jsonifiedfeat);
  });

  retjson = "[" + ret.join(",") + "]";
  say(retjson).asNormal();
  fetch(saveDrawnFeaturesUrl, {
      method: 'POST', // *GET, POST, PUT, DELETE, etc.
      // mode: 'cors', // no-cors, cors, *same-origin
      // cache: 'no-cache', // *default, no-cache, reload, force-cache, only-if-cached
      credentials: 'include', // include, *same-origin, omit
      // headers: {
      'Content-Type': 'application/json',
      //       'Content-Type': 'application/x-www-form-urlencoded'
      // },
      //redirect: 'manual', // manual, *follow, error
      // referrer: 'no-referrer', // no-referrer, *client
      body: retjson // body data type must match "Content-Type" header
    })
    .then(response => response.text())
    .then(t => {
      try {
        say("response came");
        say(t);
        let j = JSON.parse(t);
        say(j.received);
        window.j = j;
        j.successStatuses.forEach(x=>{
          if(!x.success){return}
          u = drawnFeaturesSource.getFeatureById(x.oldId);
          u.setId(x.DBId);
          u.set("id",x.DBId);
          });
      } catch (err) {
        say(err);
        say(t);
        console.log("hahahah")
        console.log(feedback.innerText);
      }
      // confirm("if device added succesfully , click ok to refresh window"+t)?
      // window.open(baseurl,"_self"):0; //try to display modal else say

    })
    .catch(err => say(err));; // parses JSON response into native JavaScript objects 
  return false; //false;//return false to prevent form from reloading the page   
}




function loadDrawnFeatures() {

  if (!confirm("load features?")) return;
  fetch(loadDrawnFeaturesUrl)
    .then(response => response.text())
    .then(t => {
      try {
        say("response came");
        say(t);
        let j = JSON.parse(t);
        say("decoded response");
        if (j.ok) {
          loadFeatures(j.features)
        } else {
          throw new Error("B: not ok response , feedback: " + j.feedback)
        }
      } catch (err) {
        say(err);
        say(t);
        alert(err);
        console.warn(err);
        throw err;
      }
      // confirm("if device added succesfully , click ok to refresh window"+t)?
      // window.open(baseurl,"_self"):0; //try to display modal else say

    })
    .catch(err => {
      alert(err);
      say(err);
      throw err;
    }); // parses JSON response into native JavaScript objects 
  return false; //false;//return false to prevent form from reloading the page   
}


function loadFeatures(j) {
  if (!j.length) {
    say("no saved features to show");
    return;
  }
  window.j = j;
  g = new ol.format.GeoJSON();
  //if (!confirm("Received response.sure to start loading features?")) return;
  j.forEach(f => {
    if (f.geometry.type === "Circle") {
      alert("circle", 1);
      feat = new ol.Feature(
        new ol.geom.Circle(
          f.geometry.center,
          f.geometry.radius))
      feat.setProperties(f.properties);
    } else {
      feat = g.readFeature(f);
    }
    let currentStyle = defaultStyle.clone();
    color = f.properties.color;
    name = f
      .properties.name;
    featureCounter = f.properties.id;
    currentStyle.getText()
      .setText(name); //this
    currentStyle.getText().getFill().setColor(color);
    currentStyle.getStroke().setColor(color);
    currentStyle.getFill().setColor(color
      .concat([DEFAULT_FILL_OPACITY]));
    currentStyle.setImage(NuDefaultPointCircleImage(color));
    feat.setStyle(currentStyle);
    feat.getStyle().getText().setText(name); //and this is redundant ?
    alert("\n feature loaded " + JSON.stringify({
      F:f.properties,id:feat.getId()
      // color: feat.getStyle().getStroke().getColor(),
      // name: feat.getStyle().getText().getText(),
    }));
    feat.setId(f.properties.id);
    drawnFeaturesSource.addFeature(feat);
  });

  map.getView().fit(drawnFeaturesSource.getExtent(), map.getSize());
}

function setupMapControlsListeners() {
  let radios = document.querySelectorAll(".radio-map-mode-input");
  for (var i = 0; i < radios.length; i++) {
    radios[i].addEventListener('change', function (ev) {
      drawingModeChange(this.value);
    });
  }
  let radios2 = document.querySelectorAll(".radio-effect-input");
  for (var i = 0; i < radios2.length; i++) {
    radios2[i].addEventListener('change', function (ev) {
      updateLastFeatureEffect(this.value);
    });
  }
}
window.addEventListener('load',setupMapControlsListeners);
//------------------------------------------------------------------------REST OF MAP MODULE
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
  say("no devices yet"); //niepotrzebne juz
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
  updateValues() {
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
  applyFilter(context) {
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

function NO_OP() {};


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
      outputData[outputIndex + 3] = a; // kernel.normalized ? a : 255;
    }
  }
  context.putImageData(output, 0, 0);
}





function NuMap(mapLat, mapLng, mapDefaultZoom, tileLayer) {
  return new ol.Map({
    target: "map",
    layers: [
      tileLayer, drawnVectorLayer
    ],
    view: new ol.View({
      center: ol.proj.fromLonLat([parseFloat(mapLng), parseFloat(mapLat)]),
      zoom: mapDefaultZoom
    })
  });
}




function NuLayer(sourcexxx) {
  return new ol.layer.Vector({
    source: sourcexxx
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
    say("no devices to show on map, so no map:P"); //niepotrzebne
    return;
  }
  tileLayer.setSource(new ol.source.OSM({
    url: url
  }));
}






function initializeMapAndShowDevices(devices) {
  alert("initializeMapAndShowDevices");
  if (devices.length < 1) {
    alert("NO DEVICES")
    say("No devices created yet. Do you want to Locate your current device to show map correctly?");
    //return;
  }

  isThereAnyDeviceWithLocation = false;
  for (let i = 0; i < devices.length; i++) {
    let dv = devices[i];
    //say(dv.device_location);
    doesDvHaveLocation = ![null, "no location", undefined].includes(dv.device_location);
    isThereAnyDeviceWithLocation = isThereAnyDeviceWithLocation || doesDvHaveLocation;

  }
  if (!isThereAnyDeviceWithLocation) {
    // return; //dont make map
  }

  window.devicesWithLocation = [];
  for (let i = 0; i < devices.length; i++) {
    let dv = devices[i];
    doesDvHaveLocation = ![null, "no location", "/", undefined].includes(dv.device_location);

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


  //musisz policzyc srednia albo znalesc na necie position map to see all markers
  window.map = NuMap(1, 1, mapDefaultZoom, tileLayer);

  // from OLDRAW-----------------{
  map.getTargetElement().addEventListener("keydown", keyDownListener);
  map.getTargetElement().addEventListener("keyup", keyUpListener);
  drawingModeChange("Circle");
  loadDrawnFeatures();
  map.getTargetElement().focus({
    preventScroll: true
  });
  // from OLDRAW-----------------}

  window.allLocatedDeviceFeaturesCollection = {};
  // convert locateable devices to features
  var allDevicesCollection = new ol.Collection([], {
    unique: true
  })

  allDevicesSource = new ol.source.Vector({
    features: allDevicesCollection,
    wrapX: false
  });
  window.allDevicesLayer = new ol.layer.Vector({
    source: allDevicesSource
  });

  window.arrayOfFeaturesAll = devicesWithLocation.map(dv => {
    feature = NuFeature(dv.device_name, dv.timebomb_status, dv.location.latitude, dv.location.longitude)
    allLocatedDeviceFeaturesCollection[dv.device_id] = feature;
    allDevicesSource.addFeature(feature);
    return feature;
  });
  //allDevicesLayer = NuLayer(arrayOfFeaturesAll);
  map.addLayer(allDevicesLayer);
  tileLayer.on('postcompose', function (event) {
    GreenFilter.applyFilter(event.context);
  }, {
    passive: true
  });

  //   var extent = ol.extent.createEmpty();
  // allDevicesLayer.forEach(function(layer) {
  //   ol.extent.extend(extent, layer.getSource().getExtent());
  // });
  //alert(allDevicesSource.getFeatures());
  if (allDevicesSource.getFeatures().length > 0) {
    map.getView().fit(allDevicesSource.getExtent(), map.getSize());
  } else {
    //say("setting zoom to 10");
    //map.getView().setZoom(10);
  }
  //map.changed();
  //map.getView().fit(map.getView().calculateExtent(),map.getSize());

  return "map creation in progress"; //no use just for me to indicate that it is async logic
}




// iiiii = 0;

// function fakeUpdate() {
//   iiiii++;
//   if (iiiii > 10) {
//     return;
//   }
//   window.devicesWithLocation.forEach(dv => {

//     f = allLocatedDeviceFeaturesCollection[dv.device_id];
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

    case "timebomb_status":
      updateMarkerStatus(freshValue, device)
      break;

    default:
      return;
      break;
  }
}



function updateMarkerStatus(freshValue, device) {
  updatedFeature = allLocatedDeviceFeaturesCollection[device.device_id];
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
  updatedFeature = allLocatedDeviceFeaturesCollection[device.device_id];
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
  }
}

window.addEventListener("load", function () {
  initializeMapAndShowDevices(tableData);
});

//   if (isThereAnyDeviceWithLocation) {
//     // show map only if there are any located devices
// //    document.querySelector("#mapDIV").innerHTML =
//     //  '<div id="map" tabindex="-1"></div>'
//     });
//   } else {
//     say("no devices that provide location coords yet" +
//       +" \n not showing the map");
//   }



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