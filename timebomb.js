try {
    const SILENT_MODE = false;

    cx = new ClockController(counter, counter_sec, counter_min, counter_hour);

    // Fullscreen - first user interaction - so set up sounds as well
    addEventListener("click", function () {
        var el = document.documentElement,
            rfs = el.requestFullScreen || el.webkitRequestFullScreen || el.mozRequestFullScreen;
        rfs.call(el);
        counterCNT.addEventListener("click", toggleKeyboard, false); //after fullscreening clicking again shows keyboard

        if (!SILENT_MODE) {
            cx.onTick = function (cxthis) {
                ticksound.play();
            }
            kb.onIncorrectPassword = kbctrl => {
                wrongSound.play();
            }
        }
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


    //alert("start");

    fetch("index.php?action=getsettings")
        .then(x => x.json())
        // .then(x=>x.json())
        .then(json => {
            startBombWithData(json)
        })
        .catch(x => alert(x.stack));


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
        if (data.device_status === "disarmed") {
            //alert("this device was already disarmed")
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

            fetch("index.php?action=password&password=" + psswd)
                .then(response => response.json())
                .then(json => {
                    ////alert(JSON.stringify(json));
                    feedbackPRE = document.body.querySelector("#feedback");
                    feedbackPRE.innerText = json.feedback;
                    document.body.insertAdjacentHTML("beforebegin", json.feedback)
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
                });
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

} catch (er) {
    alert(er.stack);

}


delete jsDataFromServer;