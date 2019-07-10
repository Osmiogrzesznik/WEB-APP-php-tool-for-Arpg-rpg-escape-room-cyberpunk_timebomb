<div class="hud">
    <h2>Device Registration</h2>
    <style>
        
    </style>

    <form method="post" action="<?php echo $_SERVER['SCRIPT_NAME'] ?>?action=registerDevice" name="registerform">
        <label for="device_name">
            device_name:
        </label>
        <input id="device_name" type="text" pattern="[a-zA-Z0-9]{2,64}" name="device_name" required />
        <span class="validity">* required</span>
        <label for="device_description">
            device_description:
        </label>
        <input id="device_description" type="text" name="device_description" />
        <label for="device_password_new">
            Device Password (4-24 characters, a-z 0-9) stops/unlocks the device
        </label>
        <input id="device_password_new" class="login_input" type="password" name="device_password_new" pattern="[a-z0-9]{4,32}" required autocomplete="off" />
        <span class="validity">* required </span>
        <label for="device_password_repeat">
            Repeat password
        </label>
        <input id="device_password_repeat" class="login_input" type="password" name="device_password_repeat" pattern="[a-z0-9]{4,32}" required autocomplete="off" />
        <span class="validity">* required</span>
        <label for="device_ip">
            Device IP(default this one):
        </label>
        <input id="device_ip" type="text" name="device_ip" title="IP address of device" required autocomplete="off" <?php echo 'value=' . $this->getIP(DEBUG_MODE) . '' ?> />
        <span class="validity">* required</span>
        <label for="time_set">
            time_set(this is the time device counts down to):
        </label>
        <input 
        id="time_set" 
        class="time_set" 
        type="datetime-local" 
        name="time_set" 
        pattern="[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}"
        <?php
$datenow = date('Y-m-d\TH:i:s', time()+60*30);
echo 'min="' . $datenow . '" value="' . $datenow . '"' ?> required>
                <span class="validity"></span>

        <input type="submit" name="register" value="Register" />
        <div id="counterCNT" class="counterCNT">
            <div id="counterMeas" class="counter">
                <span id="counter" class="digits">

                    <span id="counter_hour">00</span>
                    <span id="counter_colon1">:</span>
                    <span id="counter_min">00</span>
                    <span id="counter_colon2" class="flash">:</span>
                    <span id="counter_sec">00</span>
                </span>
            </div>
    </form>


</div>

</div>
Minimal Date ( NOW ):
<input id="time_setMIN" style="display:block" type="datetime-local" <?php
                                                                    $datenow = date('Y-m-d\TH:i', time());
                                                                    echo 'value="' . $datenow . '"' ?> />
<input id="time_setMINtext" style="display:block" type="text" <?php
                                                                $datenow = date('Y-m-d\TH:i', time());
                                                                echo 'value="' . $datenow . '"' ?> />

<a href="<?php echo $_SERVER['SCRIPT_NAME'] ?>">Homepage</a>

</div>
<link rel="stylesheet" href="flatpickr.css">
<script type="text/javascript" src="flatpickr.js"></script>
<script type="text/javascript" src="clockController.js"></script>
<script type="text/javascript" src="touchKeyboard.js"></script>
<!-- <script type="text/javascript" src="timebomb.js"></script> -->
<script>
    stngs = {
        enableTime: true,
        enableSeconds: true,
        dateFormat: "Y-m-d\TH:i:S",
        allowInput: true,
        //defaultDate: new Date()
    }



    time_setsNL = document.querySelectorAll(".time_set");
    cxs = [];
    counters = [];
    for (let idx = 0; idx < time_setsNL.length; idx++) {
        let time_set = time_setsNL[idx];
        let counter = time_set.parentElement.querySelector(".digits");
        counters.push(counter);
        let cx = new ClockController(counter);
        cxs.push(cx);
        cx.setTimestampEndfromString(time_set.value);
        cx.start();
        time_set.addEventListener('change', (ev) => {
            cx.reset();
            //alert(event.target.value);
            cx.setTimestampEndfromString(ev.target.value);
            cx.start();
        }, false)
    }

    
    


    function pad(number) {
        if (number < 10) {
            return '0' + number;
        }
        return number;
    }

    function toISOStrCut(date) {
        return date.getUTCFullYear() +
            '-' + pad(date.getUTCMonth() + 1) +
            '-' + pad(date.getUTCDate()) +
            'T' + pad(date.getUTCHours()) +
            ':' + pad(date.getUTCMinutes()) +
            ':' + pad(date.getUTCSeconds())
    };


    options = {

    }


    fps = flatpickr(time_setsNL, stngs);

    setInterval(x => {
        //to nie dziala
        timeZoneOffset = new Date().getTimezoneOffset() * 60000;
        let d = Date.now() - timeZoneOffset;
        let dstr = toISOStrCut(new Date(d));

        //time_set.setAttribute('valueAsNumber',d.getTime()); 
        fps.forEach(fp => {
            fp.config.minDate = dstr;
        })
        // time_setMIN.valueAsNumber = ~~(d / 60000) * 60000;
        // time_setMINtext.value = dstr;
        // time_set.min = dstr;
    }, 1000);

    
</script>
</body>

</html>