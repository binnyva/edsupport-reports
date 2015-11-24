<html>
  <head>
	<script type="text/javascript"
		  src="https://www.google.com/jsapi?autoload={
			'modules':[{
			  'name':'visualization',
			  'version':'1',
			  'packages':['line','corechart']
			}]
		  }"></script>

	<script type="text/javascript">
	  google.setOnLoadCallback(drawChart);
	  google.setOnLoadCallback(drawPie);

	  function drawChart() {
		var data = google.visualization.arrayToDataTable([
			['Weekly Child Attendance', '% of child attendance'],
			['Four week Back', <?php echo $data[3]['percentage'] ?>],
			['Three Week Back',	<?php echo $data[2]['percentage'] ?>],
			['Two Week Back', <?php echo $data[1]['percentage'] ?>],
			['Last Week',   <?php echo $data[0]['percentage'] ?>]
		]);

		// :TODO: Highlight points of the graph
		var options = {
			title: 'Weekly Child Attendance',
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
			['Year', '% of child attendance'],
			['Attended',<?php echo $annual_data['percentage'] ?>],
			['Absent',	<?php echo 100 - $annual_data['percentage'] ?>],
		]);

		var options = {
			title: 'Annual Child Attendance',
		};
		var chart = new google.visualization.PieChart(document.getElementById('pie_chart'));
		chart.draw(data, options);
	  }


	</script>
  </head>
  <body><br /><br />
	<div id="curve_chart" style="width: 60%; height: 300px; float:left;"></div>
	<div id="pie_chart" style="width: 35%; height: 300px; float:left;"></div>
  </body>
</html>
