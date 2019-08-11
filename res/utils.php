<?php
// define("PROJ",realpath(dirname(__FILE__) . "/../"));
define("VIEWS", PROJ . "res" . DS . "views" . DS);
define("JS", PROJ . 'js' . DS);
define("CLASSES", PROJ . "res" . DS . "classes" . DS);
// constants to determine if user just logged in or was active on this device
define("JUST_LOGGING_IN", 2);
define("LOGGED_WITH_SESSION", 3);
define("DEBUG_MODE", 1);
define("DEFAULT_TIMEZONE_NAME_LONDON", "Europe/London");
define("MY_DATE_FORMAT", "Y-m-d\TH:i:s");
define("NO_COOKIE", "NOCOOKIE_MEH");


function rafkaClassesAutoloader($class)
{
  include CLASSES . $class . '.class.php';
}

spl_autoload_register('rafkaClassesAutoloader');

// echo print_r(scandir(PROJ));
//echo PROJ;
//echo VIEWS;
$db_type = "sqlite"; //

/**
 * @var string Path of the database file (create this with _install.php)
 */
$db_sqlite_path = "./rafka_timebomb.sqlite";

/**
 * @var string System messages, likes errors, notices, etc.
 */
$feedback = "";
$GLOB_DB_CONNECTION = null;


function getGLOB_DatabaseConnection()
{
  global $db_type, $db_sqlite_path, $GLOB_DB_CONNECTION;
  if (isset($GLOB_DB_CONNECTION)) {
    return $GLOB_DB_CONNECTION;
  }


  $GLOB_DB_CONNECTION = new PDO($db_type . ':' . $db_sqlite_path);
  $GLOB_DB_CONNECTION->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  $sql = '
            PRAGMA foreign_keys = ON;
            ';
  $query = $GLOB_DB_CONNECTION->prepare($sql);
  $query->execute();
  return $GLOB_DB_CONNECTION;
}

function objectToArray($d)
{
  if (is_object($d)) {
    // Gets the properties of the given object 
    // with get_object_vars function 
    $d = get_object_vars($d);
  }
  if (is_array($d)) {
    /* * Return array converted to object * Using __FUNCTION__ (Magic constant) * for recursive call */
    return array_map(__FUNCTION__, $d);
  } else {
    // Return array
    return $d;
  }
};

function object_to_array($data)
{
  if (is_array($data) || is_object($data)) {
    $result = array();
    foreach ($data as $key => $value) {
      $result[$key] = object_to_array($value);
    }
    return $result;
  }
  return $data;
};
// require_once(realpath(dirname(__FILE__) . "/../config.php"));
//renders Layout With Content File
function View($contentFile, $variables = array())
{
  if (is_object($variables)) {
    $variables = objectToArray($variables);
  }

  //print_me($variables);
  $contentFileFullPath = VIEWS . "/" . $contentFile . ".blade.php";

  // making sure passed in variables are in scope of the template
  // each key in the $variables array will become a variable
  if ($variables) {                 //count($variables) > 0) {
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

function fromGlobal($globArr, $name, $defIfSetButEmpty = true, $defIfNotSet = false)
{
  
  if (isset($globArr[$name])) {
    if ($globArr[$name] === '') {
      return $defIfSetButEmpty;
    } else {
      return $globArr[$name];
    }
  } else {
    return $defIfNotSet;
  }
}

function req($name, $defIfSetButEmpty = true, $defIfNotSet = false)
{
  if (func_num_args()=== 2) $defIfNotSet = $defIfSetButEmpty;
  return fromGlobal($_REQUEST, $name, $defIfSetButEmpty, $defIfNotSet);
}

function get($name, $defIfSetButEmpty = true, $defIfNotSet = false)
{
 if (func_num_args()=== 2) $defIfNotSet = $defIfSetButEmpty;
return fromGlobal($_GET, $name, $defIfSetButEmpty, $defIfNotSet);
}

function post($name, $defIfSetButEmpty = true, $defIfNotSet = false)
{
 if (func_num_args()=== 2) $defIfNotSet = $defIfSetButEmpty;
return fromGlobal($_POST, $name, $defIfSetButEmpty, $defIfNotSet);
}

function cook($name, $defIfSetButEmpty = true, $defIfNotSet = false)
{
 if (func_num_args()=== 2) $defIfNotSet = $defIfSetButEmpty;
return fromGlobal($_COOKIE, $name, $defIfSetButEmpty, $defIfNotSet);
}

function sess($name, $defIfSetButEmpty = true, $defIfNotSet = false)
{
 if (func_num_args()=== 2) $defIfNotSet = $defIfSetButEmpty;
return fromGlobal($_SESSION, $name, $defIfSetButEmpty, $defIfNotSet);
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
