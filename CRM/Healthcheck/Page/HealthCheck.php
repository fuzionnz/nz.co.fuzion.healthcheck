<?php
use CRM_Healthcheck_ExtensionUtil as E;

class CRM_Healthcheck_Page_HealthCheck extends CRM_Core_Page {

  public function run() {
    $smarty= CRM_Core_Smarty::singleton();
    CRM_Core_Resources::singleton()->addStyleFile('nz.co.fuzion.healthcheck', 'css/healthcheck.css');
    $monthCount = $year = $yearVal = NULL;
    // Example: Set the page-title dynamically; alternatively, declare a static title in xml/Menu/*.xml
    CRM_Utils_System::setTitle(E::ts('Health Check'));
    $yearVal = CRM_Utils_Request::retrieve('year', 'String');
    $monthCount = CRM_Utils_Request::retrieve('month_count', 'Int');
    if ($yearVal == 'current') {
      $year = date('Y');
    }
    elseif ($yearVal == 'previous') {
      $year = date('Y') - 1;
    }
    if (empty($monthCount) && empty($year)) {
      $monthCount = 1;
    }
    $this->assign('month_count', $monthCount);
    $this->assign('year', $yearVal);

    $this->assign('optOut', $this->unsubscribeCount($monthCount, $year));
    $this->assign('openEventCount', $this->openEventCount($monthCount, $year));
    $this->assign('clickEventCount', $this->clickEventCount($monthCount, $year));
    $this->assign('onholdCount', $this->onholdCount($monthCount, $year));
    $this->assign('percentMatch', $this->matchingEmail());
    $this->assign('firstLastName', $this->matchingOnFirstLastName());
    $this->assign('completedContributions', $this->completedContributions($monthCount, $year));
    $this->assign('pendingContributions', $this->completedContributions($monthCount, $year, 2));
    $this->assign('failedContributions', $this->completedContributions($monthCount, $year, 4));
    $this->assign('spamFirstLastName', $this->spamFirstLastName());
    $this->assign('spamEmailEnding', $this->spamEmailEnding());
    $this->assign('contributionCountOnPages', $this->contributionCountOnPages($monthCount, $year));
    $this->assign('extensionUpgrades', $this->checkExtensionUpgrades());
    $this->assign('membershipChurnPresent', $this->membershipChurnPresent());

    parent::run();
  }

  public function membershipChurnPresent() {
    $dao = CRM_Core_DAO::singleValueQuery("SHOW TABLES LIKE 'membership_churn_table'");
    if ($dao) {
      return TRUE;
    }
    return FALSE;
  }

  public function checkExtensionUpgrades() {
    $extensions = [];
    $obj = new CRM_Admin_Page_Extensions();
    $localExtensionRows = $obj->formatLocalExtensionRows();
    $remoteExtensionRows = $obj->formatRemoteExtensionRows($localExtensionRows);
    foreach ($remoteExtensionRows as $key => $value) {
      if (!empty($value['is_upgradeable'])) {
        $extensions[$key] = [
          'name' => $value['label'],
          'current_ver' => $localExtensionRows[$key]['version'],
          'updated_ver' => $value['version'],
        ];
      }
    }
    return $extensions;
  }

  public function contributionCountOnPages($monthCount, $year) {
    $filterDate = date('Y') . '-01-01';
    if (!empty($year)) {
      $filterDate = $year . '-01-01';
    }
    elseif (!empty($monthCount)) {
      $filterDate = date("Y-m-d", strtotime("-{$monthCount} Months"));
    }
    $pages = civicrm_api3('ContributionPage', 'get', [
      'sequential' => 1,
      'is_active' =>1,
      'return' => ["id", "title"],
      'options' => ['limit' => 0],
    ]);
    $pageStats = [];
    foreach ($pages['values'] as $page) {
      $totalCount = civicrm_api3('Contribution', 'getcount', [
        'contribution_page_id' => $page['id'],
        'receive_date' => ['>=' => $filterDate],
        'options' => ['limit' => 0],
      ]);
      $pendingCount = civicrm_api3('Contribution', 'getcount', [
        'contribution_page_id' => $page['id'],
        'contribution_status_id' => "Pending",
        'receive_date' => ['>=' => $filterDate],
        'options' => ['limit' => 0],
      ]);
      $failedCount = civicrm_api3('Contribution', 'getcount', [
        'contribution_page_id' => $page['id'],
        'contribution_status_id' => "Failed",
        'receive_date' => ['>=' => $filterDate],
        'options' => ['limit' => 0],
      ]);
      $completedCount = civicrm_api3('Contribution', 'getcount', [
        'contribution_page_id' => $page['id'],
        'contribution_status_id' => "Completed",
        'receive_date' => ['>=' => $filterDate],
        'options' => ['limit' => 0],
      ]);
      $pageStats[$page['id']] = [
        'page_id' => $page['id'],
        'title' => $page['title'],
        'count' => $totalCount,
        'failed_count' => $failedCount,
        'pending_count' => $pendingCount,
        'completed_count' => $completedCount,
      ];
    }
    return $pageStats;
  }

  public function spamFirstLastName() {
    $matchingRecords = CRM_Core_DAO::singleValueQuery("select count(*) from civicrm_contact where first_name = last_name AND is_deleted = 0");
    $totalRecords = CRM_Core_DAO::singleValueQuery("select count(*) from civicrm_contact WHERE is_deleted = 0");

    $percent = 0;
    if (!empty($totalRecords)) {
      $percent = round(($matchingRecords/$totalRecords) * 100) . "%";
    }

    return [
      'count' => $matchingRecords,
      'total' => $totalRecords,
      'percent' => $percent,
    ];
  }

  public function spamEmailEnding() {
    $matchingRecords = CRM_Core_DAO::singleValueQuery("select COUNT(cc.id) from civicrm_contact cc LEFT JOIN civicrm_email ce ON cc.id = ce.contact_id where ce.email LIKE '%.ru'  AND cc.is_deleted = 0");
    $totalRecords = CRM_Core_DAO::singleValueQuery("select count(*) from civicrm_contact WHERE is_deleted = 0");

    $percent = 0;
    if (!empty($totalRecords)) {
      $percent = round(($matchingRecords/$totalRecords) * 100) . "%";
    }

    return [
      'count' => $matchingRecords,
      'total' => $totalRecords,
      'percent' => $percent,
    ];
  }

  public function unsubscribeCount($monthCount, $year) {
    $where = $totalWhere = '';
    if (!empty($year)) {
      $where = "where YEAR(cmeu.time_stamp) = {$year}";
      $totalWhere = "where YEAR(delivered.time_stamp) = {$year}";
    }
    elseif (!empty($monthCount)) {
      $where = "where cmeu.time_stamp >= DATE_SUB(now(), INTERVAL {$monthCount} MONTH)";
      $totalWhere = "where delivered.time_stamp >= DATE_SUB(now(), INTERVAL {$monthCount} MONTH)";
    }

    $matchingRecords = CRM_Core_DAO::singleValueQuery("select COUNT(cmeq.email_id) from
      civicrm_mailing_event_unsubscribe cmeu
      left join civicrm_mailing_event_queue cmeq on cmeu.`event_queue_id` = cmeq.id
      left join civicrm_email ce on cmeq.email_id = ce.id
      {$where}");

    $totalRecords = CRM_Core_DAO::singleValueQuery("SELECT COUNT(*) FROM (select queue.email_id, ce.email from
      civicrm_mailing_event_delivered delivered
      left join civicrm_mailing_event_queue queue on delivered.`event_queue_id` = queue.id
      left join civicrm_email ce on queue.email_id = ce.id
      {$totalWhere}
      GROUP BY queue.email_id) as a");

    $percent = 0;
    if (!empty($totalRecords)) {
      $percent = round(($matchingRecords/$totalRecords) * 100) . "%";
    }

    return [
      'count' => $matchingRecords,
      'total' => "{$totalRecords} (Delivered Mails)",
      'percent' => $percent,
    ];
  }

  public function openEventCount($monthCount, $year) {
    $where = $totalWhere = '';
    if (!empty($year)) {
      $where = "where YEAR(opened.time_stamp) = {$year}";
      $totalWhere = "where YEAR(delivered.time_stamp) = {$year}";
    }
    elseif (!empty($monthCount)) {
      $where = "where opened.time_stamp >= DATE_SUB(now(), INTERVAL {$monthCount} MONTH)";
      $totalWhere = "where delivered.time_stamp >= DATE_SUB(now(), INTERVAL {$monthCount} MONTH)";
    }

    $matchingRecords = CRM_Core_DAO::singleValueQuery("SELECT COUNT(*) FROM (select queue.email_id, ce.email from
      civicrm_mailing_event_opened opened
      left join civicrm_mailing_event_queue queue on opened.`event_queue_id` = queue.id
      left join civicrm_email ce on queue.email_id = ce.id
      {$where}  AND queue.id IN (SELECT delivered.event_queue_id from
      civicrm_mailing_event_delivered delivered
      left join civicrm_mailing_event_queue queue on delivered.`event_queue_id` = queue.id {$totalWhere})
      GROUP BY queue.email_id) as a");

    $totalRecords = CRM_Core_DAO::singleValueQuery("SELECT COUNT(*) FROM (select queue.email_id, ce.email from
      civicrm_mailing_event_delivered delivered
      left join civicrm_mailing_event_queue queue on delivered.`event_queue_id` = queue.id
      left join civicrm_email ce on queue.email_id = ce.id
      {$totalWhere}
      GROUP BY queue.email_id) as a");

    $percent = 0;
    if (!empty($totalRecords)) {
      $percent = round(($matchingRecords/$totalRecords) * 100) . "%";
    }

    return [
      'count' => $matchingRecords,
      'total' => "{$totalRecords} (Delivered Mails)",
      'percent' => $percent,
    ];
  }

  public function clickEventCount($monthCount, $year) {
    $where = $totalWhere = '';
    if (!empty($year)) {
      $where = "where YEAR(url_click.time_stamp) = {$year}";
      $totalWhere = "where YEAR(mailing.scheduled_date) = {$year}";
    }
    elseif (!empty($monthCount)) {
      $where = "where url_click.time_stamp >= DATE_SUB(now(), INTERVAL {$monthCount} MONTH)";
      $totalWhere = "where mailing.scheduled_date >= DATE_SUB(now(), INTERVAL {$monthCount} MONTH)";
    }

    $matchingRecords = CRM_Core_DAO::singleValueQuery("SELECT COUNT(*) FROM (select queue.email_id, ce.email from
      civicrm_mailing_event_trackable_url_open url_click
      left join civicrm_mailing_event_queue queue on url_click.`event_queue_id` = queue.id
      left join civicrm_email ce on queue.email_id = ce.id
      {$where}
      GROUP BY queue.email_id) as a
      ");

    $totalRecords = CRM_Core_DAO::singleValueQuery("
      select count(mailing.id)
      from civicrm_mailing_trackable_url url
      left join civicrm_mailing mailing on mailing.id = url.mailing_id
      {$totalWhere}
    ");
    $percent = 0;
    if (!empty($totalRecords)) {
      $percent = round(($matchingRecords/$totalRecords) * 100) . "%";
    }

    return [
      'count' => $matchingRecords,
      'total' => "{$totalRecords} (Trackable URLs sent in mails)",
      'percent' => $percent,
    ];
  }

  public function onholdCount($monthCount, $year) {
    $additionalWhere = '';
    if (!empty($year)) {
      $additionalWhere = "AND YEAR(hold_date) = {$year}";
    }
    elseif (!empty($monthCount)) {
      $additionalWhere = "AND hold_date >= DATE_SUB(now(), INTERVAL {$monthCount} MONTH)";
    }

    $matchingRecords = CRM_Core_DAO::singleValueQuery("select COUNT(*) from civicrm_email where on_hold = 1 {$additionalWhere}");
    $totalRecords = CRM_Core_DAO::singleValueQuery("select COUNT(*) from civicrm_email");

    $percent = 0;
    if (!empty($totalRecords)) {
      $percent = round(($matchingRecords/$totalRecords) * 100) . "%";
    }

    return [
      'count' => $matchingRecords,
      'total' => "{$totalRecords} (Email Records)",
      'percent' => $percent,
    ];
  }

  public function matchingEmail() {
    $matchingEmails = CRM_Core_DAO::singleValueQuery("SELECT COUNT(*) FROM (SELECT email, count(*) AS c
      FROM civicrm_email
      GROUP BY email
      HAVING c > 1
      ORDER BY c DESC) as a");
    $totalEmails = CRM_Core_DAO::singleValueQuery("SELECT COUNT(id) FROM civicrm_email");

    $percent = 0;
    if (!empty($totalEmails)) {
      $percent = round(($matchingEmails/$totalEmails) * 100) . "%";
    }

    return [
      'count' => $matchingEmails,
      'total' => "{$totalEmails} (Email Records)",
      'percent' => $percent,
    ];
  }

  public function matchingOnFirstLastName() {
    $matchingRecords = CRM_Core_DAO::singleValueQuery("SELECT COUNT(*) FROM (SELECT count(*) AS c
      FROM civicrm_contact
      WHERE first_name IS NOT NULL AND last_name IS NOT NULL AND is_deleted = 0
      GROUP BY first_name, last_name
      HAVING c > 1
      ORDER BY c DESC) as a");
    $totalRecords = CRM_Core_DAO::singleValueQuery("select count(*) from civicrm_contact WHERE is_deleted = 0");

    $percent = 0;
    if (!empty($totalRecords)) {
      $percent = round(($matchingRecords/$totalRecords) * 100) . "%";
    }

    return [
      'count' => $matchingRecords,
      'total' => "{$totalRecords} (Contact Records)",
      'percent' => $percent,
    ];
  }

  public function completedContributions($monthCount, $year, $status = 1) {
    $additionalWhere = '';
    if (!empty($year)) {
      $additionalWhere = "AND YEAR(receive_date) = {$year}";
    }
    elseif (!empty($monthCount)) {
      $additionalWhere = "AND receive_date >= DATE_SUB(now(), INTERVAL {$monthCount} MONTH)";
    }

    $matchingRecords = CRM_Core_DAO::singleValueQuery("
      select count(id)
      from civicrm_contribution
      where contribution_status_id = {$status} AND is_test != 1
      {$additionalWhere}");

    $totalRecords = CRM_Core_DAO::singleValueQuery("select count(id) from civicrm_contribution where is_test != 1 {$additionalWhere}");

    $percent = 0;
    if (!empty($totalRecords)) {
      $percent = round(($matchingRecords/$totalRecords) * 100) . "%";
    }

    return [
      'count' => $matchingRecords,
      'total' => $totalRecords,
      'percent' => $percent,
    ];
  }
}
