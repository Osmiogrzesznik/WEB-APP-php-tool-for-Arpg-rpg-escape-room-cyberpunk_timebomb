

const SILENT_MODE = false;


// Fullscreen - first user interaction - so set up sounds as well
addEventListener("click", function () {
    var el = document.documentElement, rfs = el.requestFullScreen || el.webkitRequestFullScreen || el.mozRequestFullScreen; rfs.call(el);


    counterCNT.addEventListener("click", toggleKeyboard, false);//after fullscreening clicking again shows keyboard

    if (!SILENT_MODE) {
        cx.onTick = function (cxthis) {
            try {
                ticksound.play();
            } catch (er) {
                alert(er.stack);
            }
        }
        kb.onIncorrectPassword = x => {
            wrongSound.play();
        }
    }

});


// debug tools
log = x => console.log(x);
jlog = x => console.log(JSON.stringify(x, null, 2));

// this should be invoked by info from server
// admin webpage should allow to set exact starttime of each timebomb and pass this info
// to all clients based on their ID
// EASY - all clients visiting website have the same time- 
//     if somebody refreshes page it should automatically 
//     ajax/fetch right time to start if time to start has passed , 
//     it should automatically adjust itself so its synchronised with server
//     right remaining time is on the clock. 


// DOESNT WORK W/OUT SERVER!!!!
//    automatically create font imports in stylesheet
//    based on the allFonts.txt file contents


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


if (jsdataFromServer.error){
    alert(jsdataFromServer.error)
    device_password = "1234";
    time_set = Date.now() + 30000;
}
else{
    device_password = jsdataFromServer.device_password;
    time_set = jsdataFromServer.time_set;
}

kb = CodeKeyboard(device_password);//easily hackable



kb.onCorrectPassword = (kbctrl, enteredpassword) => {
    cx.stop();
    //send enteredpassword to server , there is vulnerability here as password should be not stored in javascript objects, user can easily retrieve it with devtools
}

//make keyboard with 1234 as a pswd

// explosion effect on Finished
cx.onFinished = function (c) {
    document.body.classList.add("transitionable");
    setTimeout(x => {
        document.body.classList.add("WrongAnswer");
        document.body.classList.add("boom");
        setTimeout(x => {
            document.body.classList.remove("boom")
            document.body.innerHTML = "";
        }, 1000);
    }, 1000);

};








// THis should be in separate file to allow using some parts of code to visualise when admin sets it up
//Everything ready , start
kb.hide();
//cx.setLimit(0, 0, 6);
cx.setTimestampEnd(time_set);// this sets the countdown timer to 40 seconds 
cx.start();
