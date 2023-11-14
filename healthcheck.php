<?php

require_once 'healthcheck.civix.php';
use CRM_Healthcheck_ExtensionUtil as E;

/**
 * Implements hook_civicrm_config().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function healthcheck_civicrm_config(&$config) {
  $smarty= CRM_Core_Smarty::singleton();
  require_once 'CRM/Core/Smarty/plugins/function.crmHCSQL.php';
  $smarty->register_function("crmHCSQL", "smarty_function_crmHCSQL");
  _healthcheck_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function healthcheck_civicrm_install() {
  _healthcheck_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function healthcheck_civicrm_enable() {
  _healthcheck_civix_civicrm_enable();
}

// --- Functions below this ship commented out. Uncomment as required. ---

/**
 * Implements hook_civicrm_preProcess().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_preProcess
 *

 // */

/**
 * Implements hook_civicrm_navigationMenu().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_navigationMenu
 */
function healthcheck_civicrm_navigationMenu(&$menu) {
  _healthcheck_civix_insert_navigation_menu($menu, 'Administer/Administration Console', array(
    'label' => E::ts('Health Check'),
    'name' => 'health_check',
    'url' => 'civicrm/healthcheck',
    'permission' => 'administer CiviCRM',
    'separator' => 0,
  ));
  _healthcheck_civix_navigationMenu($menu);
}
