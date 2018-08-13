<?php

/**
 * Class  voor Domus Medica adres correctie
 *
 * @author Erik Hommel (CiviCooP) <erik.hommel@civicoop.org>
 * @date 13 Aug 2018
 * @license AGPL-3.0
 */
class CRM_Corrections_Adres {

  public $hasCorrAdres = FALSE;
  public $hasFactAdres = FALSE;
  private $_contactId = NULL;
  private $_contactAddressData = [];
  public $corrAddressTypeId = NULL;
  public $factAddressTypeId = NULL;

  /**
   * CRM_Corrections_Adres constructor.
   *
   * @param int $contactId
   */
  public function __construct($contactId) {
    try {
      $locationTypes = civicrm_api3('LocationType', 'get', [
        'options' => ['limit' => 0],
      ])['values'];
      foreach ($locationTypes as $locationTypeId => $locationType) {
        switch ($locationType['name']) {
          case "Billing":
            $this->factAddressTypeId = $locationTypeId;
            break;

          case "correspondentieadres":
            $this->corrAddressTypeId = $locationTypeId;
            break;
        }
      }
    $query = "SELECT COUNT(*) FROM civicrm_contact WHERE id = %1";
    $count = CRM_Core_DAO::singleValueQuery($query, [1 => [$contactId, 'Integer']]);
    if ($count == 1) {
      $this->_contactId = $contactId;
      $this->getContactAdres();
      $this->checkAddresses();
    }
    else {
      CRM_Core_Error::createError(ts('Could not find a contact with id ') . $contactId . ts(' in ') . __METHOD__);
    }
      return;
    }
    catch (CiviCRM_API3_Exception $ex) {
      CRM_Core_Error::createError(ts('Could not find any location types in ') . __METHOD__);
    }
  }

  /**
   * Method to get all the addresses of the contact
   */
  private function getContactAdres() {
    try {
      $this->_contactAddressData = civicrm_api3('Address', 'get', [
        'sequential' => 1,
        'options' => ['limit' => 0],
        'contact_id' => $this->_contactId,
      ])['values'];
      return;
    }
    catch (CiviCRM_API3_Exception $ex) {
      Civi::log()->warning(ts('Error getting adresses for contact ') . $this->_contactId . ts(' in ') . __METHOD__);
    }
  }

  /**
   * Method to determine if contact has corr address
   */
  private function checkAddresses() {
    $this->hasCorrAdres = FALSE;
    $this->hasFactAdres = FALSE;
    foreach ($this->_contactAddressData as $address) {
      if (isset($address['location_type_id'])) {
        switch ($address['location_type_id']) {
          case $this->corrAddressTypeId:
            $this->hasCorrAdres = TRUE;
            break;

          case $this->factAddressTypeId:
            $this->hasFactAdres = TRUE;
            break;
        }
      }
    }
  }

  /**
   * Method to copy an existing address of a certain type into a new one of another type
   *
   * @param $fromTypeId
   * @param $toTypeId
   */
  public function copyContactAddress($fromTypeId, $toTypeId) {
    foreach ($this->_contactAddressData as $addressData) {
      if ($addressData['location_type_id'] == $fromTypeId) {
        $newAddressParams = $addressData;
        $newAddressParams['location_type_id'] = $toTypeId;
        $newAddressParams['is_primary'] = 0;
        if ($toTypeId == $this->corrAddressTypeId) {
          $newAddressParams['is_primary'] = 1;
        }
        unset($newAddressParams['id']);
        try {
          civicrm_api3('Address', 'create', $newAddressParams);
        }
        catch (CiviCRM_API3_Exception $ex) {
          CRM_Core_Error::createError(ts('Error trying to copy address from location type id ')
            . $fromTypeId . ts(' to location type id ') . $toTypeId . ts(' with data ') . serialize($newAddressParams)
            . ts(', error message from Address Create API: ') . $ex->getMessage());
        }
      }
    }
  }

  /**
   * Method to process adres when no facturatie en no correspondentie
   */
  public function processNeither() {
    $sourceLocationTypeId = NULL;
    // gebruik eerste als bron tenzij er een andere primary is
    foreach ($this->_contactAddressData as $adresData) {
      if (!$sourceLocationTypeId || $adresData['is_primary'] == 1) {
        $sourceLocationTypeId = $adresData['location_type_id'];
      }
    }
    if ($sourceLocationTypeId) {
      // first copy source to correspondentie
      $this->copyContactAddress($sourceLocationTypeId, $this->corrAddressTypeId);
      // then copy to facturatie
      $this->copyContactAddress($sourceLocationTypeId, $this->factAddressTypeId);
    }
  }

}
