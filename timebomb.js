//    //change send location intercval


try {


    const SILENT_MODE = false;
    audioContext = null;
    beepBeepingBuffer = null;


    function playSoundAt(buffer, time) {
        var source = audioContext.createBufferSource(); // creates a sound source
        source.buffer = buffer; // tell the source which sound to play
        source.connect(audioContext.destination); // connect the source to the context's destination (the speakers)
        source.start(time); // play the source now
        // note: on older systems, may have to use deprecated noteOn(time);
    }



    cx = new ClockController(counter, counter_sec, counter_min, counter_hour);

    // Fullscreen - first user interaction - so set up sounds as well
    addEventListener("click", prepareAudioAPI);

    function prepareAudioAPI() {

        if (!devLocate.isLocating) {
            devLocate.startLocating();
        };

        var el = document.documentElement,
            rfs = el.requestFullScreen || el.webkitRequestFullScreen || el.mozRequestFullScreen;
        rfs.call(el);

        if (!SILENT_MODE) {
            try {
                // Fix up for prefixing

                window.AudioContext = window.AudioContext || window.webkitAudioContext;
                audioContext = new AudioContext();
                console.log("audio context created");


                var request = new XMLHttpRequest();
                request.open('GET', "sounds/beep.mp3", true);
                request.responseType = 'arraybuffer';
                // Decode asynchronously
                request.onload = function () {
                    audioContext.decodeAudioData(request.response, function (buffer) {
                        // _________________________________________
                        // this is the actual end of logic !!!!
                        // _________________________________________
                        // this is the actual end of logic !!!!
                        beepBeepingBuffer = buffer;
                        playSoundAt(beepBeepingBuffer, 0);

                        cx.onStop = function (cxthis) {
                            console.log("timer has stopped , stopping metronome");
                            stop(); //stop the
                        }
                        // sound here not needed As metronome will take care
                        cx.onTick = function (cxthis) {
                            if (cxthis.remainingTime < 60000) {
                                if (cxthis.remainingTime < 30000) {
                                    if (cxthis.remainingTime < 15000) {
                                        noteResolution = 0;
                                        return;
                                    }
                                    noteResolution = 1;
                                    return;
                                }
                                noteResolution = 2;
                                return;
                            }
                        }
                        unlocked = true;
                        init();
                        console.log(play());
                    }, onError);
                }
                onError = function () {
                    cx.onTick = function (cxthis) {
                        ticksound.play();
                    }
                }
                request.send();
            } catch (e) {
                alert('Web Audio API is not supported in this browser, fallback to time interval');
                cx.onTick = function (cxthis) {
                    ticksound.play();
                }
            }
            //always (doesnt need timing)

            kb.onIncorrectPassword = kbctrl => {
                wrongSound.play();
            }

        } //end of if (!SILENT_MODE)
        window.removeEventListener("click", prepareAudioAPI);
        counterCNT.addEventListener("click", toggleKeyboard, false); //after fullscreening clicking again shows keyboard
    }


    // debug tools
    log = x => console.log(x);
    jlog = x => console.log(JSON.stringify(x, null, 2));


    //dobre do php raczej
    // templ = "@font-face { font-family: \"XXXNAME\";src: url(\"fonts/XXXFILE\") format(\"truetype\"); }"
    // fetch("allFonts.txt").then(x => x.text())
    //     .then(x => {
    //         fls = x.trim().split("\n");
    //         nms = fls.map(x => x.replace(".ttf", "").replace(".TTF", ""));
    //         cx.fonts._b = nms;
    //         s = document.createElement("style");
    //         fls.forEach((f, i) => {
    //             s.innerText += templ.replace("XXXNAME", nms[i]).replace("XXXFILE", f);
    //         });
    //         document.body.insertAdjacentElement("afterbegin", s);
    //     })

    function toggleKeyboard() {
        kb.toggle();
    }

    //defaults
    device_password = "0000";
    time_set = Date.now() + 30000;

    devLocate = {
        locateURL: "index.php?action=locate",
        serverRequiresLocating: true,
        serverRequiresPrecision: false,
        watchPositionID: null,
        isLocating: false,
        lastError: "",
        wasError: false,
        wasUpdated: false,
        pos_status: "",
        position: {
            latitude: null,
            longitude: null
        },
        isOKtoSend() {
            return !this.wasError && this.wasUpdated && this.isLocating;
        },
        updatePosition(latitude, longitude) {
            this.isLocating = true;
            this.wasUpdated = true;
            this.position.latitude = latitude;
            this.position.longitude = longitude;
        },
        getLocationSuffix() {
            let suffix = this.isOKtoSend() ?
                ("&latitude=" + this.position.latitude +
                    "&longitude=" + this.position.longitude) :
                "";
            return suffix;
        },
        startLocating() {
            if (!this.serverRequiresLocating) {
                return false;
            }
            if (this.isLocating) {
                return true;
            }
            if (this.wasError) {
                return false;
            }
            if (!navigator.geolocation) {
                this.wasError = true;
                this.pos_status.textContent = 'Geolocation is not supported by your browser';
                return false;
            } else {
                this.pos_status.textContent = 'Locating…';
                let positionOptions = {
                    enableHighAccuracy: this.serverRequiresPrecision
                };
                this.watchPositionID = navigator.geolocation.watchPosition(
                    pos => this.locationSuccess(pos),
                    err => this.locationError(err),
                    positionOptions);
            }
        },
        stopLocating() {
            navigator.geolocation.clearWatch(this.watchPositionID);
            this.pos_status.textContent = 'Stopped Locating.';
            this.isLocating = false;
        },
        locationSuccess(position) {
            this.wasError = false;
            this.isLocating = true;
            console.log(position);
            this.updatePosition(position.coords.latitude, position.coords.longitude)
            locsuffix = this.getLocationSuffix();
            fetch(this.locateURL + locsuffix)
                .then(p => p.text())
                .then(t => {

		console.log(t);
		j= JSON.parse(t);
	updateStateFromServerResponse(j);

})
.catch(err=>alert(err))
        },
        locationError(error) {
            this.wasError = true;
            this.lastError = error.code + ' : ' + error.message;
            this.pos_status.textContent = 'Unable to retrieve your location';
            this.isLocating = false;
        }
    }





    // if ("geolocation" in navigator) {
    //     /* geolocation is available */
    //     navigator.geolocation.getCurrentPosition(position=>{
    //         //document.querySelector(".counterCNT").innerText += 
    //         alert("lat:"+
    //         position.coords.latitude +"lon:" + position.coords.longitude
    //         )
    //         ;})
    //   } else {
    //     /* geolocation IS NOT available */
    //   }
    // //alert("start");




    //not needed nor used now
    function displayRemainingAttempts(att) {
        remainingAttemptsV.innerText = att;
    }

    function detonate() {
        document.body.classList.add("transitionable");
        setTimeout(x => {
            document.body.classList.add("WrongAnswer");
            document.body.classList.add("boom");
            setTimeout(x => {
                document.body.classList.remove("boom");
                document.body.innerHTML = "";
            }, 1000);
        }, 1000);

    }




    function startBombWithData(data) {

        if (["disarmed", "detonated"].includes(data.device_status)) {
            alert("this device was already "+data.device_status);
            window.open("index.php?action=superuser","_self");
            return;
        }
alert("this device is "+data.device_status);

        time_set = new Date(data.time_set);
        //alert(time_set.getTime());

        let pswd = {
            length: data.password_length,
            isNumericOnly: data.password_contains_only_digits
        }
        window.kb = CodeKeyboard(pswd);

        kb.onPasswordEntered = function (kbctrl, psswd) {


            locsuffix = devLocate.getLocationSuffix();
            fetch("index.php?action=password&password=" + psswd + locsuffix)
                .then(response => response.json())
                .then(json => {
	
	updateStateFromServerResponse(json);
	
                    // alert(json);
                    // json = JSON.parse(json);
                    // ////alert(JSON.stringify(json));
                    feedbackPRE = document.body.querySelector("#feedback");
                    feedbackPRE.innerText = json.feedback;
                    if (json.password_ok) {
                        kbctrl.correctPasswordAnimation();
                        kbctrl.onCorrectPassword(kbctrl, str);
                    } else {
                        kbctrl.incorrectPasswordAnimation();
                        kbctrl.onIncorrectPassword(kbctrl, str);
                    }
                    // if(json.remaining_attemtps <= 0){
                    //     detonate();
                    //     }
                    //     else{
                    //     displayRemainingAttempts(json.remaining_attempts);
                    //     }
                })
                .catch(x => alert(x.stack));
            return true;
        }

        kb.onCorrectPassword = (kbctrl, enteredpassword) => {
            cx.stop();
            //send enteredpassword to server , there is vulnerability here as password should be not stored in javascript objects, user can easily retrieve it with devtools
        }

        //make keyboard with 1234 as a pswd

        // explosion effect on Finished
        cx.onFinished = function (c) {
            detonate();
        };








        // THis should be in separate file to allow using some parts of code to visualise when admin sets it up
        //Everything ready , start
        kb.hide();
        //cx.setLimit(0, 0, 6);
        cx.setTimestampEnd(time_set); // this sets the countdown timer to 40 seconds 
        cx.start();



    } // end odfstart bombwith data




    function startup() {
        fetchSettings();
    }

    function fetchSettings() {
        suffix = devLocate.getLocationSuffix();

        fetch("index.php?action=getsettings" + suffix)
            .then(x => x.json())
            // .then(x=>x.json())
            .then(json => {
                startBombWithData(json);

            })
            .catch(x => alert(x.stack));
    }


function updateStateFromServerResponse(j){
cx.setTimestampEnd(Date.parse(j.time_set));
if (j.device_status == "detonated"){
	cx.stop();
	cx.showTime(0,0,0);
detonate();
	}
if (j.device_status == "disarmed"){
cx.stop();
	}

}






    startup();


} catch (er) {
    alert(er.stack);

}