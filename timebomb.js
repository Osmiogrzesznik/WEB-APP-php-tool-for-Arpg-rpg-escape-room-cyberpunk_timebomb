
addEventListener("click", function() { var el = document.documentElement , rfs = el.requestFullScreen || el.webkitRequestFullScreen || el.mozRequestFullScreen ; rfs.call(el); });





// debug tools
log = x => console.log(x);
jlog = x => console.log(JSON.stringify(x, null, 2));

//keyboard settings - default templates

const QWERTY = {
    template: [
        "1234567890",
        "qwertyuiop",
        "asdfghjklE",
        "_zxcvbnm,.",
    ],
    //special keys definitions
    specKeys: {
        E: {
            //text to replace key letter
            text: "Enter",
            //functionality of the key
            func(kbctrl) {
                return function (e) {
                    kbctrl.finishInp()
                }
            }
        }
    },
    //default keyboard behaviours, attached to the keyboard object
    defaultFuncs: {
        inp(e) {
            this.inp(e);
        }
    }
}

const NUM = {
    template: [
        "789",
        "456",
        "123",
        "X0D"
    ],
    specKeys: {
        X: {
            func() {
                kb.del()
            }
        },
        D: {
            func() {
                kb.hide()
            }
        }
    },
    defaultFuncs: {
        inp(kbCtrl) {
            return function (e) {
                kbCtrl.inp(e)
            }
        }
    }
}



//clock controller
cx = {
    s: 0,
    m: 0,
    h: 0,
    d: 0,
    frm00: new Intl.NumberFormat(undefined, {
        minimumIntegerDigits: 2,
        maximumFractionDigits: 0
    }),
    frm0: new Intl.NumberFormat(undefined, {
        minimumIntegerDigits: 1,
        maximumFractionDigits: 0
    }),
    fonts: {
        _b: [],
        _i: 0,
        //next font circular
        n() {
            let i = this._i,
                b = this._b;
            i = i == b.length - 1 ? 0 : i + 1;
            this._i = i;
            //	console.log(b[i])
            return b[i];
        },
        c() {
            return this._b[this._i];
        }
    },
    nxfnt() {
        this.setFont(this.fonts.n());
    },
    ct: counter,
    cts: counter_sec,
    ctm: counter_min,
    cth: counter_hour,
    ctflashers:counter.querySelectorAll(".flash"),
    mBase: Date.now(),
    ticklg: 1000, //refreshment rate
    tLim: 64000,
    sw: 0,
    isPaused: false,
    isRunning: false,
    timeElapsed: 0,
    IID: null,
   onFinished() { },
   onTick() { },

    setFont(fntnm) {
        let o = this.ct.style;

        this.ct.style.fontFamily = fntnm;
        boominf.innerText = fntnm;
        this.ct.style.fontSize = "100vw";
        let a = this.ct.parentElement;
        let fs = o.fontSize,
            fsi = fs ? fs.replace("vw", "") : 100;
        while (a.scrollWidth > a.clientWidth + this.sw) {
            fsi = fsi - 1;
            o.fontSize = fsi + "vw";
        }
        o.fontSize = (fsi - 5) + "vw";
    },
    showTime(h, m, s) {
            //extracting formatting function to var f
            // in this case formatting to 00
      let f = this.frm00.format;
        this.cts.innerText = f(s);
        this.ctm.innerText = f(m);
        this.cth.innerText = f(h);
    },

    /**
     *
     *  starts the clock
     * TODO: should contact the server to sync up
     * @returns undefined
     */
    start() {
        if (this.isRunning) {
            return;
        }
        if (!this.isPaused) {
            this.mBase = Date.now();
            //uwaga !!!
            //WTEDY PAUZUJE TYLKO WYSWIETLANIE!!!!
            //zaczyna ponownie ale dalej pamieta kiedy 
        }
        this.IID = window.setInterval(x => {
            this.update();
        }, this.ticklg);
        
        this.isRunning = true;
        this.update();
    },
    stop() {
        if (!this.isRunning) {
            return;
        }
        window.clearInterval(this.IID);
        this.isRunning = false;
    },
    reset() {
        if (this.isRunning) {
            this.stop();
        }
        this.mBase = Date.now();
        this.isPaused = false;
        this.isRunning = false;
    },
    pause() {
        if (!this.isRunning || this.isPaused) {
            return;
        }
        this.stop();
        this.isPaused = true;
        window.clearInterval(this.IID);
        this.isRunning = false;
    },
    update() {
        this.onTick(this);
        let te = this.tLim - (Date.now() - this.mBase);
        this.timeElapsed = te;
        if (this.timeElapsed < this.ticklg) {
            this.showTime(0, 0, 0);
            for (let i=0;i<this.ctflashers.length;i++){
                this.ctflashers[i].classList.remove("flash");
            }
            this.stop();
            this.onFinished(this);
            return;
        }
        let d, h, m, s, r;
        h = ~~(Math.round(te / (3600 * 1000)));
        r = ~~(te % (3600 * 1000));
        m = ~~(Math.round(r / (60 * 1000)));
        r = ~~(r % (60 * 1000));
        s = ~~(Math.round(r / 1000));

        this.s = Math.abs(s);
        this.m = Math.abs(m);
        this.h = Math.abs(h);
        this.showTime(this.h, this.m, this.s);
    },
    setLimit(h = 0, m = 0, s = 0) {
        let tl = 0;
        tl = s * 1000;
        tl += m * 60 * 1000;
        tl += h * 3600 * 1000;
        tl = ~~(tl);
        this.tLim = tl;
        this.showTime(~~h, ~~m, ~~s);
    },
    setLimitOnDate(dt) {

    }
}
cx.setLimit(0, 0, 40);
cx.onFinished = function (c) {
    boominf.innerText = "Boom!"
}


// this should be invoked by info from server
// admin webpage should allow to set exact starttime of each timebomb and pass this info
// to all clients based on their ID
// EASY - all clients visiting website have the same time- 
//     if somebody refreshes page it should automatically 
//     ajax/fetch right time to start if time to start has passed , 
//     it should automatically adjust itself so its synchronised with server
//     right remaining time is on the clock. 

cx.start();

// DOESNT WORK W/OUT SERVER!!!!
//    automatically create font imports in stylesheet
//    based on the allFonts.txt file contents

templ = "@font-face { font-family: \"XXXNAME\";src: url(\"fonts/XXXFILE\") format(\"truetype\"); }"
fetch("allFonts.txt").then(x => x.text())
    .then(x => {
        fls = x.trim().split("\n");
        nms = fls.map(x => x.replace(".ttf", "").replace(".TTF", ""));
        cx.fonts._b = nms;
        s = document.createElement("style");
        fls.forEach((f, i) => {
            s.innerText += templ.replace("XXXNAME", nms[i]).replace("XXXFILE", f);
        });
        document.body.insertAdjacentElement("afterbegin", s);
    })

/**
 * Pseudoclass quasi class let keywords allow each object have its own methods
 *
 * @param {string} pswd password
 * @returns kbCtrl keyboard Controller object
 */
function CodeKeyboard(pswd) {
    let customKeyboard = {}

    

    // resets Display filling it with underscores depending on the passwrod length
    let resetDisplay = function () {
        this.kbd.innerText = "_".repeat(this.pswd.length);
    }

    // keyboard controller
    let KeyboardControllerClass = {
        setKbd(customKeyboard) {
            this.kbd = customKeyboard.KBDHTML;
            this.kbds = customKeyboard.KBDHTML.style;
            this.disp = customKeyboard.disp;
        },
        pswd: pswd,
        kbd: null,
        kbds: null,
        numDisp: null,
        inpBuf: "",
        resetDisplay() {
            this.disp.innerText = "_".repeat(this.pswd.length);
        },
        update () {
            let nd = this.disp,
                ib = this.inpBuf,
                ndl = this.disp.innerText.length,
                ibl = this.inpBuf.length;;
            nd.innerText = this.inpBuf + "_".repeat(ndl - ibl)
        },
        hide() {
            this.kbds.display = "none";
            this.resetDisplay();
            this.inp = this.inpfuncWhenOff;
        },
        show() {
            this.kbds.display = "";
            this.inp = this.inpfuncWhenOn;
        },
        toggle() {
            //log("toggling")
            this.resetDisplay();
            let nk = this.kbds;
            let d = nk.display;
            nk.display =
                d == "none" ?
                    "" : "none";
            //set input function accordingly
            this.inp =
                d == "none" ?
                    this.inpfuncWhenOn : this.inpfuncWhenOff;
        },
        del() {
            this.inpBuf = this.inpBuf.slice(0, this.inpBuf.length - 1);
            this.update();
            //                 if (this.inpBuf.length == 0) {
            //                     this.finishInp()
            //                 }
        },
        inpfuncWhenOn(btn) {
            this.inpBuf = this.inpBuf + btn.innerText;
            this.update();
            if (this.inpBuf.length == this.pswd.length) {
                this.finishInp();
            }
        },
        inpfuncWhenOff() { },
        inp() {
            //this will be replaced by funcs inpfuncWhenOn or inpfuncWhenOff depending on the state 
            //state includes visibility of keyboard
            log("this is default nonfunction inp not turned on yet!")
        },
        // these are to be replaced by user
        onCorrectPassword() { },
        onIncorrectPassword() { },
        finishInp() {
            this.enternumber(this.inpBuf);
            this.inpBuf = "";
            this.blockInp();
            setTimeout(x => {
                this.toggle(0);
                this.kbd.classList.remove("CorrectAnswer");
                this.kbd.classList.remove("WrongAnswer");
            }, 1000);
        },
        enternumber(str) {
            console.log(str);
            if (this.pswd === str) {
                this.kbd.classList.add("CorrectAnswer");
                this.kbd.classList.remove("WrongAnswer");
                this.onCorrectPassword(this);
            } else {
                this.kbd.classList.add("WrongAnswer");
                this.kbd.classList.remove("CorrectAnswer");
                this.onIncorrectPassword(this);
            }
        },
        blockInp() {
            this.inp = this.inpfuncWhenOff;
        }
    }
    customKeyboard = makeKBDHTML(QWERTY, KeyboardControllerClass);
    document.body.appendChild(customKeyboard.KBDHTML);
    customKeyboard.KBDHTML.style.display = "";
    return KeyboardControllerClass;
}





// 				function asd(num){

// 				let	ret={
// 						a:num
// 					}
// 					return ret;
// 				}

// 				a=asd(1)
// 				log(a.a)
// 				b=asd(2)
// 				log(b.a)
// 				log(a.a)


function makeKBDHTML(keyboardSettings, keyboardController) {

    let kb = keyboardSettings.defaultFuncs;
    let newCustomKeyboard = {
        ctrl: kb
    };
    
    let sK = Object.keys(keyboardSettings.specKeys),
        ts = keyboardSettings.template;

    if (!ts) {
        throw new Error("makeKBDHTML has been given settings without keyboardSettings.template property");
    }
    let KBDHTML = fromTemplate(customKeybTMPLT);
    let disp = KBDHTML.querySelector('.KBdisplay');
    let longestRowlength = 0;
    if (!keyboardSettings.displayHidden) {
        disp.style.display = "";
    }

    if (ts instanceof Array) {
        ts.forEach(kRow => {
            let rowHTML = document.createElement("tr"); //fromTemplate("rowTMPLT");
            let kRowLetters = kRow.split("");
            longestRowlength = longestRowlength < kRowLetters.length ?
                kRowLetters.length : longestRowlength;
            kRowLetters.forEach(key => {
                let keyHTML = document.createElement("td"); //fromTemplate(keyTMPLT);
                keyHTML.innerText = key;
                if (sK.includes(key)) {
                    log(sK.includes(key));
                    let sKey = keyboardSettings.specKeys[key];
                    log(sKey);
                    keyHTML.innerText = sKey.text ? sKey.text : key;
                    if (sKey.func) {
                        keyHTML.addEventListener("click", sKey.func(keyboardController), false);
                    } else {
                        keyHTML.addEventListener("click", function (e) {
                            keyboardController.inp(e.target)
                        }, false);
                    }
                } else {

                    keyHTML.addEventListener("click", function (e) {
                        keyboardController.inp(e.target)
                    }, false);

                }
                rowHTML.appendChild(keyHTML);
            });
            KBDHTML.appendChild(rowHTML);
        });

        // 	sK.forEach(spKey=>{

        // 		})
    }
    log(disp.colSpan)
    disp.colSpan = longestRowlength;

    newCustomKeyboard.KBDHTML = KBDHTML;
    newCustomKeyboard.disp = disp;
    newCustomKeyboard.ctrl = kb;
    keyboardController.setKbd(newCustomKeyboard)

    return newCustomKeyboard
}




function fromTemplate(idorel) {
    if (typeof idorel === "string") {
        el = document.getElementById(idorel)
    } else {
        el = idorel
    }
    var xtmpl = el.cloneNode(true);
    xtmpl.id = "";
    xtmpl.style.display = "";
    return xtmpl;
}


//make keyboard with 1234 as a pswd
kb = CodeKeyboard("1234");
kb.hide()




function numKbdHandler(e) {
    kb.inp(e.target);
}

function keyboff() {
    kb.hide()
}

function dellast() {
    kb.del();
}

function test() {
    // kb.kbds.display="none";
    //kb.kbds.display="initial";
   //€ alert(1);
    kb.toggle();
    //setTimeout(x => kb.show(), 500)

}

counterCNT.addEventListener("click", test, false)
kb.onCorrectPassword = x => {
    cx.stop()
}