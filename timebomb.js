
// debug tools
log = x => console.log(x);
jlog = x => console.log(JSON.stringify(x, null, 2));

//keyboard templates

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
        n() {
            let i = this._i,
                b = this._b;
            i = i == b.length - 1 ? 0 : i + 1;
            this._i = i;
            //	console.log(b[i])
            return b[i];
        },
        c() {
            return this._b[this._i]
        }
    },
    nxfnt() {
        this.setFont(this.fonts.n());
    },
    ct: counter,
    mBase: Date.now(),
    ticklg: 1000, //refreshment rate
    tLim: 64000,
    sw: 0,
    isPaused: false,
    isRunning: false,
    timeElapsed: 0,
    onFinished() { },
    IID: null,
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
    onTick() { },
    showTime(h, m, s) {
        let st = "",
            //extracting formatting function to var f
            // in this case formatting to 00
            f = this.frm00.format;
        this.ct.innerText = "" + f(h) + ":" + f(m) + ":" + f(s)
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
            this.update()
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
        this.stop()
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
        tl = s * 1000
        tl += m * 60 * 1000
        tl += h * 3600 * 1000
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
        s = document.createElement("style")
        fls.forEach((f, i) => {
            s.innerText += templ.replace("XXXNAME", nms[i]).replace("XXXFILE", f);
        });
        document.body.insertAdjacentElement("afterbegin", s);
    })

function CodeKeyboard(pswd) {
    let customKeyboard = {}
    let update = function () {
        let nd = this.disp,
            ib = this.inpBuf,
            ndl = this.disp.innerText.length,
            ibl = this.inpBuf.length;;
        nd.innerText = this.inpBuf + "_".repeat(ndl - ibl)
    }

    let resetDisplay = function () {
        this.kbd.innerText = "_".repeat(this.pswd.length);
    }

    let kbCtrl = {
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
        update: update,
        hide() {
            this.kbds.display = "none";
            this.resetDisplay();
            this.inp = this.inpOff;
        },
        show() {
            this.kbds.display = "";
            this.inp = this.inpOn;
        },
        toggle() {
            //log("toggling")
            this.resetDisplay();
            let nk = this.kbds;
            let d = nk.display;
            nk.display =
                d == "none" ?
                    "" : "none";
            this.inp =
                d == "none" ?
                    this.inpOn : this.inpOff;
        },
        del() {
            this.inpBuf = this.inpBuf.slice(0, this.inpBuf.length - 1);
            this.update();
            //                 if (this.inpBuf.length == 0) {
            //                     this.finishInp()
            //                 }
        },
        inpOn(btn) {
            this.inpBuf = this.inpBuf + btn.innerText;
            this.update();
            if (this.inpBuf.length == this.pswd.length) {
                this.finishInp()
            }
        },
        inpOff() { },
        inp() {
            log("inp not turned on yet!")
        },
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
            console.log(str)
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
            this.inp = this.inpOff;
        }

    }


    customKeyboard = makeKBDHTML(QWERTY, kbCtrl);
    document.body.appendChild(customKeyboard.KBDHTML);
    customKeyboard.KBDHTML.style.display = "";




    return kbCtrl;
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


function makeKBDHTML(stg, kbCtrl) {

    let kb = stg.defaultFuncs;
    let newCustomKeyboard = {
        ctrl: kb
    }
    let sK = Object.keys(stg.specKeys),
        ts = stg.template;

    if (!ts) {
        throw new Error("makeKBDHTML has been given settings without keyboardSettings.template property");
    }
    let KBDHTML = fromTemplate(customKeybTMPLT);
    let disp = KBDHTML.querySelector('.KBdisplay');
    let longestRowlength = 0;
    if (!stg.displayHidden) {
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
                    let sKey = stg.specKeys[key];
                    log(sKey);
                    keyHTML.innerText = sKey.text ? sKey.text : key;
                    if (sKey.func) {
                        keyHTML.addEventListener("click", sKey.func(kbCtrl), false);
                    } else {
                        keyHTML.addEventListener("click", function (e) {
                            kbCtrl.inp(e.target)
                        }, false);
                    }
                } else {

                    keyHTML.addEventListener("click", function (e) {
                        kbCtrl.inp(e.target)
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
    kbCtrl.setKbd(newCustomKeyboard)

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
    kb.hide();
    setTimeout(x => kb.show(), 500)

}

counterCNT.addEventListener("click", test, false)
kb.onCorrectPassword = x => {
    cx.stop()
}