<?php
use CRM_Healthcheck_ExtensionUtil as E;

class CRM_Healthcheck_BAO_HCDedupe extends CRM_Healthcheck_DAO_HCDedupe {

  /**
   * Create a new HCDedupe based on array-data
   *
   * @param array $params key-value pairs
   * @return CRM_Healthcheck_DAO_HCDedupe|NULL
   **/
  public static function create($params) {
    $className = 'CRM_Healthcheck_DAO_HCDedupe';
    $entityName = 'HCDedupe';
    $hook = empty($params['id']) ? 'create' : 'edit';

    CRM_Utils_Hook::pre($hook, $entityName, CRM_Utils_Array::value('id', $params), $params);
    $instance = new $className();
    $instance->copyValues($params);
    $instance->save();
    CRM_Utils_Hook::post($hook, $entityName, $instance->id, $instance);

    return $instance;
  }

  /**
   * update counts of matching contact per rule.
   */
  public static function updateCount() {
    $rules = CRM_Dedupe_BAO_RuleGroup::getByType();
    foreach ($rules as $id => $title) {
      $dupeCount = count(CRM_Dedupe_Finder::dupes($id));
      if (!empty($dupeCount)) {
        $data = [];
        $currentMonth = date('m');
        $currentYear = date('Y');
        $params = [
          'rule_id' => $id,
          'action_log_date' => date('Y-m-d'),
        ];
        $dao = CRM_Core_DAO::executeQuery("
          SELECT id, value
          FROM civicrm_healthcheck_dedupe
          WHERE MONTH(action_log_date) = {$currentMonth} AND YEAR(action_log_date) = {$currentYear}
          AND rule_id = {$id}"
        );
        if ($dao->fetch()) {
          $params['id'] = $dao->id;
          $data = json_decode($dao->value, TRUE);
        }
        $data[date('Y-m-d')] = $dupeCount;
        $params['value'] = json_encode($data);
        self::create($params);
      }
    }
  }

}
