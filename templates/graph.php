<script type="text/javascript"
	  src="https://www.google.com/jsapi?autoload={
		'modules':[{
		  'name':'visualization',
		  'version':'1',
		  'packages':['corechart']
		}]
	  }"></script>

<script type="text/javascript">
  <?php if($weekly_graph_data) { ?>
  google.setOnLoadCallback(drawChart);
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
  <?php } ?>

google.setOnLoadCallback(drawPie);
function drawPie() {
	var data = google.visualization.arrayToDataTable(<?php echo json_encode($annual_graph_data); ?>);

	var options = {
		title: 'Annual <?php echo $page_title ?>',
		slices: {
            0: { color: 'green' },
            1: { color: 'red' },
            2: { color: 'blue' }
          }
	};
	var chart = new google.visualization.PieChart(document.getElementById('pie_chart'));
	chart.draw(data, options);
  }
</script>

<h1><?php echo $page_title ?> Report</h1>

<?php include('_filter.php'); ?>

<div id="curve_chart"></div>
<div id="pie_chart"></div>

<br /><?php if(isset($listing_link)) echo "<a href='$listing_link'>List All</a>";