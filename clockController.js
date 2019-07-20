
//clock controller quasi singleton module
/**
 * 
 * cx Module
 */
function ClockController(counterEl,counter_sec,counter_min,counter_hour){
    if (!counterEl){
        throw new Error("target HTML element required to create clock")
    }
    this.noop = function (){};
    /**
     * number formatter for clock display
     */
    this.frm00 = new Intl.NumberFormat(undefined, {
        minimumIntegerDigits: 2,
        maximumFractionDigits: 0
    });

    this.ct = counterEl;
    //if any component missing set it up ourselves
    if (!(counter_sec && counter_min && counter_hour)){
        this.ct.innerHTML = "";
        this.cts = document.createElement("span"); 
        this.ctm = document.createElement("span"); 
        this.cth = document.createElement("span");
        this.cl1 = document.createElement("span"); 
        this.cl2 = document.createElement("span");
        this.cl1.innerText = ":";
        this.cl2.innerText = ":";
        this.cts.innerText = "00"; 
        this.ctm.innerText = "00"; 
        this.cth.innerText = "00";
        [this.cth,this.cl1,this.ctm,this.cl2,this.cts]
        .forEach(span=>this.ct.append(span));
    }else{
    this.cts = counter_sec,
    this.ctm = counter_min,
    this.cth = counter_hour;
    }

    this.s = 0,
    this.m = 0,
    this.h = 0,
    this.d = 0,
    this.tEnd = Date.now() + 60000, // default in minute - but this should come from server
    this.ctflashers = counter.querySelectorAll(".flash"),
    this.ticklg = 1000, //refreshment rate
    this.tLim = 64000, // what is this ? Limit ?
    this.sw = 0, //?
    this.isPaused = false,
    this.isRunning = false,
    this.remainingTime = 0, // time remaining in miliseconds
    this.IID = null, // ID for stopping of the function ran in interval 
    /**
     * replaceable listeners  - user customizable
     */
    this.onFinished = this.noop;
    this.onTick = this.noop;
    /**
     * method displaying the formatted time in provided div
     * @param h hours
     * @param m minutes
     * @param s seconds
     */
    this.showTime = function(h, m, s) {
        //extracting formatting function to var f
        // in this case formatting to 00
        let f = this.frm00.format;
        this.cts.innerText = f(s);
        this.ctm.innerText = f(m);
        this.cth.innerText = f(h);
        //this.ctte.innerText = this.remainingTime;
        //pause here 
    };
   /**
     * method
     *  starts the clock
     * TODO: should contact the server to sync up ?
     */
    this.start = function() {
        //dont do nothing if its already running
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
    };
    this.onStop = this.noop;
    this.stop = function() {
        if (!this.isRunning) {
            return;
        }
        window.clearInterval(this.IID);
        this.isRunning = false;
        this.onStop(this);
    },
    this.reset = function() {
        if (this.isRunning) {
            this.stop();
        }
        this.mBase = Date.now();//mBase not used anymore
        this.isPaused = false;
        this.isRunning = false;
    },
    this.pause = function() {
        if (!this.isRunning || this.isPaused) {
            return;
        }
        this.stop();
        this.isPaused = true;
        window.clearInterval(this.IID);
        this.isRunning = false;
    },
    this.tick = function(){
        this.onTick(this);
        this.update();
    },
    this.update = function() {
        // old counting based on Limit
        // let rT = this.tLim - (Date.now() - this.mBase);
        // new counting - deadline-based
        let rT = this.tEnd - Date.now();
        this.remainingTime = rT;
        if (this.remainingTime < this.ticklg) {
            this.showTime(0, 0, 0);
            for (let i = 0; i < this.ctflashers.length; i++) {
                this.ctflashers[i].classList.remove("flash");
            }
            this.stop();
            this.onFinished(this);
            return;
        }
        let d, h, m, s, r;
        //never use math round here it rounds hours every time when there is more than 30 minutes
        h = ~~((rT / (3600 * 1000)));
        r = ~~(rT % (3600 * 1000));
        m = ~~((r / (60 * 1000))); // was m = ~~(Math.round(r / (60 * 1000)));
        r = ~~(r % (60 * 1000));
        s = ~~((r / 1000));
        // console.log({ rT, r, m });
        this.s = Math.abs(s);
        this.m = Math.abs(m);
        this.h = Math.abs(h);
        this.showTime(this.h, this.m, this.s);
    },
    this.setLimit = function (h = 0, m = 0, s = 0) {
        let tl = 0;
        tl = s * 1000;
        tl += m * 60 * 1000;
        tl += h * 3600 * 1000;
        tl = ~~(tl);
        this.tEnd = Date.now() + tl;
        this.showTime(~~h, ~~m, ~~s);
    },
    this.setTimestampEnd = function (timestampEnd) {
        this.tEnd = timestampEnd;
        this.update();
    },
    this.setTimestampEndfromString = function(strDate) {
        this.tEnd = Date.parse(strDate);
        this.update();
    },
    this.setTimeEndAndStart = function(timeEnd) {
        this.tEnd = timeEnd;
        this.start();
    }
    
};
    
    //ctte: counter_remainingTime,
    
    

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




