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
  this.removeClassChanges = function() {
    changed = tableToEdit.getElementsByClassName("changed");
    [].forEach.call(changed, function(el) {
      el.classList.remove("changed");
    });
    updatedAnim = tableToEdit.getElementsByClassName("updatedAnim");
    [].forEach.call(updatedAnim, function(el) {
      el.classList.remove("updatedAnim");
    });
  }
  this.stop = function() {
    clearInterval(this.IID);
    this.isOn = false;
    this.removeClassChanges();
    tableToEdit.classList.remove("nonclickable");
    setTimeout(x => this.removeClassChanges(), 1000);
  };
  this.fetchToUpdate = function() {
    fetch(this.urlgetalldevices, {
        credentials: "include"
      })
      .then(x => {
        try {
          return x.json();
        } catch (e) {
          say(e.stack);
        }
      })
      .then(data => {
        if ((typeof data) === "string") {
          say(data)
        }
        this.update(data)
      })
      .catch(err => say(err.stack));
  };
  this.update = function(data) {
      if (!this.isOn) {
        return;
      }
      console.log(data);
      say("fetchOK", 1);
      cols = data.columnNames;
      rows = data.rows;

      for (let i = 0; i < rows.length; i++) {
        let row = rows[i];
        tr = tableToEdit.querySelector("#r" + row.device_id);
        if (!tr) { // new Device was added that is not yet in table
          // trTMPL = tableToEdit.querySelector("#r")*****
          /* and add device to map*/
          this.stop();
          let msg = "TODO: cloneNode the row and fill it in with data " +
            "\nyou need to refresh, there is a new device registered on your account"
          say(msg);
          open(baseurl); //just open new window - to much hassle ?
          return;
        }
        // later in api you can make it so cols are not all send
        // so only important/changeable stuff is updated
        for (let ci = 0; ci < cols.length; ci++) {
          col = cols[ci];
          field = tr.querySelector("#r" + row.device_id + col);
          if (!field) {
            continue;
          }
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
            }, 300);

            this.onUpdate(this, anew, row, col);

          } // end of if
        } //end of for

      } //end of for


    },


    //end of update
    this.onUpdate = function(watchmodeinstance, freshValue, device, columnName) {
      // noop function replaced by user
    };
} //end of watchmode func
watchmode = new Watchmode();

say("watchmode module loaded ok");