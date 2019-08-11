<?php
//$feedback = $this->feedback;
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="stylesheet" href="halfdigi.css">
</head>

<body>
    <script>
        EOL = "\n";
        window.onerror = function(msg, file, line, number, ob) {
            say("Blad kompilu js: " + msg + "\n file: " + file + " at " + line + ":" + number + event);

            alert("Blad kompilu js: " + msg + "\n file: " + file + " at " + line + ":" + number + event);
        }
        feedbackModalConfirmationEventListeners = {
            listeners:[],
            getFunc(ind){
                return this.listeners[ind].func;
            },
            getType(ind){
                return this.listeners[ind].eventtype;
            },
            on(eventtype,func){
                let lastlistenerid = this.listeners.push({eventtype:eventtype,func:"none"}) - 1;
                let selfRemListnr = event => {
                    func(event);
                    modalContainer.removeEventListener(eventtype,feedbackModalConfirmationEventListeners.getFunc(lastlistenerid));
                }
                this.listeners[lastlistenerid].func = selfRemListnr;
                modalContainer.addEventListener(eventtype,selfRemListnr);
                return this;
            }
        }

        lastFeedbackSeen = false;

        function feedbackModalConfirmation(event) {
            lastFeedbackSeen = true;
            modalContainer.removeEventListener('click', feedbackModalConfirmation);
            document.removeEventListener("keydown",feedbackModalConfirmation);
            feedback.classList.remove("feedback-pre-console");
            feedbackContainer.classList.remove("feedback-pre-console");
            modalContainer.classList.remove("modal-container-on");
            event.preventDefault();
        }

        function say(msg, keephidden = false, retainPrevious = false) {
            //     let prevText = /*(lastFeedbackSeen && !retainPrevious)? "\n":*/
            //     feedback.innerHTML;//.replace("<br>",EOL);
            //   feedback.innerHTML = prevText + (msg ? "\n>>>" + msg : "");
            feedback.insertAdjacentHTML("beforeend", "\n> " + msg);
            return !keephidden ? showfeedback() : feedbackModalConfirmationEventListeners;
        }

        //say = alert;

        function showfeedback() {
            feedbackContainer.classList.add("feedback-pre-console");
            feedback.classList.add("feedback-pre-console");
            modalContainer.classList.add("modal-container-on");
            modalContainer.addEventListener('click', feedbackModalConfirmation);
            document.addEventListener("keydown", feedbackModalConfirmation);
            return feedbackModalConfirmationEventListeners;
        }

        window.addEventListener("load", x => say("finished loading"));
    </script>

   

    <div id="modalContainer" class="modal-container">

        <div id="feedbackContainer" class="feedbackContainer">
            <pre id="feedback" class="feedback-pre-console">
FEEDBACK:<?= $feedback; ?>
</pre>
            <div class="flex-row">
                <button>OK</button>
                <button onclick="feedback.innerText='cleared'">Clear</button>
            </div>
        </div>
    </div>
    
    <br />
    <a href="<?php echo $_SERVER['SCRIPT_NAME'] ?>"><button>Homepage</button></a>
    <button onclick="showfeedback()">Show feedback</button>