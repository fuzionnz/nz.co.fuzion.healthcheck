<?php
use CRM_Healthcheck_ExtensionUtil as E;

/**
 * HCDedupe.Updatecount API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 *
 * @see https://docs.civicrm.org/dev/en/latest/framework/api-architecture/
 */
function _civicrm_api3_h_c_dedupe_Updatecount_spec(&$spec) {
}

/**
 * HCDedupe.Updatecount API
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
function civicrm_api3_h_c_dedupe_Updatecount($params) {
  $returnValues = [];
  CRM_Healthcheck_BAO_HCDedupe::updateCount();
  return civicrm_api3_create_success($returnValues, $params, 'HCDedupe', 'Updatecount');
}
