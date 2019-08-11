
  function sendUpdate(id, tr_row) {
    var FD = new FormData();
    let deviceRow = tableToEdit.querySelector("#r" + id);
    let editables = deviceRow.querySelectorAll(".field-editable");
    let fields = {};
    fields.updatedevice = true;
    fields.device_id = id;
    FD.append("updatedevice", true);
    FD.append("device_id", id);
    for (let idx = 0; idx < editables.length; idx++) {
      val = editables[idx].innerText;
      if (!val) {
        val = editables[idx].value;
        val = !val ? "" : val; //replace nullish values with empty string
      }
      name = editables[idx].dataset.columnName;
      //datafield = new DataField(name, val);
      fields[name] = val;
      FD.append(name, val);
    }

    // update marker on map
    updateMarkerStatus(fields.device_status, fields)


    fetch(UpdateUrl, {
        method: 'POST', // *GET, POST, PUT, DELETE, etc.
        // mode: 'cors', // no-cors, cors, *same-origin
        // cache: 'no-cache', // *default, no-cache, reload, force-cache, only-if-cached
        credentials: 'include', // include, *same-origin, omit
        // headers: {
        //    // 'Content-Type': 'application/json',
        //      'Content-Type': 'application/x-www-form-urlencoded',
        // },
        // redirect: 'follow', // manual, *follow, error
        // referrer: 'no-referrer', // no-referrer, *client
        body: FD // body data type must match "Content-Type" header
      })
      .then(response => response.text())
      .then(t => {
        say(t);
      });; // parses JSON response into native JavaScript objects 

    console.log(fields)


    say(id);
  }


  let table = document.getElementById('tableToEdit');

  let editingTd;


  if (table) {
    table.onclick = function(event) {

      // 3 possible targets
      let target = event.target.closest('.edit-cancel,.edit-ok,td');

      if (!table.contains(target)) return;

      if (target.className == 'edit-cancel') {
        finishTdEdit(editingTd.elem, false);
      } else if (target.className == 'edit-ok') {
        finishTdEdit(editingTd.elem, true);
      } else if (
        target.nodeName === 'TD' && target.className === "field-editable" && !editingTd) { //  not already editing
        makeTdEditable(target);
      }

    };
  }

  function makeTdEditable(td) {
    editingTd = {
      elem: td,
      data: td.innerHTML
    };

    td.classList.add('edit-td'); // td is in edit state, CSS also styles the area inside

    let textArea = document.createElement('textarea');
    textArea.style.width = td.clientWidth + 'px';
    textArea.style.height = td.clientHeight + 'px';
    textArea.className = 'edit-area';

    textArea.value = td.innerText;
    td.innerHTML = '';
    td.appendChild(textArea);
    textArea.focus();

    td.insertAdjacentHTML("beforeEnd",
      '<div class="edit-controls"><button class="edit-ok">OK</button><button class="edit-cancel">CANCEL</button></div>'
    );
  }

  function finishTdEdit(td, isOk) {
    if (isOk) {
      td.innerHTML = td.firstChild.value;
    } else {
      td.innerHTML = editingTd.data;
    }
    td.classList.remove('edit-td');
    editingTd = null;
  }