<?php

class MapEntity extends TableObject
{
  public $db = null;
  public $tablename;
  public $columnNames = null;
  private $p_tableExists;
  public $lastAssembledSQL;
  public $curQuery;
  public $insertsql;
  public $lastInsertedId;
  public $lastResult;
  public $prepColumns;
  public $columns = array(
    "mapentity_id",
    "mapentity_effect_id_fk",
    "mapentity_effect_on",
    "user_id_fk",
    "mapentity_name",
    "mapentity_description",
    "mapentity_style",
    "mapentity_geometry_type",
    "mapentity_center",
    "mapentity_radius",
    "mapentity_geometry_json"
  );




  function __construct() //,array $columnNames)
  {
    $this->db = getGLOB_DatabaseConnection();
    $this->tablename = "mapentity";


    // $this->columnNames = $columnNames;
  }


  public function setUpColumnNamesFromDB()
  {
    $res = $this->db->query("PRAGMA table_info($this->tablename);");
    $columnNames = $res->fetchAll(PDO::FETCH_COLUMN, 1);
    $this->columnNames = $columnNames;
    return $columnNames;
  }

  public function exists()
  {
    $res = $this->db->query("
    SELECT count(*) FROM sqlite_master WHERE type='table' AND name='$this->tablename';
    ");
    $this->p_tableExists = $res->fetchColumn() ? true : false;
    $res->closeCursor();
    return $this->p_tableExists;
  }


  public function createTable(array $columnNamesToTypes, $additional = "", $deleteOld)
  {
    $TblNme = $this->tablename;
    $sql = "";
    if ($deleteOld && $this->exists()) {
      $res = $this->db->query("DROP TABLE $TblNme;");
      $this->p_tableExists = false;
    }

    $shouldRatherCopyTable = !$deleteOld && $this->exists() && $additional; //if constraints are added and table already exists

    try {

      $sql .= "CREATE TABLE $TblNme (";
      foreach ($columnNamesToTypes as $CN => $OPT) {
        $sql .= $CN . " " . $OPT . ", ";
      }
      $sql = rtrim($sql, ", ") . $additional . ");";

      if ($shouldRatherCopyTable) {
        $sqlstart = "PRAGMA foreign_keys=off;
  BEGIN TRANSACTION;
  ALTER TABLE $TblNme RENAME TO " . $TblNme . "_old;";
        $sqlend = "
        INSERT INTO $TblNme SELECT * FROM " . $TblNme . "_old;" .
          "DROP TABLE $TblNme" . "_old;" .
          "COMMIT;
        PRAGMA foreign_keys=on;";
        $sql = $sqlstart . $sql . $sqlend;
      }

      $succ = $this->db->exec($sql);
      return $succ;
    } catch (PDOException $e) {
      throw new PDOException($e . "SQL : " . $sql, null, $e);
    }
  }



  public function set(string $where, array $feat)
  {
    try {
      $keysCN = array_keys($feat);
      $sql = "UPDATE $this->tablename SET ";
      for ($i = 0; $i < count($feat) - 1; $i++) {
        $CN = $keysCN[$i];
        $sql .= $CN . " = :" . $CN . ", " . PHP_EOL;
      }
      $CN = $keysCN[$i];
      $sql .= $CN . " = :" . $CN . PHP_EOL;
      $sql .= " WHERE " . $where . ";";
      $query = $this->db->prepare($sql);

      foreach ($feat as $ColumnName => $NewValue) {
        $query->bindValue(":" . $ColumnName, $NewValue);
      }
      $query->execute();
      return true;
    } catch (PDOException $e) {
      throw new PDOException($e . "SQL : " . $sql, null, $e);
    }
    return false;
  }

  public function select(string $where = "", array $columnNames, string $tablejoin = "")
  {
    try {

      $colsjoined =  $columnNames(",", $columnNames);
      $sql = "SELECT $colsjoined FROM $this->tablename $tablejoin ";
      $query = $this->db->prepare($sql);

      // foreach ($columnNames as $ColumnName => $NewValue) {
      //   $query->bindValue(":" . $ColumnName, $NewValue);
      // }
      $query->execute($columnNames);
      $this->curQuery = $query;
      return $this;
    } catch (PDOException $e) {
      throw new PDOException($e . "SQL : " . $sql, null, $e);
    }
    return false;
  }

  public function preparePropsArr(array $mapentity, $user_id = null, $isnew)
  {
    $props = array(
      "mapentity_effect_id_fk" => $mapentity["properties"]["effect_id_fk"],
      "mapentity_effect_on" => $mapentity["properties"]["effect_on"],
      "mapentity_name" => $mapentity["properties"]["name"],
      "mapentity_description" => $mapentity["properties"]["description"],
      "mapentity_style" => join(",", $mapentity["properties"]["color"]),
      "mapentity_geometry_type" => $mapentity["geometry"]["type"],
      "mapentity_geometry_json" => json_encode($mapentity["geometry"]) //todo not save if circle
    );

    if ($isnew) { //$user_id should be stored in goejson aswell and here just check for it
      $props["user_id_fk"] = $user_id;
      $props['mapentity_id'] = null;
    } else {
      $props["user_id_fk"] = $user_id;
      $props['mapentity_id'] = $mapentity["id"];
    }


    if ($mapentity["geometry"]["type"] === "Circle") {
      $typespecificprops = array(
        "mapentity_center" => join(",", $mapentity["geometry"]["center"]),
        "mapentity_radius" => $mapentity["geometry"]["radius"],
      );
    } else {
      $typespecificprops = array(
        "mapentity_center" => null,
        "mapentity_radius" => null,
      );
    }
    $arr = array_merge($props, $typespecificprops);
    return $arr;
  }

  public function insertWithDataAndGetProperId(array $mapentity, $user_id, $query)
  {
    addFeedback("inserting:\n");
    addFeedback(print_me($mapentity, 1));
    $arr = $this->preparePropsArr($mapentity, $user_id, "is new");
    try {
      $s = $query->execute($arr);
    } catch (PDOException $e) {
      //todo: factor out to a function
      //---------------------------factor out to a function
      $query->debugDumpParams();
      $arrKeys = array_map('prep',array_keys($arr));
      $preps = $this->insertPreps;
      $preps_hasmissingarg = count($preps) < count($arr);
      $arr_hasmissingarg = count($arr) < count($preps);
      
      if($preps_hasmissingarg) {
        $name = '$arr';
        $howmany = count($arr) - count($preps);
        echo "preps has $howmany less elements than values array\n";
        echo "values array has then $howmany elements that have not been prepared\n";
        $danglArr = array_diff($arrKeys,$preps);

        
      }else if($arr_hasmissingarg){
        $name = '$preps';
        $howmany = count($preps) - count($arr);
         echo "values array has $howmany less elements than preps\n";
        echo "there is $howmany elements that have been prepared but not assigned to in execute\n";
        $danglArr = array_diff($preps, $arrKeys);
      }
      if (count($danglArr) > $howmany){
        echo "there are some keys that have been mismatched (look for typos)\n";
      }
      echo "$name has following dangling elements : \n";
      echo join(",",$danglArr);
      // foreach($this->insertPreps as $prep){
        
//         echo str_repeat("------",20);
//         $preps_hasmissingarg = count($this->insertPreps) < count($arr);
//         $arr_hasmissingarg = count($arr) < count($this->insertPreps);
//         for($i=0; $i<count($this->insertPreps) || $i<count($arr);$i++){
//           echo "-\n";
//           $prep = $this->insertPreps[$i] ?? "NO PREP";
//           if ($prep === "NO PREP"){

//           }
//           else{
//             $a = in_array()
//             $prepscheckedAlready[]
//           }
//           $arrKey = $arrKeys[$prep] ?? "NO KEY";
       
//         $prepk = $prep;
// // zrob najpierw porownaj count obydwu arrayow 
// // potem mapuj prepsy zeby nie mialy znaczka : potem zrob 
// // array_diff wiekszy od mniejszego pamietajac ktory jest ktory
// // jesli pozostaje cos to wtedy wyswietl juz jako normalne imie 
// // i napisz ze nie ma pary
//         if ($prepk[0] = ':') $prepk = ltrim($prep,':');
// $danglingValue = $arr[$arrKey] ?? "NO VALUE";
// $danglingKey = $arrKey;
// $danglingpair = $danglingKey . $danglingValue;
// if (isset($arr[$prepk])){
// 	$pair = $arrKey . " -----" . $arr[$prepk];
// 	}
// 	else{
// 		$pair = $danglingpair;
// 		}
// //
// $uuu = $pair;
//         echo "prep is " . $prep . " value is " . $uuu;
//         $prepValpairs[$i] = "prep is " . $prep . " value is " . $uuu;
//       }
      // print_me($prepValpairs);
      //-END--------------------------factor out to a function
      echo "values:\n";
      print_me($arr);
      echo "preps: \n";
      print_me($this->insertPreps);

      echo sql_debug($this->insertsql, $arr);
      throw $e;
    }

    $this->lastResult = $s;
    $this->lastInsertedId = $this->db->lastInsertId();
    return $this->lastInsertedId;
  }

  public function updateWithData($mapentity, $user_id, $query)
  {
    addFeedback("updating:\n");
    addFeedback(print_me($mapentity, 1));
    $arr = $this->preparePropsArr($mapentity, $user_id, false); //false means is not new 
    try {
      $s = $query->execute($arr);
    } catch (PDOException $e) {
      $query->debugDumpParams();
      print_me($arr);
      echo sql_debug($this->insertsql, $arr);
      throw $e;
    }
    $this->lastResult = $s;
    return $s;
  }

  public function saveAllFeatures(array $allFeatures, $user_id) //, array $feat)
  {
    //todo what if feat has been deleted
    //todo features geometry  dont get updated 
    $preps = array_map('prep', $this->columns); //prepare array of ":column"
    //addFeedback(print_me($preps, 1));
    $column_eq_preps = array_map('col_eq_prep', $this->columns); //prepare array of "column = :column"

    $this->updatesql = "UPDATE $this->tablename SET "
      . join(",", array_slice($column_eq_preps, 1)) //updates every column apart from id (slice)
      . " WHERE $column_eq_preps[0] ;"; //where rowid = :rowid; no need to check for user author
    $this->insertPreps = $preps;
    
    $this->insertsql = "INSERT INTO $this->tablename ( "
      . join(",", $this->columns)
      . " )  VALUES ("
      . join(",", $this->insertPreps) //inserts every column apart from id (slice), id will be auto
      . ");";

    $insertquery = $this->db->prepare($this->insertsql);
    $updatequery = $this->db->prepare($this->updatesql);


    // TODO: to mozna zrobic array_filter() albo po prostu dzielac je na dwa arraye i kazdy jesli ma length>0 
    // intializuje swoj wlasny query, wtedy oszczedzimy tworzenie query i sqlow,

    $successStatuses = array();
    foreach ($allFeatures as $feat) {
      $featId = $feat["properties"]["id"];
      $isNewFeat = startsWith($featId, "tempId");

      if ($isNewFeat) {
        $newId = $this->insertWithDataAndGetProperId($feat, $user_id, $insertquery);
        $successStatuses[] = array("oldId" => $featId, "DBId" => $newId, "success" => $this->lastResult);
      } else {
        $s = $this->updateWithData($feat, $user_id, $updatequery);
        //$updatequery->debugDumpParams();
        $successStatuses[] = array("oldId" => $featId, "DBId" => $featId, "success" => $s);
      }
    }
    return $successStatuses;
  }

  public function deleteFeatures(array $featIDs, $user_id) //, array $feat)
  {
    $preps = array_fill(0, count($featIDs), "?"); //generate array of ?s

    $this->deletesql = "DELETE FROM $this->tablename WHERE mapentity_id IN ("
      . join(",", $preps) // (?,?,?) as many as given IDs
      . ");";

    $deletequery = $this->db->prepare($this->deletesql);
    $successStatuses = $deletequery->execute($featIDs);
    echo " S|successStatuses = " . $successStatuses;
    return $successStatuses;
  }

  public function loadAllFeatures($user_id)
  {
    $allfeatsResult = $this->getAllByUser($user_id)->toTableReadyArray();
    //$allfeatsCols = $allfeatsResult["columnNames"];
    $feats = $allfeatsResult["rows"];
    $outfs = array();

    foreach ($feats as $m) {

      $f = array(
        "type" => "Feature",
        "id" => 0 + $m["mapentity_id"]
      );

      $properties = array();
      $properties["id"] = $f["id"];
      $properties["name"] = $m["mapentity_name"];
      $properties["color"] = toNumbersArray($m["mapentity_style"]);
      $properties["effect_on"] = $m["mapentity_effect_on"];
      $properties["description"] = $m["mapentity_description"];
      $properties["effect_id_fk"] = $m["mapentity_effect_id_fk"];

      if ($m["mapentity_geometry_type"] === "Circle") {
        $geom = array();
        $geom["radius"] = 0 + $m["mapentity_radius"];
        $geom["center"] = toNumbersArray($m["mapentity_center"]);
        $geom["type"] = $m["mapentity_geometry_type"];
      } else {
        $geom = json_decode($m["mapentity_geometry_json"]);
      }

      $f["geometry"] = $geom;
      $f["properties"] = $properties;

      $outfs[] = $f;
    }

    return $outfs;
  }



  /**
   * gets all of table rows with headers
   *
   * @param string $where
   * @return array TablereadyArray array("columnNames" => $columnNames,"rows" => $resultset);
   */

  public function getAllByUser($userID)
  {
    try {
      $db = $this->db;
      $sql = "SELECT * FROM  $this->tablename Where user_id_fk = :user_id_fk";
      $query = $db->prepare($sql);
      $query->setFetchMode(PDO::FETCH_ASSOC);
      $query->bindValue(":" . "user_id_fk", $userID);
      $query->execute();
    } catch (PDOException $e) {
      throw new PDOException($e . "SQL : " . $sql, null, $e);
    }
    $this->curQuery = $query;
    return $this;
  }

  public function getAll($where = "")
  {
    try {
      $db = $this->db;
      $sql = "SELECT * FROM  $this->tablename $where;";
      $query = $db->prepare($sql);
      $query->setFetchMode(PDO::FETCH_ASSOC);
      $query->execute();
    } catch (PDOException $e) {
      throw new PDOException($e . "SQL : " . $sql, null, $e);
    }

    $this->curQuery = $query;
    return $this;
  }

  public function toTableReadyArray(PDOStatement $query = null) //: array
  {
    $query = $query ? $query : $this->curQuery;
    $columnNames = array();
    $resultset = array();
    # Set columns and results array
    while ($row = $query->fetch()) {
      if (empty($columnNames)) {
        $columnNames = array_keys($row);
      }
      $resultset[] = $row;
    }
    $ret = array(
      "columnNames" => $columnNames,
      "rows" => $resultset
    );

    return $ret;
  }

  public function toObjectsArray(PDOStatement $query = null) //: array
  {
    $query = $query ? $query : $this->curQuery;
    $resultset = array();
    # Set columns and results array
    while ($row = $query->fetchObject()) {
      $resultset[] = $row;
    }

    return $resultset;
  }
}
