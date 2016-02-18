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
google.setOnLoadCallback(drawPieOverall);
function drawPieOverall() {
	var data = google.visualization.arrayToDataTable(<?php echo json_encode($overall); ?>);

	var options = {
		title: 'Overall',
		slices: <?php echo json_encode($slice_colors); ?>
	};
	var chart = new google.visualization.PieChart(document.getElementById('pie_chart_overall'));
	chart.draw(data, options);
}

google.setOnLoadCallback(drawPieInternal);
function drawPieInternal() {
	var data = google.visualization.arrayToDataTable(<?php echo json_encode($internal); ?>);

	var options = {
		title: 'Internal Factors',
		slices: <?php echo json_encode($slice_colors); ?>
	};
	var chart = new google.visualization.PieChart(document.getElementById('pie_chart_internal'));
	chart.draw(data, options);
}

google.setOnLoadCallback(drawPieExternal);
function drawPieExternal() {
	var data = google.visualization.arrayToDataTable(<?php echo json_encode($external); ?>);

	var options = {
		title: 'External Factors',
		slices: <?php echo json_encode($slice_colors); ?>
	};
	var chart = new google.visualization.PieChart(document.getElementById('pie_chart_external'));
	chart.draw(data, options);
}

</script>

<h1>Class Cancellation Drill Down <?php echo ($center_name) ? " for $center_name" : (($city_name) ? " in $city_name" : ''); ?></h1>

<div id="pie_chart_overall"></div><br />
<div id="pie_chart_internal" style="width:45%; float:left;"></div>
<div id="pie_chart_external" style="width:45%; float:left;"></div>

