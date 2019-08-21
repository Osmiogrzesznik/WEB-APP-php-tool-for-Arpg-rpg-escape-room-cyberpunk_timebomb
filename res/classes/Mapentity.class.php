<?php

class MapEntity
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
    "mapentity_geometrytype",
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

  public function insertWithDataAndGetProperId(array $mapentity, $user_id, $query)
  {
    print_me ($mapentity);
    $s = $query->execute(array(
      "mapentity_effect_id_fk" => $mapentity["properties"]["effect_id_fk"],
      "mapentity_effect_on" => $mapentity["properties"]["effect_on"],
      "user_id_fk" => $user_id,
      "mapentity_name" => $mapentity["properties"]["name"],
      "mapentity_description" => $mapentity["properties"]["description"],
      "mapentity_style" => $mapentity["properties"]["color"],
      "mapentity_geometrytype" => $mapentity["geometry"]["type"],
      "mapentity_center" => $mapentity["geometry"]["center"],
      "mapentity_radius" => $mapentity["geometry"]["radius"],
      "mapentity_geometry_json" => json_encode($mapentity["geometry"])
    ));
    $this->lastResult = $s;
    $this->lastInsertedId = $this->db->lastInsertId();
    return $this->lastInsertedId;
  }

  public function updateWithData($mapentity, $query)
  {
    
    $s = $query->execute(array(
      "mapentity_id" => $mapentity["properties"]["id"],
      "mapentity_effect_id_fk" => $mapentity["properties"]["effectd_id_fk"],
      "mapentity_effect_on" => $mapentity["properties"]["effect_on"],
      //doesnt changes "user_id_fk" => $user_id,
      "mapentity_name" => $mapentity["properties"]["name"],
      "mapentity_description" => $mapentity["properties"]["description"],
      "mapentity_style" => $mapentity["properties"]["color"],
      "mapentity_geometrytype" => $mapentity["geometry"]["type"],
      "mapentity_center" => $mapentity["geometry"]["center"],
      "mapentity_radius" => $mapentity["geometry"]["radius"],
      "mapentity_geometry_json" => json_encode($mapentity["geometry"])
    ));
    $this->lastResult = $s;
    return $s;
  }

  public function saveAllFeatures(array $allFeatures, $user_id) //, array $feat)
  {
    $preps = array_map('prep', $this->columns); //prepare array of ":column"
    $column_eq_preps = array_map('col_eq_prep', $this->columns); //prepare array of "column = :column"

    $this->updatesql = "UPDATE $this->tablename SET "
      . join(",", array_slice($column_eq_preps, 1)) //updates every column apart from id (slice)
      . " WHERE $column_eq_preps[0] ;"; //where rowid = :rowid;

    $this->insertsql = "INSERT INTO $this->tablename ( "
      . join(",", $this->columns)
      . " )  VALUES (null, "
      . join(",", array_slice($preps, 1)) //inserts every column apart from id (slice), id will be auto
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
          $s = $this->updateWithData($feat, $updatequery);
          $successStatuses[] = array("oldId" => $featId, "DBId" => $newId, "success" => $s);
        }
     
    }
    return $successStatuses;
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
