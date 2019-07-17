<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="stylesheet" href="halfdigi.css">
</head>
<body><script>
window.onerror = function (event){
	say("Blad kompilu js: "+JSON.stringify(event,null,2) +": "+event);
	}


    function feedbackModalConfirmation(clickevent){
    modalContainer.removeEventListener('click',feedbackModalConfirmation);
    feedback.classList.remove("feedback-pre-console");
    //feedbackContainer.classList.remove("feedback-pre-console");
    modalContainer.classList.remove("modal-container-on");
}

function say(msg,keephidden=false){
  feedback.innerText += "\n" + msg;
  if (keephidden){
      return;
  }
  
  //feedbackContainer.classList.add("feedback-pre-console");
  feedback.classList.add("feedback-pre-console");
  modalContainer.classList.add("modal-container-on");
    modalContainer.addEventListener('click',feedbackModalConfirmation);
}

</script>
	
<a href="<?php echo $_SERVER['SCRIPT_NAME'] ?>">Homepage</a>

<div id="modalContainer" class="modal-container">
    
    <div id="feedbackContainer">
    <pre id="feedback">    
<?php
if ($this->feedback) {          
            echo "FEEDBACK : \n" . $this->feedback ;
}?>
</pre>
<div class="flex-row">
<button>OK</button>
<button onclick="feedback.innerText=[];">Clear</button>
</div>
</div>
</div>
<button onclick="say('')">Show feedback</button>
<br/>


