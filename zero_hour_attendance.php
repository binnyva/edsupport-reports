<?php
require('../common.php');

$city_id = i($QUERY,'city_id', 0);
$center_id = i($QUERY,'center_id', 0);
$base_date = i($QUERY,'base_date', date('Y-m-d'));
$year = 2015;
$year_start = $year . '-04-01 00:00:00';
$year_end = intval($year+1) . '-03-31 00:00:00';

$all_classes = $sql->getAll("SELECT UC.id, UC.substitute_id, UC.class_id, C.class_on, UC.zero_hour_attendance
		FROM Class C
		INNER JOIN Batch B ON B.id=C.batch_id
		INNER JOIN UserClass UC ON UC.class_id=C.id
		WHERE C.class_on>'$year_start' AND C.status='happened' AND C.class_on<'$year_end' AND B.year=$year");

$template_array = array('total_class' => 0, 'zero_hour_attendance' => 0);
$data = array($template_array, $template_array, $template_array, $template_array);
$annual_data = $template_array;

$class_done = array();
$count = 0;
foreach ($all_classes as $c) {
	if(isset($class_done[$c['id']])) continue; // If data is already marked, skip.
	$class_done[$c['id']] = true;
	if($c['class_on'] > date("Y-m-d H:i:s")) continue; // Don't count classes not happened yet.

	$datetime1 = date_create($c['class_on']);
	$datetime2 = date_create(date('Y-m-d'));
	$interval = date_diff($datetime1, $datetime2);
	$gap = $interval->format('%a');

	$index = ceil($gap / 7) - 1;

	$annual_data['total_class']++;
	if($index <= 3) $data[$index]['total_class']++;

	if($c['zero_hour_attendance']) {
		$annual_data['zero_hour_attendance']++;
		if($index <= 3) $data[$index]['zero_hour_attendance']++;
	}
	$count++;
	// if($count > 100) break;
}

foreach($data as $index => $value) {
	$data[$index]['percentage'] = round($data[$index]['zero_hour_attendance'] / $data[$index]['total_class'] * 100, 2);
}
$annual_data['percentage'] = round($annual_data['zero_hour_attendance'] / $annual_data['total_class'] * 100, 2);

render();
