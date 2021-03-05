<h1><?php echo $page_title ?> Report</h1>

<?php 
include('_filter.php');
$percentage_colors = array('#e74c3c', '#16a085'); // Red , Green
?>


<h3>Total Classes: <?php echo $data['total_classes'] ?></h3>

<h4>Teacher Attendance Data Filled: <?php 
echo $data['teacher_data_filled'];
$percentage = round($data['teacher_data_filled'] / $data['total_classes'] * 100);
echo " ($percentage)";
?></h4>
<p>This data is entered by the mentor</p>
<div class="progress">
<div class="data" style="width:<?php echo $percentage ?>%; background-color: <?php echo $percentage_colors[1] ?>;">Data Filled Percentage: <?php echo $percentage ?>%</div>
<div class="no-data" style="width:<?php echo 100-$percentage ?>%; background-color: <?php echo $percentage_colors[0] ?>;">&nbsp;</div>
</div>

<h4>Student Attendance Data Filled: <?php 
echo $data['student_data_filled'];
$percentage = round($data['student_data_filled'] / $data['total_classes'] * 100);
echo " ($percentage)";
?></h4>
<p>This data is entered by the teacher</p>
<div class="progress">
<div class="data" style="width:<?php echo $percentage ?>%; background-color: <?php echo $percentage_colors[1] ?>;">Data Filled Percentage: <?php echo $percentage ?>%</div>
<div class="no-data" style="width:<?php echo 100-$percentage ?>%; background-color: <?php echo $percentage_colors[0] ?>;">&nbsp;</div>
</div>