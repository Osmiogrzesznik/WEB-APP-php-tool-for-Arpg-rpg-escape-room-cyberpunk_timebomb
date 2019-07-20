//change send location intercval


try {

    function onError(error){
        alert(error);
    }

    const SILENT_MODE = false;
    audioContext = null;
    beepBeepingBuffer = null;

    window.addEventListener("load", x => {
        if (!SILENT_MODE) {

            try {
                // Fix up for prefixing

                window.AudioContext = window.AudioContext || window.webkitAudioContext;
                audioContext = new AudioContext();
                // ticksoundA = new Audio("sounds/beep.mp3");

                // //ticksoundA = document.getElementById("ticksound");
                // ticksound = audioContext.createMediaElementSource(ticksoundA);
                // //audioContext = new AudioContext().destination;
                // //ticksound.connect(audioContext.destination);
                // this.console.log(ticksound)
                // alert(JSON.stringify(ticksound));

                // cx.onTick = function (cxthis) {
                //     //  ticksound.start();
                // }

            } catch (e) {
                alert('Web Audio API is not supported in this browser, fallback to time interval');
            }
        }

        if (audioContext) {
            alert("audio context created");

            function loadSound(url) {
                var request = new XMLHttpRequest();
                request.open('GET', url, true);
                request.responseType = 'arraybuffer';

                // Decode asynchronously
                request.onload = function () {
                    audioContext.decodeAudioData(request.response, function (buffer) {
                        beepBeepingBuffer = buffer;
                    }, onError);
                }
                request.send();
            }

            loadSound("sounds/beep.mp3");
        }

        

    });

    function playSound(buffer) {
        var source = audioContext.createBufferSource(); // creates a sound source
        source.buffer = buffer;                    // tell the source which sound to play
        source.connect(audioContext.destination);       // connect the source to the context's destination (the speakers)
        source.start(0);                           // play the source now
                                                   // note: on older systems, may have to use deprecated noteOn(time);
      }



    cx = new ClockController(counter, counter_sec, counter_min, counter_hour);

    // Fullscreen - first user interaction - so set up sounds as well
    addEventListener("click", function () {
        var el = document.documentElement,
            rfs = el.requestFullScreen || el.webkitRequestFullScreen || el.mozRequestFullScreen;
        rfs.call(el);
        counterCNT.addEventListener("click", toggleKeyboard, false); //after fullscreening clicking again shows keyboard

        if (!SILENT_MODE) {

            // try {
            //     // Fix up for prefixing
            //     window.AudioContext = window.AudioContext || window.webkitAudioContext;
            //     audioContext = new AudioContext();
            // } catch (e) {
            //     alert('Web Audio API is not supported in this browser, fallback to time interval');
            // }


            // function playtick(){
            //     alert("this is null func");
            if (audioContext) {
                playSound(beepBeepingBuffer);
                // ticksound = document.getElementById("ticksound");
                // alert(JSON.stringify(ticksound));
                // // ticksoundA = new Audio("beep.mp3");
                // ticksound = audioContext.createMediaElementSource(ticksound);
                // //audioContext = new AudioContext().destination;
                // ticksound.connect(audioContext.destination);
                // this.console.log(ticksound)

                // cx.onTick = function (cxthis) {
                //   //  ticksound.start();
                // }
                //ticksound.start();
            } else {
                cx.onTick = function (cxthis) {
                    ticksound.play();
                }
            }

            //always (doesnt need timing)

            kb.onIncorrectPassword = kbctrl => {
                wrongSound.play();
            }

        } //end of if (!SILENT_MODE) 
    });


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
        lastError: "",
        wasError: false,
        wasUpdated: false,
        pos_status: "",
        position: {
            latitude: null,
            longitude: null
        },
        isOKtoSend() {
            return !this.wasError && this.wasUpdated;
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
        }
    }

    function locationSuccess(position) {
        devLocate.updatePosition(position.coords.latitude, position.coords.longitude)
    }

    function locationError(error) {
        devLocate.wasError = true;
        devLocate.lastError = error.code + ' : ' + error.message;
        devLocate.pos_status.textContent = 'Unable to retrieve your location';
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


    isLocating = false;

    function startBombWithData(data) {
        if (!isLocating) {
            window.sendLocationIID = setInterval(x => {
                locsuffix = devLocate.getLocationSuffix();
                fetch("index.php?action=password&" + locsuffix);
                locate();
            }, 8000);
            isLocating = true;
        }



        if (["disarmed", "detonated"].includes(data.device_status)) {
            //alert("this device was already disarmed")
            clearInterval(sendLocationIID);
            return;
        }
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
        locate();
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


    startup();


} catch (er) {
    alert(er.stack);

}