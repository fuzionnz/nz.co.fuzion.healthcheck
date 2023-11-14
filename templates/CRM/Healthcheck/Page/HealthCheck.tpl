<h3 id='filters'>Add Filters</h3>
<a id='1_month' class='button' href='healthcheck?month_count=1'>Last Month</a>
<a id='3_month' class='button' href='healthcheck?month_count=3'>Last 3 Months</a>
<a id='6_month' class='button' href='healthcheck?month_count=6'>Last 6 Months</a>
<a id='12_month' class='button' href='healthcheck?month_count=12'>Last 12 Months</a>
<a id='current_year' class='button' href='healthcheck?year=current'>Current Year</a>
<a id='previous_year' class='button' href='healthcheck?year=previous'>Previous Year</a>
<br />
<br />
<br />

<h3>Email Stats</h3>
<table>
  <thead>
    <th>Criteria</th>
    <th>Count</th>
    <th>Total</th>
    <th>Percent</th>
  </thead>
  <body>
    <tr>
      <td width = '600px'>On hold Emails</td>
      <td>{$onholdCount.count}</td>
      <td>{$onholdCount.total}</td>
      <td>{$onholdCount.percent}</td>
    </tr>
    <tr>
      <td>Unsubscribe events</td>
      <td>{$optOut.count}</td>
      <td>{$optOut.total}</td>
      <td>{$optOut.percent}</td>
    </tr>
    <tr>
      <td>Mail Opened</td>
      <td>{$openEventCount.count}</td>
      <td>{$openEventCount.total}</td>
      <td>{$openEventCount.percent}</td>
    </tr>
    <tr>
      <td>Mail Clicked</td>
      <td>{$clickEventCount.count}</td>
      <td>{$clickEventCount.total}</td>
      <td>{$clickEventCount.percent}</td>
    </tr>
  </body>
</table>
<br />
<br />
<h3>Dedupe Stats</h3>
<table>
  <thead>
    <th>Criteria</th>
    <th>Count</th>
    <th>Total</th>
    <th>Percent</th>
  </thead>
  <body>
    <tr>
      <td width = '600px'>Contacts matching on Emails</td>
      <td>{$percentMatch.count}</td>
      <td>{$percentMatch.total}</td>
      <td>{$percentMatch.percent}</td>
    </tr>
    <tr>
      <td>Contacts matching on First/Last Name</td>
      <td>{$firstLastName.count}</td>
      <td>{$firstLastName.total}</td>
      <td>{$firstLastName.percent}</td>
    </tr>
  </body>
</table>
<br />
<br />
<h3>Contributions</h3>
<table>
  <thead>
    <th>Contribution Status</th>
    <th>Count</th>
    <th>Total</th>
    <th>Percent</th>
  </thead>
  <body>
    <tr>
      <td width = '600px'>Completed</td>
      <td>{$completedContributions.count}</td>
      <td>{$completedContributions.total}</td>
      <td>{$completedContributions.percent}</td>
    </tr>
    <tr>
      <td>Pending</td>
      <td>{$pendingContributions.count}</td>
      <td>{$pendingContributions.total}</td>
      <td>{$pendingContributions.percent}</td>
    </tr>
    <tr>
      <td>Failed</td>
      <td>{$failedContributions.count}</td>
      <td>{$failedContributions.total}</td>
      <td>{$failedContributions.percent}</td>
    </tr>
  </body>
</table>
<br />
<br />
<h3>Spam Contacts</h3>
<table>
  <thead>
    <th>Conditions</th>
    <th>Count</th>
    <th>Total</th>
    <th>Percent</th>
  </thead>
  <body>
    <tr>
      <td width = '600px'>First Name = Last Name</td>
      <td>{$spamFirstLastName.count}</td>
      <td>{$spamFirstLastName.total}</td>
      <td>{$spamFirstLastName.percent}</td>
    </tr>
    <tr>
      <td width = '600px'>Emails ending with .ru</td>
      <td>{$spamEmailEnding.count}</td>
      <td>{$spamEmailEnding.total}</td>
      <td>{$spamEmailEnding.percent}</td>
    </tr>
  </body>
</table>

<br />
<br />
<h3>List of Contribution Pages with COUNT of contributions made through them</h3>
<table>
  <thead>
    <th>Contribution Pages</th>
    <th>PageId</th>
    <th>Total</th>
    <th>Completed</th>
    <th>Pending</th>
    <th>Failed</th>
  </thead>
  <body>
    {foreach from=$contributionCountOnPages item=pageStats}
      <tr>
        <td width = '600px'>
          <a href="{crmURL p='civicrm/admin/contribute/settings' q="reset=1&action=update&id=`$pageStats.page_id`"}">
            {$pageStats.title}
          </a>
        </td>
        <td>{$pageStats.page_id}</td>
        <td>{$pageStats.count}</td>
        <td>{$pageStats.completed_count}</td>
        <td>{$pageStats.pending_count}</td>
        <td>{$pageStats.failed_count}</td>
      </tr>
    {/foreach}
  </body>
</table>

{if $extensionUpgrades}
  <br />
  <br />
  <h3>Extension Upgrades</h3>
  <table>
    <thead>
      <th>Extension Name</th>
      <th>Current Version</th>
      <th>Available Version</th>
    </thead>
    <body>
      {foreach from=$extensionUpgrades item=ext}
        <tr>
          <td width = '600px'>{$ext.name}</td>
          <td>{$ext.current_ver}</td>
          <td>{$ext.updated_ver}</td>
        </tr>
      {/foreach}
    </body>
  </table>
{/if}

<h3>Membership Churn</h3>

{if $membershipChurnPresent}
  {capture assign="chartURL"}{crmURL p="civicrm/membership/membershipchurnchart" q=""}{/capture}
  <p><a href = "{$chartURL}">Click here</a> to view Membership Churn Report.</p>
{else}
  <p>{ts}Looks like Membership Churn Chart extension is not installed on your site. If you are interested in tracking membership churn and renewal's please <a href="https://www.fuzion.co.nz/how-can-we-help">contact us</a> to find out more.{/ts}</p>
{/if}

{literal}
<script>
CRM.$(function($) {
  var month_count = {/literal}{if $month_count} {$month_count} {else} '' {/if} {literal};
  var year = {/literal}{if $year} '{$year}' {else} '' {/if} {literal};

  $('.button').removeClass('highlighted');
  if (month_count) {
    $('#' + month_count + '_month').addClass('highlighted');
    $('#filters').text("Add Filters (Current Selection = " + $('#' + month_count + '_month').text() + ")");
  }
  else if (year) {
    $('#' + year + '_year').addClass('highlighted');
    $('#filters').text("Add Filters (Current Selection = " + $('#' + year + '_year').text() + ")");
  }
});
</script>
{/literal}