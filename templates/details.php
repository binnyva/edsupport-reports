<?php
if(!isset($colors)) $colors = array('#ff9800', '#4caf50', '#3f51b5', '#f44336');
$slice_colors = array();
for($i=0; $i<count($colors); $i++) $slice_colors[$i] = array('color' => $colors[$i]);

?><script type="text/javascript"
	  src="https://www.google.com/jsapi?autoload={
		'modules':[{
		  'name':'visualization',
		  'version':'1',
		  'packages':['corechart']
		}]
	  }"></script>

<script type="text/javascript">
<?php if($overall[1][1] or $overall[2][1] or $overall[3][1]) { ?>
google.setOnLoadCallback(drawPieOverall);
function drawPieOverall() {
	var data = google.visualization.arrayToDataTable(<?php echo json_encode($overall); ?>);

	var options = {
		title: 'Overall',
		slices: [{"color": "#3f51b5"}, {"color": "#4caf50"}, {"color": "#f44336"}]
	};
	var chart = new google.visualization.PieChart(document.getElementById('pie_chart_overall'));
	chart.draw(data, options);
}
<?php } ?>

<?php if($internal[1][1] or $internal[2][1] or $internal[3][1]) { ?>
google.setOnLoadCallback(drawPieInternal);
function drawPieInternal() {
	var data = google.visualization.arrayToDataTable(<?php echo json_encode($internal); ?>);

	var options = {
		title: 'Internal Factors',
		slices: [{"color": "#4caf50"}, {"color": "#8bc34a"}, {"color": "#cddc39"}, {"color": "#009688"}, {"color": "#ffeb3b"}]
	};
	var chart = new google.visualization.PieChart(document.getElementById('pie_chart_internal'));
	chart.draw(data, options);
}
<?php } ?>

<?php if($external[1][1] or $external[2][1] or $external[3][1]) { ?>
google.setOnLoadCallback(drawPieExternal);
function drawPieExternal() {
	var data = google.visualization.arrayToDataTable(<?php echo json_encode($external); ?>);

	var options = {
		title: 'External Factors',
		slices: [{"color": "#3f51b5"}, {"color": "#2196f3"}, {"color": "#03a9f4"}, {"color": "#00bcd4"}]
	};
	var chart = new google.visualization.PieChart(document.getElementById('pie_chart_external'));
	chart.draw(data, options);
}
<?php } ?>
</script>

<h1>Class Cancellation Drill Down <?php echo ($center_name) ? " for $center_name" : (($city_name) ? " in $city_name" : ''); ?></h1>

<div id="pie_chart_overall" style="height: 200px;">No data available.</div><br />
<div id="pie_chart_internal" style="width:45%; float:left;"></div>
<div id="pie_chart_external" style="width:45%; float:left;"></div>

<br />