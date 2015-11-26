<script type="text/javascript"
	  src="https://www.google.com/jsapi?autoload={
		'modules':[{
		  'name':'visualization',
		  'version':'1',
		  'packages':['corechart']
		}]
	  }"></script>

<script type="text/javascript">
  google.setOnLoadCallback(drawChart);
  google.setOnLoadCallback(drawPie);

  function drawChart() {
	var data = google.visualization.arrayToDataTable(<?php echo json_encode($weekly_graph_data); ?>);

	// :TODO: Highlight points of the graph
	var options = {
		title: 'Weekly <?php echo $page_title ?>',
		vAxis: {
			viewWindow: {
				max:100,
				min:0
			}
		}
	};
	
	var chart = new google.visualization.LineChart(document.getElementById('curve_chart'));
	chart.draw(data, options);
  }

function drawPie() {
	var data = google.visualization.arrayToDataTable(<?php echo json_encode($annual_graph_data); ?>);

	var options = {
		title: 'Annual <?php echo $page_title ?>',
	};
	var chart = new google.visualization.PieChart(document.getElementById('pie_chart'));
	chart.draw(data, options);
  }
</script>

<h1><?php echo $page_title ?></h1>

<?php include('_filter.php'); ?>

<div id="curve_chart" style="width: 60%; height: 300px; float:left;"></div>
<div id="pie_chart" style="width: 35%; height: 300px; float:left;"></div>
