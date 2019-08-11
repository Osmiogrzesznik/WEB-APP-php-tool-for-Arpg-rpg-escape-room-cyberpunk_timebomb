
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


  say("locate module ok");