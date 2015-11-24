
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
	var data = google.visualization.arrayToDataTable([
		['Weekly Child Participation', '% of level 4 and above', 	'% of level 3', '% of level 2 and below'],
		['Four week Back', <?php echo $data[3]['percentage_5'] + $data[3]['percentage_4']  ?>, 
				<?php echo $data[3]['percentage_3']  ?>, <?php echo $data[3]['percentage_1'] + $data[3]['percentage_2']  ?>],
		['Three Week Back', <?php echo $data[2]['percentage_5'] + $data[2]['percentage_4']  ?>, 
				<?php echo $data[2]['percentage_3']  ?>, <?php echo $data[2]['percentage_1'] + $data[2]['percentage_2']  ?>],
		['Two Week Back', <?php echo $data[1]['percentage_5'] + $data[1]['percentage_4']  ?>, 
				<?php echo $data[1]['percentage_3']  ?>, <?php echo $data[1]['percentage_1'] + $data[1]['percentage_2']  ?>],
		['Last Week', <?php echo $data[0]['percentage_5'] + $data[0]['percentage_4']  ?>, 
				<?php echo $data[0]['percentage_3']  ?>, <?php echo $data[0]['percentage_1'] + $data[0]['percentage_2']  ?>]
	]);

	// :TODO: Highlight points of the graph
	var options = {
		title: 'Weekly Child Participation',
		vAxis: {
			viewWindow: {
				max:100,
				min:0
			}
		}
	};
	
	var chart = new google.visualization.LineChart(document.getElementById('curve_chart'));
	// var chart = new google.charts.Line(document.getElementById('curve_chart'));
	chart.draw(data, options);
  }

function drawPie() {
	var data = google.visualization.arrayToDataTable([
		['Year', '% of child Participation'],
		['Level 4 and above', <?php echo $annual_data['percentage_5'] + $annual_data['percentage_4']  ?>], 
		['Level 3', <?php echo $annual_data['percentage_3']  ?>], 
		['Level 2 or below', <?php echo $annual_data['percentage_1'] + $annual_data['percentage_2']  ?>]
	]);

	var options = {
		title: 'Annual Child Participation',
	};
	var chart = new google.visualization.PieChart(document.getElementById('pie_chart'));
	chart.draw(data, options);
  }


</script>
<div id="curve_chart" style="width: 60%; height: 300px; float:left;"></div>
<div id="pie_chart" style="width: 35%; height: 300px; float:left;"></div>
