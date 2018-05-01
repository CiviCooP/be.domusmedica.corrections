<?php
use CRM_Corrections_ExtensionUtil as E;

/**
 * ContributionSoft.Sepfix API
 * Temp job om zachte kredieten te verwijderen ivm someoneelsepays, en line item bij te werken
 *
 * @author Erik Hommel (CiviCooP) <erik.hommel@civicoop.org>
 * @date 1 May 2018
 * @license AGPL-3.0
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_contribution_soft_Sepfix($params) {
  $returnValues = [];
  $query = "SELECT soft.id AS soft_id, soft.contribution_id, li.id AS line_item_id, cc.display_name, 
    fv.label AS fee_label, CAST(li.qty AS UNSIGNED) AS quantity
    FROM civicrm_contribution_soft AS soft
    LEFT JOIN civicrm_line_item AS li ON soft.contribution_id = li.contribution_id
    LEFT JOIN civicrm_price_field_value AS fv ON li.price_field_value_id = fv.id
    LEFT JOIN civicrm_contact AS cc ON soft.contact_id = cc.id
    WHERE soft_credit_type_id = %1";
  $dao = CRM_Core_DAO::executeQuery($query, [1 => [12, 'Integer']]);
  while ($dao->fetch()) {
    // update line item label
    $newLabel = $dao->fee_label . ' (on behalf of ' . $dao->display_name
      . '): ' . $dao->quantity;
    $update = 'UPDATE civicrm_line_item SET label = %1 WHERE id = %2';
    CRM_Core_DAO::executeQuery($update, [
      1 => [$newLabel, 'String'],
      2 => [$dao->line_item_id, 'Integer'],
    ]);
    // remove soft credit
    $delete = 'DELETE FROM civicrm_contribution_soft WHERE id = %1';
    CRM_Core_DAO::executeQuery($delete, [1 => [$dao->soft_id, 'Integer']]);
  }
  return civicrm_api3_create_success($returnValues, $params, 'ContributionSoft', 'Sepfix');
}
