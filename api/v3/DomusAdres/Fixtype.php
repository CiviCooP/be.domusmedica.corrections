<?php
/**
 * DomusAdres.Fixtype API
 *
 * - als contact geen adres heeft, niks doen
 * - als contact zowel een correspondentie als facturatieadres heeft, niks doen
 * - als contact wel een correspondentieadres heeft maar geen facturatieadres, kopieer correspondentie
 *   naar facturatie
 * - als contact wel een facturatieadres heeft maar geen correspondentieadres, kopieer facturatie
 *   naar correspondentie
 * - als contact geen facturatie en geen correspondentieadres heeft maar wel een ander adres, kopieer dit
 *   naar correspondentie en daarna correspondentie naar facturatie
 * - als correspondentieadres aangemaakt wordt krijgt het altijd is primary
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_domus_adres_Fixtype($params) {
  set_time_limit(0);
  $returnValues = [];
  $countRepaired = 0;
  // haal alle contacten waarvoor een adres is
  $query = "SELECT DISTINCT(contact_id) FROM civicrm_address WHERE contact_id IS NOT NULL LIMIT 1500";
  $dao = CRM_Core_DAO::executeQuery($query);
  while ($dao->fetch()) {
    $returnValue = NULL;
    $domusAdres = new CRM_Corrections_Adres($dao->contact_id);
    // als wel correspondentie maar geen facturatie, kopieer correspondentie naar facturatie
    if (!$domusAdres->hasFactAdres && $domusAdres->hasCorrAdres) {
      $countRepaired++;
      $domusAdres->copyContactAddress($domusAdres->corrAddressTypeId, $domusAdres->factAddressTypeId);
    }
    // als wel facturatie maar geen correspondentie, kopieer correspondentie naar facturatie
    if (!$domusAdres->hasCorrAdres && $domusAdres->hasFactAdres) {
      $domusAdres->copyContactAddress($domusAdres->factAddressTypeId, $domusAdres->corrAddressTypeId);
      $countRepaired++;
    }
    // als geen van beiden, maak eerst correspondentie aan en daarna factuur
    if (!$domusAdres->hasFactAdres && !$domusAdres->hasCorrAdres) {
      $countRepaired++;
      $domusAdres->processNeither();
    }
  }
  if ($countRepaired == 0) {
    $returnValues[] = ts('Alle adressen gerepareerd!');
  }
  else {
    $returnValues[] = ts('In deze run ') . $countRepaired . ts(' gerepareerd. Volgende run is nog nodig!');
  }
  return civicrm_api3_create_success($returnValues, $params, 'DomusAdres', 'Fixtype');
}
