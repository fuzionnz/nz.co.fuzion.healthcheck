<div class="row clear">
    <div id="dedupe-chart" class="col-md-12">
        <strong>Dedupe Count</strong><br /><br />
        <select class='crm-select2 crm-form-select' id='dedupe_rules' >
          <option value="">- Select dedupe rule-</option>
        </select>
        <span class="reset" style="display: none;">range: <span class="filter"></span></span>
        <a class="reset" href="javascript:dedupeChart.filterAll();dc.redrawAll();" style="display: none;">reset</a>
        <div class="clearfix"></div>
    </div>
</div>

<script>
    'use strict';
    var rules = {crmHCSQL file="dedupe_by_date" type='dedupe' context='build_element'};
    var data = {crmHCSQL file="dedupe_by_date" type='dedupe'};

    {literal}
    CRM.$(function($) {
      loadDupeElement(rules);

      $("#dedupe_rules").change(function() {
        var data = {/literal}{crmHCSQL file="dedupe_by_date" type='dedupe'}{literal};
        loadDedupeData(data, $(this).val());
      });
      function loadDupeElement(rules) {
        if (!rules.is_error) {
          rules.values.forEach(function(d) {
            $('#dedupe_rules').append(`<option value=` + d.id + `>` + d.title + ` </option>`);
          });
        }
        else{
          $('.eventsoverview').html('<div style="color:red; font-size:18px;">Civisualize Error. Please contact Admin.' + data.error + '</div>');
        }
      }

      function loadDedupeData(data, rule_id) {
        if (!data.is_error) {
            var numberFormat = d3.format(".2f");
            var dedupeChart = null;

            $(function($) {
                dedupeChart = dc.lineChart("#dedupe-chart");
                var dateFormat = d3.timeFormat("%Y-%m-%d");
                var dateFormatParse = d3.timeParse("%Y-%m-%d");
                var mydata = [];
                data.values.forEach(function(d) {
                  var value = JSON.parse(d.value);
                  if (d.rule_id != rule_id) {
                    return;
                  }
                  $.each(value, function( date_val, dupe_count ) {
                    var arr = {
                      dd: dateFormatParse(date_val),
                      dupe_count: dupe_count
                    };
                    mydata.push(arr);
                  });
                });

                var min = d3.min(mydata, function(d) { return d.dd;} );
                var max = d3.max(mydata, function(d) { return d.dd;} );
                var ndx = crossfilter(mydata);

                var byMonth     = ndx.dimension(function(d) { return d3.timeMonth(d.dd); });
                var byDay       = ndx.dimension(function(d) { return d.dd; });
                var dupeCountByDayGroup = byDay.group().reduceSum(function(d) { return d.dupe_count; });

                dedupeChart.width(850)
                    .height(200)
                    .transitionDuration(1000)
                    .margins({top: 30, right: 50, bottom: 25, left: 40})
                    .dimension(byDay)
                    .mouseZoomable(true)
                    .x(d3.scaleTime().domain([min,max]))
                    .xUnits(d3.timeMonths)
                    .elasticY(true)
                    .renderHorizontalGridLines(true)
                    .legend(dc.legend().x(800).y(10).itemHeight(13).gap(5))
                    .brushOn(false)
                    .group(dupeCountByDayGroup)
                    .valueAccessor(function (d) {
                        return d.value;
                    })
                    .title(function (d) {
                      var value = d.value;
                      if (isNaN(value)) value = 0;
                      return dateFormat(d.key) + "\n" + numberFormat(value);
                    });
                dc.renderAll();
            });//end $
        }
        else{
            $('.eventsoverview').html('<div style="color:red; font-size:18px;">Civisualize Error. Please contact Admin.'+data.error+'</div>');
        }
      }
    });

    {/literal}
</script>
<div class="clear"></div>