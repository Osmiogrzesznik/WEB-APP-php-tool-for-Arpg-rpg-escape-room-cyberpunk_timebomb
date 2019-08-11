<?php

//echo ( false == "" ? "yeah":"nope");
//exit;



define("DS", DIRECTORY_SEPARATOR);
define("PROJ", realpath(dirname(__FILE__)) . DS);

require_once(PROJ . "res" . DS . "utils.php");


installDrawnFeature();

function installDrawnFeature()
{

  //$db= getGLOB_DatabaseConnection();

  /*
$sql= "create table	 if not exists drawnfeature(
'drawnfeature_id' INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
'drawnfeature_creator ' integer not null,
'drawnfeature_name' TEXT,
'drawnfeature_description' TEXT,
'drawnfeature_type' varchar(16),
'drawnfeature_geometryjson' text,
'drawnfeature_effectFK' integer,
'drawnfeature_color' TEXT" ;
'drawnfeature_pseudo_id' TEXT not null);
";*/
  $fts = new modelTableObject("drawnfeature3");
  $cols = array(
    'id' => 'INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL',
    'creator_fk' => 'integer',
'creator' => 'integer not null',
'name' => 'TEXT',
'description' => 'TEXT',
'type' => 'varchar(16)',
'geometryjson' => 'TEXT',
'effect_FK' => 'integer',
'color' => 'TEXT',
'pseudo_id' => 'TEXT not null'
  );

  $u = $fts->createTable($cols,null,true);
  echo $u;
  //$succ= $db->query($sql);
}



//file_put_contents("a.txt",print_r($_REQUEST,1));
if (get("savedrawnfeatures", false)) {
  $fs = file_get_contents("php://input");
  $fsphp = json_decode($fs);


  $db = getGLOB_DatabaseConnection();

  /**
   *Todo:
   *kalkulacja dystansu musi chyba sie odywac na kliencie niestety, kazdy player kalkuluje swoja
   * bomb should have a choice of detonation effect
   *1. creates Feature bomb_feature(eg circle) with specified effect like
   * radiation 
   *2. stops something el-se working
   *3. damages the player object(hp mechanics) in radius(loccation based),
   *and or damages the functionality of their personal terminals. Effect !!
   *effect 3 must only happen once for everybody in db having their locatuon in 
   *raidus of effect
   *basically on detonation ut should send request , or if browser off,
   *	the each request should check if there are any effects, if there are loop
   *		 through all players checking for distance. if in distance change values, and
   *			 add status wasnearexplosion or something.
   **/


  $fsnice = json_encode($fsphp, JSON_PRETTY_PRINT);
  $succ = file_put_contents("aaaa.json", $fsnice);


  $ans = array(
    "ok" => $succ,
    "received" => json_decode($fs),
  );
  echo json_encode($ans);
} else {

  $ans = file_get_contents("aaaa.json");
  echo $ans;
}
