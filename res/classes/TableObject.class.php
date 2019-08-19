<?php

class TableObject
{
  public $db = null;
  public $tablename;
  public $columnNames = null;
  private $p_tableExists;
  public $lastAssembledSQL;
  public $curQuery;

  function __construct(String $tablename)//,array $columnNames)
  {
    $this->db = getGLOB_DatabaseConnection();
    $this->tablename = $tablename;
   // $this->columnNames = $columnNames;
  }

  public function setUpColumnNamesFromDB(){
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
      throw new PDOException($e . "SQL : " . $sql , null, $e);
    }
  }



  public function set(string $where, array $columnNamesToNewValues)
  {
    try {
      $keysCN = array_keys($columnNamesToNewValues);
      $sql = "UPDATE $this->tablename SET ";
      for ($i = 0; $i < count($columnNamesToNewValues) - 1; $i++) {
        $CN = $keysCN[$i];
        $sql .= $CN . " = :" . $CN . ", " . PHP_EOL;
      }
      $CN = $keysCN[$i];
      $sql .= $CN . " = :" . $CN . PHP_EOL;
      $sql .= " WHERE " . $where . ";";
      $query = $this->db->prepare($sql);

      foreach ($columnNamesToNewValues as $ColumnName => $NewValue) {
        $query->bindValue(":" . $ColumnName, $NewValue);
      }
      $query->execute();
      return true;
    } catch (PDOException $e) {
      throw new PDOException($e . "SQL : " . $sql , null, $e);
    }
    return false;
  }

  public function select(string $where="", array $columnNames,string $tablejoin ="")
  {
    try {
	
      $colsjoined =  $columnNames (",",$columnNames);
      $sql = "SELECT $colsjoined FROM $this->tablename $tablejoin ";
      $query = $this->db->prepare($sql);

      // foreach ($columnNames as $ColumnName => $NewValue) {
      //   $query->bindValue(":" . $ColumnName, $NewValue);
      // }
      $query->execute($columnNames);
      return true;
    } catch (PDOException $e) {
      throw new PDOException($e . "SQL : " . $sql , null, $e);

    }
    return false;
  }

  public function insert(string $where, array $columnNamesToNewValues)
  {
    try {
      $keysCN = array_keys($columnNamesToNewValues);
      $sql = "INSERT INTO $this->tablename ( ";
      for ($i = 0; $i < count($columnNamesToNewValues) - 1; $i++) {
        $CN = $keysCN[$i];
        $sql .= $CN .  ", ";
      }
      $CN = $keysCN[$i];
      $sql .= $CN . " = :" . $CN;
      $sql .= $where . ";";
      $query = $this->db->prepare($sql);

      foreach ($columnNamesToNewValues as $ColumnName => $NewValue) {
        $query->bindValue(":" . $ColumnName, $NewValue);
      }
      $s = $query->execute();
      return $s;
    } catch (PDOException $e) {
      throw new PDOException($e . "SQL : " . $sql , null, $e);

    }
  }
/**
 * gets all of table rows with headers
 *
 * @param string $where
 * @return array TablereadyArray array("columnNames" => $columnNames,"rows" => $resultset);
 */
  public function getAll($where="")
  {
    try {
      $db = $this->db;
      $sql = "SELECT * FROM  $this->tablename $where;";
      $query = $db->prepare($sql);
      $query->setFetchMode(PDO::FETCH_ASSOC);
      $query->execute();
    } catch (PDOException $e) {
      throw new PDOException($e . "SQL : " . $sql , null, $e);
    }
    $this->curQuery = $query;
    return $this;
  }

  public static function toTableReadyArray(PDOStatement $query = null)//: array
  {
    $query = $query || $this->curQuery;
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

  public function toObjectsArray(PDOStatement $query = null)//: array
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
