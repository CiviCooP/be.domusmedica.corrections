<?php


/**
 * Prefix.Fix API Fix prefix after migration
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_prefix_Fix($params) {
  $returnValues = array("Updated contacts :");
  // first set the relevant prefix id's in an array (TitelCode / prefix_id)
  $migratePrefix = array(
    1 => 1,
    2 => 2,
    5 => 3,
    6 => 4
  );
  // get all contact from civicrm with contact type individual and empty prefix
  $testDAO = CRM_Core_DAO::executeQuery('SELECT * FROM domus_migratie.leden');
  CRM_Core_Error::debug('test dao', $testDAO);
  exit();

  $sqlContact = "SELECT id, fist_name, last_name FROM civicrm_contact WHERE contact_type = %1 AND prefix_id IS NULL";
  $contact = CRM_Core_DAO::executeQuery($sqlContact, array(1 => array('Individual', 'String')));
  while ($contact->fetch()) {
    // get related record from leden with titel
    $sqlLeden = "SELECT TitelCode FROM leden WHERE LidNummer = %1";
    $titelCode = CRM_Core_DAO::singleValueQuery($sqlLeden, array(1 => $contact->id, 'Integer'));
    // now update prefix_id, display_name and addressee_display from contact
    if (isset($migratePrefix[$titelCode])) {
      $update = "UPDATE civicrm_contact SET prefix_id = %1, display_name = %2, addressee_display = %2 WHERE id = %3";
      $displayName = "";
      $updateParams = array(
        1 => array($migratePrefix[$titelCode], 'Integer')
      );
      CRM_Core_DAO::executeQuery($update, $updateParams);
      $returnValues[] = $displayName;
    }

  }
  return civicrm_api3_create_success($returnValues, $params, 'Prefix', 'Fix');
}

