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
  $updateCount = 0;
  // first set the relevant prefix id's in an array (TitelCode / prefix name)
  $migratePrefixes = array(
    1 => array('name' => 'Mr.', 'prefix_id' => null, 'label' => null),
    2 => array('name' => 'Dr.', 'prefix_id' => null, 'label' => null),
    5 => array('name' => 'Ms.', 'prefix_id' => null, 'label' => null),
    6 => array('name' => 'Prof. Dr.', 'prefix_id' => null, 'label' => null));
  // now add prefix ids to the array
  foreach ($migratePrefixes as $titelCode => $migratePrefix) {
    $prefix = civicrm_api3('OptionValue', 'getsingle', array(
      'option_group_id' => 'individual_prefix',
      'name' => $migratePrefix['name'],
    ));
    $migratePrefixes[$titelCode]['prefix_id'] = $prefix['value'];
    $migratePrefixes[$titelCode]['label'] = $prefix['label'];
  }
  // get all contact from civicrm with contact type individual and linked titelcode from leden
  $sqlContact = "SELECT cc.id, cc.first_name, cc.last_name, ld.TitelCode AS titel_code
FROM civicrm_contact cc LEFT JOIN leden ld ON cc.id = ld.LidNummer
WHERE cc.contact_type = %1 AND ld.TitelCode IS NOT NULL AND ld.TitelCode != '' AND cc.modified_date < CURDATE() LIMIT 500";
  $contact = CRM_Core_DAO::executeQuery($sqlContact, array(1 => array('Individual', 'String')));
  if ($contact->N == 0) {
    $returnValues = array('Fixed all individuals');
  } else {
    while ($contact->fetch()) {
      // only if we have a titel code to go from
      if (isset($migratePrefixes[$contact->titel_code])) {
        $updateParams = array(
          'id' => $contact->id,
          'contact_type' => 'Individual',
          'display_name' => $migratePrefixes[$contact->titel_code]['label'] . ' ' . $contact->first_name . ' ' . $contact->last_name,
          'addressee_display' => 1,
          'email_greeting_id' => 3,
          'postal_greeting_id' => 3,
          'prefix_id' => $migratePrefixes[$contact->titel_code]['prefix_id']);
        civicrm_api3('Contact', 'Create', $updateParams);
        $updateCount++;
      }
    }
    $returnValues = array($updateCount . ' contacts fixed');
  }
  return civicrm_api3_create_success($returnValues, $params, 'Prefix', 'Fix');
}

