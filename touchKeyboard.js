
//keyboard settings - default templates

const QWERTY = {
    // template: [
    //     "1234567890",
    //     "qwertyuiop",
    //     "asdfghjklE",
    //     "_zxcvbnm,.",
    // ],
    template: [
        "1234567890",
        "QWERTYUIOP",
        "ASDFGHJKLe",
        "_ZXCVBNM,d",
    ],
    //special keys definitions
    specKeys: {
        e: {
            //text to replace key letter
            text: "↵",
            //functionality of the key with current controller is not applicable (auto submit at the end)
            func(kbctrl) {
                return function (ev) {
                    kbctrl.finishInp()
                }
            }
        },
        d: {
            //text to replace key letter
            text: "⌫",
            //functionality of the key with current controller is not applicable (auto submit at the end)
            func(kbctrl) {
                return function (ev) {
                    kbctrl.del()
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
            text:"X",
            func(kbctrl) {
                return function (ev){
                    kbctrl.del()
                }   
            }
        },
        D: {
            text:"C",
            func(kbctrl) {
                return function (ev){
                kbctrl.clear();
                kbctrl.hide();
            }
        }
    },
    defaultFuncs: {
        inp(kbCtrl) {
            return function (e) {
                kbCtrl.inp(e);
            }
            }
        }
    }
}



/**
     * Pseudoclass quasi class let keywords allow each object have its own methods
     *
     * @param {string} pswd password
     * @returns kbCtrl keyboard Controller object
     */
    function CodeKeyboard(pswd,settingsTemplate = false) {
        let customKeyboard = {};
        if (pswd.length > 24) throw new Error("Password too long, will make display unreadable")
        if (!settingsTemplate){
            settingsTemplate = /^[0-9]+$/.test(pswd) ? NUM : QWERTY;
            //careful - if password will contain other signs, there is no 
            //proper settingTemplate
            //think about auto adding buttons for symbols that pswd contains
        }


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
            update() {
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
            clear(){
                this.inpBuf = "";
            },
            finishInp() {
                this.enternumber(this.inpBuf);
                this.clear();
                this.blockInp();
                setTimeout(x => {
                    this.toggle(0);
                    this.kbd.classList.remove("CorrectAnswer");
                    this.kbd.classList.remove("WrongAnswer");
                }, 1000);
            },
            enternumber(str) {
                console.log(str);
                if (this.pswd.toUpperCase() === str) {
                    this.kbd.classList.add("CorrectAnswer");
                    this.kbd.classList.remove("WrongAnswer");
                    this.onCorrectPassword(this,str);
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
        // customKeyboard = makeKBDHTML(QWERTY, KeyboardControllerClass);
        customKeyboard = makeKBDHTML(settingsTemplate, KeyboardControllerClass);
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
                                keyboardController.inp(e.target);
                            }, false);
                        }
                    } else {

                        keyHTML.addEventListener("click", function (e) {
                            keyboardController.inp(e.target);
                        }, false);

                    }
                    rowHTML.appendChild(keyHTML);
                });
                KBDHTML.appendChild(rowHTML);
            });

            // 	sK.forEach(spKey=>{

            // 		})
        }
        log(disp.colSpan);
        disp.colSpan = longestRowlength;

        newCustomKeyboard.KBDHTML = KBDHTML;
        newCustomKeyboard.disp = disp;
        newCustomKeyboard.ctrl = kb;
        keyboardController.setKbd(newCustomKeyboard);

        return newCustomKeyboard;
    }




    function fromTemplate(idorel) {
        if (typeof idorel === "string") {
            el = document.getElementById(idorel);
        } else {
            el = idorel;
        }
        var xtmpl = el.cloneNode(true);
        xtmpl.id = "";
        xtmpl.style.display = "";
        return xtmpl;
    }

    
