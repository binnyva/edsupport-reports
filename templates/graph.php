<?php
if(!isset($colors)) $colors = array('red', 'orange', 'green');

// If no city or center is selected, don't show the national average. That is whats shown by default.
if(!$city_id and !$center_id and 
		isset($weekly_graph_data[0][2]) and !isset($weekly_graph_data[0][3])) {
	for($i=0; $i<count($weekly_graph_data);$i++)
		unset($weekly_graph_data[$i][2]);
}

?><script type="text/javascript"
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
		},
		colors: <?php echo json_encode($colors) ?>
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
		slices: <?php 
			$slice_colors = array();
			for($i=0; $i<count($colors); $i++) $slice_colors[$i] = array('color' => $colors[$i]);
			echo json_encode($slice_colors);
		?>
	};
	var chart = new google.visualization.PieChart(document.getElementById('pie_chart'));
	chart.draw(data, options);
  }
</script>

<h1><?php echo $page_title ?> Report</h1>

<?php include('_filter.php'); ?>

<?php if(isset($adoption)) { ?>
<div id="progress">
<div id="data" style="width:<?php echo $adoption ?>%; background-color: green;">Adoption Persentage: <?php echo $adoption ?>%</div>
<div id="no-data" style="width:<?php echo 100-$adoption ?>%; background-color: red;">&nbsp;</div>
</div>
<?php } ?>
<br /><?php
if(isset($listing_link)) echo "<a href='$listing_link'>" . (isset($listing_text) ? $listing_text : 'List All') . "</a><br />";
?>
<div id="curve_chart"></div>
<div id="pie_chart"></div>

