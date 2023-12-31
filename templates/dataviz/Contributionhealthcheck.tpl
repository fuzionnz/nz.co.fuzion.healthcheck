{crmTitle string="Contributions"}
<h3 id='filters'>Add Filters</h3>
<a id='1_month' class='button' href="#">Last Month</a>
<a id='3_month' class='button' href="#">Last 3 Months</a>
<a id='6_month' class='button' href='#'>Last 6 Months</a>
<a id='12_month' class='button' href='#'>Last 12 Months</a>
<a id='current_year' class='button' href='#'>Current Year</a>
<a id='previous_year' class='button' href='#'>Previous Year</a>
<br />
<br />
<br />

<h1><span id="nbcontrib"></span> Contributions for a total of <span id="amount"></span></h1>
<div class="row">
<div id="recur" class="col-md-4">
  <strong>Recurring</strong>
  <a class="reset" href="javascript:graphs.recur.filterAll();dc.redrawAll();" style="display: none;">reset</a>
  <graph />
  <div class="clearfix"></div>
</div>

<div id="contact_type" class="col-md-4 hidden">
  <strong>Type</strong>
  <a class="reset" href="javascript:pietype.filterAll();dc.redrawAll();" style="display: none;">reset</a>
  <div class="clearfix"></div>
</div>

<div id="instrument" class="col-md-4">
    <strong>Payment instrument</strong>
    <a class="reset" href="javascript:pieinstrument.filterAll();dc.redrawAll();" style="display: none;">reset</a>
    <div class="clearfix"></div>
</div>

<div id="day-of-week-chart" class="col-md-4">
    <strong>Day of Week</strong>
    <a class="reset" href="javascript:dayOfWeekChart.filterAll();dc.redrawAll();" style="display: none;">reset</a>
    <div class="clearfix"></div>
</div>
</div>
<div class="row clear">
    <div id="monthly-move-chart" class="col-md-12">
        <strong>Amount by month</strong>
        <span class="reset" style="display: none;">range: <span class="filter"></span></span>
        <a class="reset" href="javascript:moveChart.filterAll();volumeChart.filterAll();dc.redrawAll();" style="display: none;">reset</a>
        <div class="clearfix"></div>
    </div>
</div>

<div id="monthly-volume-chart" class="col-md-12"></div>

<div class="clear"></div>

<script>
    'use strict';
    var data = {crmHCSQL file="contribution_by_date-1_month"};

    var i = {crmAPI entity="OptionValue" option_group_id="10"}; {*todo on 4.4, use the payment_instrument as id *}
    loadContibutionData(data, i);

    {literal}
    cj('.button').removeClass('highlighted');
        cj(this).addClass('highlighted');
        cj('#filters').text("Add Filters (Current Selection = " + cj('#1_month').text() + ")");

      cj("#1_month").click(function() {
        cj('.button').removeClass('highlighted');
        cj(this).addClass('highlighted');
        cj('#filters').text("Add Filters (Current Selection = " + cj('#1_month').text() + ")");
        var data = {/literal}{crmHCSQL file="contribution_by_date-1_month"} {literal};
        loadContibutionData(data, i);
      });
      cj("#3_month").click(function() {
        cj('.button').removeClass('highlighted');
        cj(this).addClass('highlighted');
        cj('#filters').text("Add Filters (Current Selection = " + cj('#3_month').text() + ")");
        var data = {/literal}{crmHCSQL file="contribution_by_date-3_month"} {literal};
        loadContibutionData(data, i);
      });
      cj("#6_month").click(function() {
        cj('.button').removeClass('highlighted');
        cj(this).addClass('highlighted');
        cj('#filters').text("Add Filters (Current Selection = " + cj('#6_month').text() + ")");
        var data = {/literal}{crmHCSQL file="contribution_by_date-6_month"} {literal};
        loadContibutionData(data, i);
      });
      cj("#12_month").click(function() {
        cj('.button').removeClass('highlighted');
        cj(this).addClass('highlighted');
        cj('#filters').text("Add Filters (Current Selection = " + cj('#12_month').text() + ")");
        var data = {/literal}{crmHCSQL file="contribution_by_date-12_month"} {literal};
        loadContibutionData(data, i);
      });
      cj("#current_year").click(function() {
        cj('.button').removeClass('highlighted');
        cj(this).addClass('highlighted');
        cj('#filters').text("Add Filters (Current Selection = " + cj('#current_year').text() + ")");
        var data = {/literal}{crmHCSQL file="contribution_by_date-current_year"} {literal};
        loadContibutionData(data, i);
      });
      cj("#previous_year").click(function() {
        cj('.button').removeClass('highlighted');
        cj(this).addClass('highlighted');
        cj('#filters').text("Add Filters (Current Selection = " + cj('#previous_year').text() + ")");
        var data = {/literal}{crmHCSQL file="contribution_by_date-previous_year"} {literal};
        loadContibutionData(data, i);
      });



      function loadContibutionData(data, i) {
        if (!data.is_error) {
            var instrumentLabel = {};
            i.values.forEach (function(d) {
                instrumentLabel[d.value] = d.label;
            });

            var numberFormat = d3.format(".2f");
            var graphs = {};
            var volumeChart=null,dayOfWeekChart=null,moveChart=null,pieinstrument,pietype;

            cj(function($) {

              function drawRecur(dom){
                var dim = ndx.dimension(function(d) {return d.recurring;});
                var group = dim.group().reduceSum(function(d) { return d.count; });
                var graph = dc.pieChart(dom)
                  .innerRadius(20)
                  .radius(90)
                  .dimension(dim)
                  .group(group)
                  .label(function (d) { return d.key? "Recurring":"One Off";})
                  .title(function (d) { return (d.key? "Recurring":"One Off") +": "+d.value;});
                ;
                return graph;
              }

                // create a pie chart under #chart-container1 element using the default global chart group
                pietype = dc.pieChart("#type").innerRadius(20).radius(90);
                pieinstrument = dc.pieChart("#instrument").innerRadius(50).radius(90);
                volumeChart = dc.barChart("#monthly-volume-chart");
                dayOfWeekChart = dc.rowChart("#day-of-week-chart");
                //var moveChart = dc.seriesChart("#monthly-move-chart");
                moveChart = dc.lineChart("#monthly-move-chart");
                var dateFormat = d3.timeFormat("%Y-%m-%d");
                var dateFormatParse = d3.timeParse("%Y-%m-%d");
                //data.values.forEach(function(d){data.values[i].dd = new Date(d.receive_date)});

                data.values.forEach(function(d){d.dd = dateFormatParse(d.receive_date)});
                var min = d3.min(data.values, function(d) { return d.dd;} );
                var max = d3.max(data.values, function(d) { return d.dd;} );
                var ndx                 = crossfilter(data.values),
                all = ndx.groupAll();

                var type        = ndx.dimension(function(d) {return d.financial_type;});
                var typeGroup   = type.group().reduceSum(function(d) { return d.count; });

                var instrument        = ndx.dimension(function(d) {return d.instrument;});
                var instrumentGroup   = instrument.group().reduceSum(function(d) { return d.count; });

                var byMonth     = ndx.dimension(function(d) { return d3.timeMonth(d.dd); });
                var byDay       = ndx.dimension(function(d) { return d.dd; });
                var volumeByMonthGroup  = byMonth.group().reduceSum(function(d) { return d.count; });
                var totalByDayGroup     = byDay.group().reduceSum(function(d) { return d.total; });

                var dayOfWeek = ndx.dimension(function (d) {
                    var day = d.dd.getDay();
                    var name=["Sun","Mon","Tue","Wed","Thu","Fri","Sat"];
                    return day+"."+name[day];
                });


                var group=ndx.groupAll().reduce(
                    function(a, d) {
                        a.total += d.total;
                        a.count += d.count;
                        return a;
                    },
                    function(a, d) {
                        a.total -= d.total;
                        a.count -= d.count;
                        return a;
                    },
                    function() {
                        return {total:0, count:0};
                    }
                );

                var contribND   = dc.numberDisplay("#nbcontrib")
                    .group(group)
                    .valueAccessor(function (d) {
                    return d.count;})
                    .formatNumber(d3.format("3.3s"));

                var amountND    = dc.numberDisplay("#amount")
                    .group(group)
                    .valueAccessor(function(d) {return d.total});



                var dayOfWeekGroup = dayOfWeek.group();

                dayOfWeekChart.width(300)
                    .height(220)
                    .margins({top: 20, left: 10, right: 10, bottom: 20})
                    .group(dayOfWeekGroup)
                    .dimension(dayOfWeek)
                    .ordinalColors(["#d95f02","#1b9e77","#7570b3","#e7298a","#66a61e","#e6ab02","#a6761d"])
                    .label(function (d) {
                        return d.key.split(".")[1];
                    })
                    .title(function (d) {
                        return d.value;
                    })
                    .elasticX(true)
                    .xAxis().ticks(4);


                pieinstrument
                    .width(200)
                    .height(200)
                    .dimension(instrument)
                    .group(instrumentGroup)
                    .title(function(d) {
                        return instrumentLabel[d.key]+":"+d.value;
                    })
                    .label(function(d) {
                        return instrumentLabel[d.key];
                    })
                    .on('renderlet', function (chart) {
                    });

                pietype
                    .width(200)
                    .height(200)
                    .dimension(type)
                    .colors(d3.scaleOrdinal(d3.schemeCategory10)())
                    .group(typeGroup)
                    .on('renderlet', function (chart) {
                    });
                //.round(d3.timeMonth.round)
                //.interpolate('monotone')
                moveChart.width(850)
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
                    .rangeChart(volumeChart)
                    .group(totalByDayGroup)
                    .valueAccessor(function (d) {
                        return d.value;
                    })
                    .title(function (d) {
                        var value = d.value;
                        if (isNaN(value)) value = 0;
                        return dateFormat(d.key) + "\n" + numberFormat(value);
                    });

                volumeChart.width(850)
                    .height(200)
                    .margins({top: 0, right: 50, bottom: 20, left:40})
                    .dimension(byMonth)
                    .group(volumeByMonthGroup)
                    .centerBar(true)
                    .gap(1)
                    .x(d3.scaleTime().domain([min, max]))
                    .round(d3.timeMonth.round)
                    .xUnits(d3.timeMonths);

                graphs.recur=drawRecur("#recur graph");
                dc.renderAll();
                //  pietype.render();
            });//end cj
        }
        else{
            cj('.eventsoverview').html('<div style="color:red; font-size:18px;">Civisualize Error. Please contact Admin.'+data.error+'</div>');
        }
    }
    {/literal}
</script>
<div class="clear"></div>