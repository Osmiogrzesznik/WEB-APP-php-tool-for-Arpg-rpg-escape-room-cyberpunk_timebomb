<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width" />
    <title>Test</title>
</head>
<style>
    html,
    body,
    button {
        --fg: #20f86c;
        --rd: red;
        background-color: #000;
        color: #20f86c;
        font-family: digitalix;
        font-size: 100%;
    }
    
    btn,
    td {
        border: 1px solid;
        padding: 0.5em;
        background-color: rgba(0, 0, 0, .7);
        text-align: center;
    }
    
    btn {
        margin: 0.5em;
    }
    
    .KBdisplay {
        border: 0.5px solid;
        /*         padding: 0.5em; */
        /*         margin: 0.5em; */
        background-color: rgba(0, 0, 0, .7);
        /* 				max-height:10vh; */
    }
    
    table {
        position: absolute;
        margin: 10vh;
        width: 80vw;
        height: 80vh;
    }
    
    .btnCNT {
        position: absolute;
        top: 5px;
        left: 5px;
    }
    
    .counterCNT {
        position: relative;
        /*         font-family: digitalix; */
    }
    
    .counterScreen {
        text-align: center;
        line-height: 100vw;
        /* 	text-align-last:center; */
        height: 100vh;
        display: flex;
        justify-content: center;
        align-items: center;
        /*	position:absolute;
 left:0; right:0;
 top:0; bottom:0;
 margin:auto;
 max-width:100%;
 max-height:100%;
 overflow:auto;*/
    }
    
    .digits {
        font-family: digit;
        color: red;
        font-size: 20vw;
        text-align: center;
        line-height: 100vw;
        /* 	text-align-last:center; */
        height: 100vh;
        display: flex;
        justify-content: center;
        align-items: center;
        text-shadow: 0px 0px 10px red;
    }
    
    .cyber {
        font-family: digitalix;
    }
    
    @font-face {
        font-family: "digitalix";
        src: url("fonts/digitalix.ttf") format("truetype");
    }
    
    @font-face {
        font-family: "digit";
        src: url("fonts/Digit.ttf") format("truetype");
    }
    
    @font-face {
        font-family: "taximeter";
        src: url("fonts/taximeter.ttf") format("truetype");
    }
    
    @font-face {
        font-family: "alarm clock";
        src: url("fonts/alarm clock.ttf") format("truetype");
    }
    
    @font-face {
        font-family: "Computerfont";
        src: url("fonts/Computerfont.ttf") format("truetype");
    }
    
    .WrongAnswer {
        animation-name: shake;
        animation-duration: 0.1s;
        animation-iteration-count: 4;
        color: var(--rd);
        border-color: var(--rd);
    }
    
    .CorrectAnswer {
        animation-name: shine;
        animation-duration: 1s;
        animation-iteration-count: 1;
        animation-timing-function: cubic-bezier(0.190, 1.000, 0.220, 1.000);
        color: green;
        border-color: green;
    }
    
    @keyframes shine {
        0% {
            opacity: 1;
            /* 	color:red; */
            transform: scaleX(1) scaleY(1);
        }
        /*         50% {
  opacity: .5;
	  transform: scaleX(4) scaleY(0.1);
  } */
        100% {
            opacity: 0;
            transform: scaleX(16) scaleY(0);
            /* 	color:red; */
        }
    }
    
    @keyframes shake {
        0%,
        100% {
            /*             opacity: 0; */
            transform: translateX(0);
        }
        50% {
            /*             opacity: 1; */
            transform: translateX(-40px);
        }
    }
</style>

<body>

    <div id="counterCNT" class="counterCNT">
        <div id="counterMeas" class="counter">
            <span id="counter" class="digits">
   00:00:00
	</span>
        </div>
        <div id="btnCNT" class="btnCNT">
            <btn id="str" onclick="cx.start()">
                ST
            </btn>
            <btn id="stp" onclick="cx.nxfnt()">
                nxfnt
            </btn>
            <btn id="boominf" onclick="togglenumkeyb()">
                numkeyb
            </btn>
        </div>
    </div>



    <!-- 
 <table id="numkeyb" class="btnCNT" style="display:none">
  <tr>
   <td id="numDisp" colspan="9" class="KBdisplay" style="width:100%">
	  ____
   </td>
  </tr>
  <tr>
   <td id="k7" onclick="numKbdHandler(event)">
	7
   </td>
   <td id="k8" onclick="numKbdHandler(event)">
	8
   </td>
   <td id="k9" onclick="numKbdHandler(event)">
	9
   </td>
  </tr>
  <tr>
   <td id="k7" onclick="numKbdHandler(event)">
	4
   </td>
   <td id="k8" onclick="numKbdHandler(event)">
	5
   </td>
   <td id="k9" onclick="numKbdHandler(event)">
	6
   </td>
  </tr>
  <tr>
   <td id="k7" onclick="numKbdHandler(event)">
	1
   </td>
   <td id="k8" onclick="numKbdHandler(event)">
	2
   </td>
   <td id="k9" onclick="numKbdHandler(event)">
	3
   </td>
  </tr>
  <tr>
   <td id="k7" onclick="keyboff()">
	X
   </td>
   <td id="k8" onclick="numKbdHandler(event)">
	0
   </td>
   <td id="k9" onclick="dellast()">
	D
   </td>
  </tr>
 </table>
   -->
    <table id="customKeybTMPLT" class="btnCNT" style="display:none">
        <tr>
            <td colspan="9" class="KBdisplay" style="  ">
                ____
            </td>
        </tr>
        <tr id="rowTMPLT" style="display:none">
        </tr>
        <tr style="display:none">
            <td id="keyTMPLT" style="display:none">
            </td>
        </tr>
    </table>




<script type="text/javascript">
</script>
    <script type="text/javascript" src="timebomb.js"></script>
</body>

</html>