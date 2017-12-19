<?php

/**
 * Contribution.Fixstatus API
 * API is supposed to run as a scheduled job. Reads all contributions of financial type 'Vorming Domus Medica'
 * with contribution status 'Pending' and updates the status to 'Completed'
 *
 * @author Erik Hommel (CiviCooP) <erik.hommel@civicoop.org>
 * @date 19 Dec 2017
 * @license AGPL-3.0
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws CiviCRM_API3_Exception
 */
function civicrm_api3_contribution_Fixstatus($params) {
  $returnValues = array();
  $count = 0;
  try {
    $vormingFinTypeId = civicrm_api3('FinancialType', 'getvalue', array(
      'name' => 'Vorming Domus Medica',
      'return' => 'id',
    ));
  }
  catch (CiviCRM_API3_Exception $ex) {
    civicrm_api3_create_error(ts('Geen financieel type met de naam Vorming Domus Medica gevonden in ' . __METHOD__
      . ' (extensie be.domusmedica.corrections)'));
  }
  try {
    $pendingStatusId = civicrm_api3('OptionValue', 'getvalue', array(
      'name' => 'Pending',
      'option_group_id' => 'contribution_status',
      'return' => 'value',
    ));
    $completedStatusId = civicrm_api3('OptionValue', 'getvalue', array(
      'name' => 'Completed',
      'option_group_id' => 'contribution_status',
      'return' => 'value',
    ));
  }
  catch (CiviCRM_API3_Exception $ex) {
    civicrm_api3_create_error(ts('Geen bijdrage status Pending of Completed gevonden in ' . __METHOD__
      . ' (extensie be.domusmedica.corrections)'));
  }

  $query = 'SELECT id FROM civicrm_contribution WHERE financial_type_id = %1 AND contribution_status_id = %2 AND is_test = %3';
  $dao = CRM_Core_DAO::executeQuery($query, array(
    1 => array($vormingFinTypeId, 'Integer'),
    2 => array($pendingStatusId, 'Integer'),
    3 => array(0, 'Integer'),
  ));
  while ($dao->fetch()) {
    try {
      civicrm_api3('Contribution', 'create', array(
        'id' => $dao->id,
        'contribution_status_id' => $completedStatusId,
      ));
      $count++;
    }
    catch (CiviCRM_API3_Exception $ex) {
      CRM_Core_Error::debug_log_message('Could not update contribution with id '.$dao->id.' to status Completed in API Contribution Fixstatus');
    }
  }
  $returnValues[] = $count.ts(' bijdragen bijgewerkt naar status Betaald');
  return civicrm_api3_create_success($returnValues, $params, 'Contribution', 'Fixstatus');
}
