<script type="text/javascript"
	  src="https://www.google.com/jsapi?autoload={
		'modules':[{
		  'name':'visualization',
		  'version':'1',
		  'packages':['corechart']
		}]
	  }"></script>

<script type="text/javascript">
google.setOnLoadCallback(drawPie);
 
function drawPie() {
	var data = google.visualization.arrayToDataTable([
		['Year', 'Credit Status'],
		['Zero Or Below',	<?php echo $annual_data['zero_or_below_percentage'] ?>],
		['One/Two Credit',	<?php echo $annual_data['one_or_two_percentage'] ?>],
		['Three or More',	<?php echo $annual_data['three_or_more_percentage'] ?>],
	]);

	var options = {
		title: 'Credit Status',
	};
	var chart = new google.visualization.PieChart(document.getElementById('pie_chart'));
	chart.draw(data, options);
  }


</script>
<div id="curve_chart" style="width: 60%; height: 300px; float:left;"></div>
<div id="pie_chart" style="width: 35%; height: 300px; float:left;"></div>
