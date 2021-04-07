<?php

/**
 * Class  voor Domus Medica vinden van onvindbare message text
 *
 * @author Erik Hommel (CiviCooP) <erik.hommel@civicoop.org>
 * @date 7 April 2021
 * @license AGPL-3.0
 */
class CRM_Corrections_Msgfind {
  private $_likeTxt;
  private $_dataBase;
  private $_columns = [];

  /**
   * CRM_Corrections_Msgfind constructor.
   */
  public function __construct() {
    $this->_likeTxt = "een elektronisch factuur voor uw boekhouding";
    $this->_dataBase = CRM_Core_DAO::getDatabaseName();
  }

  /**
   * Method om tabellen op te halen en te bekijken of de specifieke tekst er in een van de tekstvelden zit
   */
  public function find() {
    $tableQuery = "SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = %1";
    $table = CRM_Core_DAO::executeQuery($tableQuery, [1 => [$this->_dataBase, "String"]]);
    while ($table->fetch()) {
      Civi::log()->debug('Inspecteer tabel ' . $table->TABLE_NAME);
      // get all columns from table
      $this->getTableColumns($table->TABLE_NAME);
      $select = $this->buildSelect();
      $where = $this->buildWhere();
      if (!empty($select) && !empty($where)) {
        $query = "SELECT " . $this->buildSelect() . " FROM " . $table->TABLE_NAME . " WHERE " . $this->buildWhere();
        $dao = CRM_Core_DAO::executeQuery($query, [1 => ["%" . $this->_likeTxt . "%", "String"]]);
        while ($dao->fetch()) {
          foreach ($this->_columns as $columnName) {
            if (strpos($dao->$columnName, $this->_likeTxt) !== FALSE) {
              Civi::log()->debug('String komt voor in kolom ' . $columnName . ' in tabel ' . $table->TABLE_NAME);
            }
          }
        }
      }
    }

  }

  /**
   * @param $column
   * @return string
   */
  private function buildWhere() {
    $result = [];
    foreach ($this->_columns as $columnName) {
      $result[] = $columnName . " LIKE %1";
    }
    return implode(" OR ", $result);
  }

  /**
   * @return string
   */
  private function buildSelect() {
    $result = [];
    foreach ($this->_columns as $columnName) {
      $result[] = $columnName;
    }
    return implode(",", $result);
  }

  /**
   * @param $tableName
   * @return CRM_Core_DAO|DB_Error|object
   */
  private function getTableColumns($tableName) {
    $this->_columns = [];
    $query = "SELECT COLUMN_NAME FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = %1 AND TABLE_NAME = %2 AND DATA_TYPE IN (%3, %4, %5, %6, %7, %8, %9, %10, %11, %12)";
    $queryParams = [
      1 => [CRM_Core_DAO::getDatabaseName(), "String"],
      2 => [$tableName, "String"],
      3 => ["char", "String"],
      4 => ["varchar", "String"],
      5 => ["blob", "String"],
      6 => ["tinyblob", "String"],
      7 => ["tinytext", "String"],
      8 => ["text", "String"],
      9 => ["mediumtext", "String"],
      10 => ["mediumblob", "String"],
      11 => ["longtext", "String"],
      12 => ["longblob", "String"],
    ];
    $column = CRM_Core_DAO::executeQuery($query, $queryParams);
    while ($column->fetch()) {
      $this->_columns[] = $column->COLUMN_NAME;
    }
  }

}
