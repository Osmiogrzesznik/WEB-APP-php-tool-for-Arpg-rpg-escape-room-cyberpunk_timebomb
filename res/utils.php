<?php
// define("PROJ",realpath(dirname(__FILE__) . "/../"));
define("VIEWS", PROJ."/res/views/");
// constants to determine if user just logged in or was active on this device
define("JUST_LOGGING_IN", 2);
define("LOGGED_WITH_SESSION", 3);
define("DEBUG_MODE", 1);
define("DEFAULT_TIMEZONE_NAME_LONDON", "Europe/London");
define("MY_DATE_FORMAT", "Y-m-d\TH:i:s");
define("NO_COOKIE", "NOCOOKIE_MEH");


// echo print_r(scandir(PROJ));
//echo PROJ;
//echo VIEWS;


    // require_once(realpath(dirname(__FILE__) . "/../config.php"));
 //renders Layout With Content File
    function View($contentFile, $variables = array())
    {
        //print_me($variables);
        $contentFileFullPath = VIEWS . "/" . $contentFile . ".blade.php";
        
        // making sure passed in variables are in scope of the template
        // each key in the $variables array will become a variable
        if ($variables){                 //count($variables) > 0) {
            foreach ($variables as $key => $value) {
                if (strlen($key) > 0) {
                    ${$key} = $value;
                    
                }
            }
        }
        // require_once(VIEWS_PATH . "/header.php");
     
        // echo "<div id=\"container\">\n"
        //    . "\t<div id=\"content\">\n";
     
        if (file_exists($contentFileFullPath)) {
            require_once($contentFileFullPath);
        } else {
            /*
                If the file isn't found the error can be handled in lots of ways.
                In this case we will just include an error template.
            */
            require_once(VIEWS_PATH . "/error.php");
        }
    }

function req($name,$def=''){
    return isset($_REQUEST[$name]) ? $_REQUEST[$name] : $def;
}

function get($name,$def=''){
    return isset($_GET[$name]) ? $_GET[$name] : $def;
}

function cook($name,$def=''){
    return isset($_COOKIE[$name]) ? $_COOKIE[$name] : $def;
}

function sess($name,$def=''){
    return isset($_SESSION[$name]) ? $_SESSION[$name] : $def;
}

function print_me($var, $return = false)
{
    if (!$return) {
        echo "<pre>";
        print_r($var, $return);
        echo "</pre>";
    } else {
        return "<pre>" . print_r($var, true) . "</pre>";
    }
}


?>