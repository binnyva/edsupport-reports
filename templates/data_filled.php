<h1><?php echo $page_title ?> Report</h1>

<?php 
include('_filter.php');

// Find the total numbers for all the centers.
$total = array_reduce($data, function($carry, $cntr) {
	if(!isset($cntr['total_classes'])) return $carry; 

	$carry['total_classes'] += $cntr['total_classes'];
	$carry['student_data_filled'] += $cntr['student_data_filled'];
	$carry['teacher_data_filled'] += $cntr['teacher_data_filled'];

	return $carry;
}, $data_template);

show($total);
echo "<hr />";

foreach($city_centers as $cty_id => $city) {
	if($city_id and $city_id != $cty_id) continue;

	echo "<h1>{$city['name']}</h1>";
	foreach($city['centers'] as $cntr_id => $cntr) {
		if($center_id and $center_id != $cntr_id) continue;

		if(!isset($data[$cntr_id])) continue;
		echo "<h2>{$cntr['name']}</h2>";
		show($data[$cntr_id]);
	}
	echo "<hr />";
}


function show($filled) { 
	if(!$filled) return;
	$percentage_colors = array('#e74c3c', '#16a085'); // Red , Green
	?>
<h3>Total Classes: <?php echo $filled['total_classes'] ?></h3>

<h4>Teacher Attendance Data Filled: <?php 
echo $filled['teacher_data_filled'];
$percentage = round($filled['teacher_data_filled'] / $filled['total_classes'] * 100);
?></h4>
<!-- <p>This data is entered by the mentor</p> -->
<div class="progress">
<div class="data" style="width:<?php echo $percentage ?>%; background-color: <?php echo $percentage_colors[1] ?>;"><?php echo $percentage ?>% Data Filled</div>
<div class="no-data" style="width:<?php echo 100-$percentage ?>%; background-color: <?php echo $percentage_colors[0] ?>;">&nbsp;</div>
</div>

<h4>Student Attendance Data Filled: <?php 
echo $filled['student_data_filled'];
$percentage = round($filled['student_data_filled'] / $filled['total_classes'] * 100);
?></h4>
<!-- <p>This data is entered by the teacher</p> -->
<div class="progress">
<div class="data" style="width:<?php echo $percentage ?>%; background-color: <?php echo $percentage_colors[1] ?>;"><?php echo $percentage ?>% Data Filled</div>
<div class="no-data" style="width:<?php echo 100-$percentage ?>%; background-color: <?php echo $percentage_colors[0] ?>;">&nbsp;</div>
</div>
<?php
}