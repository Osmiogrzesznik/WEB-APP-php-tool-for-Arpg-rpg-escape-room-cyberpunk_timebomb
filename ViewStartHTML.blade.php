<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="stylesheet" href="halfdigi.css">
</head>
<body><script>
window.onerror = function (msg,file,line,number,ob){
    say("Blad kompilu js: "+msg+"\n file: "+file+" at "+line+":"+number + event);
	
	alert("Blad kompilu js: "+msg+"\n file: "+file+" at "+line+":"+number + event);
	}

    function feedbackModalConfirmation(clickevent){
    modalContainer.removeEventListener('click',feedbackModalConfirmation);
    feedback.classList.remove("feedback-pre-console");
    feedbackContainer.classList.remove("feedback-pre-console");
    modalContainer.classList.remove("modal-container-on");
}

function say(msg,keephidden=false){
  feedback.innerText += msg ? "\n>>>" + msg : "";
!keephidden? showfeedback():0;
}

//say = alert;

function showfeedback(){
     feedbackContainer.classList.add("feedback-pre-console");
  feedback.classList.add("feedback-pre-console");
  modalContainer.classList.add("modal-container-on");
    modalContainer.addEventListener('click',feedbackModalConfirmation);
}

window.addEventListener("load",x=>say("finished loading"));

</script>
	
<a href="<?php echo $_SERVER['SCRIPT_NAME'] ?>">Homepage</a>

<div id="modalContainer" class="modal-container">
    
    <div id="feedbackContainer" class="feedbackContainer">
    <pre id="feedback" class="feedback-pre-console">    
<?php
echo "FEEDBACK : \n";
if ($this->feedback) {          
            echo $this->feedback ;
}?>
</pre>
<div class="flex-row">
<button>OK</button>
<button onclick="feedback.innerText='cleared'">Clear</button>
</div>
</div>
</div>
<button onclick="showfeedback()">Show feedback</button>
<br/>


