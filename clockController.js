
//clock controller quasi singleton module
/**
 * 
 * cx Module
 */
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
    // fonts: {
    //     _b: [],
    //     _i: 0,
    //     //next font circular
    //     n() {
    //         let i = this._i,
    //             b = this._b;
    //         i = i == b.length - 1 ? 0 : i + 1;
    //         this._i = i;
    //         //	console.log(b[i])
    //         return b[i];
    //     },
    //     c() {
    //         return this._b[this._i];
    //     }
    // },
    // nxfnt() {
    //     this.setFont(this.fonts.n());
    // },
    ct: counter,
    cts: counter_sec,
    ctm: counter_min,
    cth: counter_hour,
    ctte: counter_timeElapsed,
    tEnd: Date.now() + 60000, // default in minute this should come from server
    ctflashers: counter.querySelectorAll(".flash"),
    mBase: Date.now(),//mBase not used anymore
    ticklg: 1000, //refreshment rate
    tLim: 64000,
    sw: 0,
    isPaused: false,
    isRunning: false,
    timeElapsed: 0,
    IID: null,
    onFinished() { },
    onTick() { },

    // setFont(fntnm) {
    //     let o = this.ct.style;

    //     this.ct.style.fontFamily = fntnm;
    //     boominf.innerText = fntnm;
    //     this.ct.style.fontSize = "100vw";
    //     let a = this.ct.parentElement;
    //     let fs = o.fontSize,
    //         fsi = fs ? fs.replace("vw", "") : 100;
    //     while (a.scrollWidth > a.clientWidth + this.sw) {
    //         fsi = fsi - 1;
    //         o.fontSize = fsi + "vw";
    //     }
    //     o.fontSize = (fsi - 5) + "vw";
    // },
    showTime(h, m, s) {
        //extracting formatting function to var f
        // in this case formatting to 00
        let f = this.frm00.format;
        this.cts.innerText = f(s);
        this.ctm.innerText = f(m);
        this.cth.innerText = f(h);
        this.ctte.innerText = this.timeElapsed;
        //pause here 
    },

    /**
     * method
     *  starts the clock
     * TODO: should contact the server to sync up
     * @returns undefined
     */
    start() {
        if (this.isRunning) {
            return;
        }
        if (!this.isPaused) {
            this.mBase = Date.now();//mBase not used anymore
            //uwaga !!!
            //WTEDY PAUZUJE TYLKO WYSWIETLANIE!!!!
            //zaczyna ponownie ale dalej pamieta kiedy 
        }
        this.IID = window.setInterval(x => {
            this.tick();
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
        this.mBase = Date.now();//mBase not used anymore
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
    tick(){
        this.onTick(this);
        this.update();
    },
    update() {
        // old counting based on Limit
        // let te = this.tLim - (Date.now() - this.mBase);
        // new counting - deadline-based
        let te = this.tEnd - Date.now();
        this.timeElapsed = te;
        if (this.timeElapsed < this.ticklg) {
            this.showTime(0, 0, 0);
            for (let i = 0; i < this.ctflashers.length; i++) {
                this.ctflashers[i].classList.remove("flash");
            }
            this.stop();
            this.onFinished(this);
            return;
        }
        let d, h, m, s, r;
        h = ~~(Math.round(te / (3600 * 1000)));
        r = ~~(te % (3600 * 1000));
        m = ~~((r / (60 * 1000))); // was m = ~~(Math.round(r / (60 * 1000)));
        console.log({ te, r, m });
        r = ~~(r % (60 * 1000));
        s = ~~((r / 1000));

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
        this.tEnd = Date.now() + tl;
        this.showTime(~~h, ~~m, ~~s);
    },
    setTimestampEnd(timestampEnd) {
        this.tEnd = timestampEnd;
        this.update();
    },
    setTimestampEndfromString(strDate) {
        this.tEnd = Date.parse(strDate);
        this.update();
    },
    setTimeEndAndStart(timeEnd) {
        this.tEnd = timeEnd;
        this.start();
    }
};