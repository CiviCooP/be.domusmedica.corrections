<?php
use CRM_Corrections_ExtensionUtil as E;

/**
 * MsgTxt.Find API
 *
 * @param array $params
 *
 * @return array
 *   API result descriptor
 *
 * @see civicrm_api3_create_success
 *
 * @throws API_Exception
 */
function civicrm_api3_msg_txt_Find($params) {
  $msgFind = new CRM_Corrections_Msgfind();
  $msgFind->find();
  return civicrm_api3_create_success([], $params, 'MsgTxt', 'Find');
}
