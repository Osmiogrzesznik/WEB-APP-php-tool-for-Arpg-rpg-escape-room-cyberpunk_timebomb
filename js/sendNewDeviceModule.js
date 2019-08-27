function sendNewDevice() {
  let latitude,
  longitutde;

    var FD = new FormData(document.querySelector("#new_device_form"));
    let fields = [];
    if (devLocate.isOKtoSend()){
    DEV_LOCATION = devLocate.getLocationObject();
    }else if(initial_device_location.value){
      let a = initial_device_location.value.split(",")
      DEV_LOCATION = {latitude:a[0],longitude:a[1]};
      
    }

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
        try {
          let j = JSON.parse(t);
          if (j.ok) {
            let reloadFunc = function(ev) {
              window.open(baseurl, "_self")
            }
            say(j.feedback + "\n click or press any key to see your device")
            .on('click', reloadFunc).on('keydown', reloadFunc);

          } else {
            say("there was a problem with form entries :\n" + j.feedback)
          }
        } catch (err) {
          say(err);
          say(t);
        }
        // confirm("if device added succesfully , click ok to refresh window"+t)?
        // window.open(baseurl,"_self"):0; //try to display modal else say

      })
      .catch(err => say(err));; // parses JSON response into native JavaScript objects 
    return false; //false;//return false to prevent form from reloading the page   
  }

  say("send new device module ok");