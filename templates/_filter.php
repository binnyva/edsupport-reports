<?php
$centers = array('0' => array('Any'));
foreach ($all_centers as $this_center_id => $center) {
	if(!isset($centers[$center['city_id']])) $centers[$center['city_id']] = array('Any');
	$centers[$center['city_id']][$this_center_id] = $center['name'];
}
?>
<script type="text/javascript">
var centers = <?php echo json_encode($centers); ?>;
</script>

<form action="" method="post" class="form-area">
<?php
$html->buildInput("city_id", 'City', 'select', $city_id, array('options' => $all_cities));
$html->buildInput("center_id", 'Center', 'select', $center_id, array('options' => $centers[$city_id]));
?>
<div id="select-date-area">
<?php
$html->buildInput('from', 'From', 'text', $from, array('class' => 'date-picker'));
$html->buildInput('to', 'To', 'text', $to, array('class' => 'date-picker'));
?>
</div><a href="#" id="select-date-toggle">Select Date Range</a><br />
<?php

$html->buildInput("action", '&nbsp;', 'submit', 'Filter', array('class' => 'btn btn-primary'));
?>
</form><br /><br />
