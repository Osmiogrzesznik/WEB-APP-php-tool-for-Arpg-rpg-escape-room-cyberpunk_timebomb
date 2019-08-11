
<script>

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
      //say(event.target.value);
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



  stngs = {
    minuteIncrement: 1,
    enableTime: true,
    enableSeconds: true,
    dateFormat: "Y-m-d\TH:i:S",
    allowInput: true,
    appendTo: tableWrapper
    //defaultDate: new Date()
  }

  //if there is many counters fps would be an array 
  //otherwise (when user didn't add any devices yet) its one flatpicker
  fps = flatpickr(time_setsNL, stngs);
  is_many_flatpickers = fps.hasOwnProperty("length");
  units = [
    'year',
    'month',
    'week',
    'day',
    'hour',
    'minute',
    'second'
  ];

  //prepare for refreshing here to not burden cpu
  time_last_active_tds = document.querySelectorAll(".time_last_active");
  tlaObjs = [];
  for (let i = 0; i < time_last_active_tds.length; i++) {
    let ob = {};
    ob.td = time_last_active_tds[i];
    ob.datesrv = ob.td.querySelector(".my_date_format").innerText;
    ob.agospan = ob.td.querySelector(".ago");
    tlaObjs.push(ob);
  }
  //     time_last_active.forEach(te=>{
  //         datasetvalues = units.map(unitname=>{
  //             ob={};
  //             ob[unitname]=te.dataset[unitname];
  //             return ob;
  //             });//object with propertiies names and values
  //         console.log(datasetvalues);

  // });
  //this interval is only for minimum and displaying ago's so can be refreshed each 5s
  setInterval(x => {

    timeZoneOffset = new Date().getTimezoneOffset() * 60000;
    timestampNOWwOffs = Date.now() - timeZoneOffset;
    dateNOWwOffs = new Date(timestampNOWwOffs);
    let dstr = toISOStrCut(dateNOWwOffs);
    //refresh time ago's
    tlaObjs.forEach(ob => {
      ob.agospan.innerText = time_ago(ob.datesrv);
    })
    //if there is many counters fps would be an array 
    //otherwise (when user didn't add any devices yet) its one flatpicker
    if (is_many_flatpickers) {
      fps.forEach(fp => {
      fp.config.minDate = dstr;
      })
    } else {
      fps.config.minDate = dstr;
    }
    // time_setMIN.valueAsNumber = ~~(timestampNOWwOffs / 60000) * 60000;
    // time_setMINtext.value = dstr;
    // time_set.min = dstr;
  }, 5000);

  function time_ago(time) {

    switch (typeof time) {
      case 'number':
        break;
      case 'string':
        time = +new Date(time);
        break;
      case 'object':
        if (time.constructor === Date) time = time.getTime();
        break;
      default:
        time = +new Date();
    }
    var time_formats = [
      [60, 'seconds', 1], // 60
      [120, '1 minute ago', '1 minute from now'], // 60*2
      [3600, 'minutes', 60], // 60*60, 60
      [7200, '1 hour ago', '1 hour from now'], // 60*60*2
      [86400, 'hours', 3600], // 60*60*24, 60*60
      [172800, 'Yesterday', 'Tomorrow'], // 60*60*24*2
      [604800, 'days', 86400], // 60*60*24*7, 60*60*24
      [1209600, 'Last week', 'Next week'], // 60*60*24*7*4*2
      [2419200, 'weeks', 604800], // 60*60*24*7*4, 60*60*24*7
      [4838400, 'Last month', 'Next month'], // 60*60*24*7*4*2
      [29030400, 'months', 2419200], // 60*60*24*7*4*12, 60*60*24*7*4
      [58060800, 'Last year', 'Next year'], // 60*60*24*7*4*12*2
      [2903040000, 'years', 29030400], // 60*60*24*7*4*12*100, 60*60*24*7*4*12
      [5806080000, 'Last century', 'Next century'], // 60*60*24*7*4*12*100*2
      [58060800000, 'centuries', 2903040000] // 60*60*24*7*4*12*100*20, 60*60*24*7*4*12*100
    ];

    var seconds = ~~(+new Date() - time) / 1000,
      token = 'ago',
      list_choice = 1;

    if (seconds == 0) {
      return 'Just now'
    }
    if (seconds < 0) {
      seconds = Math.abs(seconds);
      token = 'from now';
      list_choice = 2;
    }
    var i = 0,
      format;
    while (format = time_formats[i++])
      if (seconds < format[0]) {
        if (typeof format[2] == 'string')
          return format[list_choice];
        else
          return ~~(seconds / format[2]) + ' ' + format[1] + ' ' + token;
      }
    return time;
  }

  say("main CP scripts ok")
</script>
</body>

</html>