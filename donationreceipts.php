<?php
/*
    sfe.donationreceipts extension for CiviCRM
    Copyright (C) 2011,2012 digitalcourage e.V.
    Copyright (C) 2012 Software fuer Engagierte e.V.

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU Affero General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU Affero General Public License for more details.

    You should have received a copy of the GNU Affero General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

require_once 'donationreceipts.civix.php';

require_once 'backend.php';

// number of years for which the annual receipt is offered
define('DONATIONRECEIPTS_YEAR_COUNT', 5);

/**
 * Implementation of hook_civicrm_config
 */
function donationreceipts_civicrm_config(&$config) {
  _donationreceipts_civix_civicrm_config($config);
}

/**
 * Implementation of hook_civicrm_xmlMenu
 *
 * @param $files array(string)
 */
function donationreceipts_civicrm_xmlMenu(&$files) {
  _donationreceipts_civix_civicrm_xmlMenu($files);
}

/**
 * Implementation of hook_civicrm_install
 */
function donationreceipts_civicrm_install() {
  setup_custom_data();
  setup_template();
  return _donationreceipts_civix_civicrm_install();
}

/**
 * Implementation of hook_civicrm_uninstall
 */
function donationreceipts_civicrm_uninstall() {
  return _donationreceipts_civix_civicrm_uninstall();
}

/**
 * Implementation of hook_civicrm_enable
 */
function donationreceipts_civicrm_enable() {
  return _donationreceipts_civix_civicrm_enable();
}

/**
 * Implementation of hook_civicrm_disable
 */
function donationreceipts_civicrm_disable() {
  return _donationreceipts_civix_civicrm_disable();
}

/**
 * Implementation of hook_civicrm_upgrade
 *
 * @param $op string, the type of operation being performed; 'check' or 'enqueue'
 * @param $queue CRM_Queue_Queue, (for 'enqueue') the modifiable list of pending up upgrade tasks
 *
 * @return mixed  based on op. for 'check', returns array(boolean) (TRUE if upgrades are pending)
 *                for 'enqueue', returns void
 */
function donationreceipts_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _donationreceipts_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implementation of hook_civicrm_managed
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 */
function donationreceipts_civicrm_managed(&$entities) {
  return _donationreceipts_civix_civicrm_managed($entities);
}


function donationreceipts_civicrm_alterContent(&$content, $context, $tplName, &$object) {
  if ($tplName == 'CRM/Contact/Page/View/CustomData.tpl') {
    $result = civicrm_api("CustomGroup", "get", array('version' => '3', 'name' => CUSTOM_TABLE_NAME));
    if ($object->_groupId == $result['id']) {
      $bescheinigungen = array();
      for ($year = date("Y"); $year > date("Y")-DONATIONRECEIPTS_YEAR_COUNT; $year--) {
        $url = CRM_Utils_System::url("civicrm/donationreceipts/zuwendungsbescheinigung", "snippet=1&cid={$object->_contactId}&year=$year");
        $bescheinigungen[$year] = $url;
      }

      $template = CRM_Core_Smarty::singleton();
      $template->assign("bescheinigungen", $bescheinigungen);
      $content = $template->fetch("CRM/Donationreceipts/Page/ContactTab.tpl") . "<hr />" . $content;
    }
  }
}

function donationreceipts_civicrm_navigationMenu(&$menu) {
  /* Invoke the callback function on each existing menu item at any depth. */
  function menu_walk(&$menu, $callback) {
    foreach ($menu as &$item) {
      if (!empty($item['child']))
        menu_walk($item['child'], $callback);
      $callback($item);
    }
  }

  /* Add menu entry at end of "Contributions" submenu. */
  menu_walk($menu, function (&$submenu) {
    if ($submenu['attributes']['name'] == 'Contributions') {
      /* First, add separator after original last element. */
      if (!empty($submenu['child'])) {
        $last_key = array_pop(array_keys($submenu['child']));
        $submenu['child'][$last_key]['attributes']['separator'] = 1;
      }

      $submenu['child'][] = array(
        'attributes' => array(
          'label' => 'Jahresbescheinigungen',
          'name' => 'receipts_batch',
          'url' => 'civicrm/donationreceipts/jahresbescheinigungen/confirm',
          'active' => 1
        )
      );
    }
  });

}
