<?php
require('../common.php');

$city_id = i($QUERY,'city_id', 0);
$center_id = i($QUERY,'center_id', 0);
$base_date = i($QUERY,'base_date', date('Y-m-d'));
$year = 2015;
$year_start = $year . '-04-01 00:00:00';
$year_end = intval($year+1) . '-03-31 00:00:00';

$all_classes = $sql->getAll("SELECT C.id, C.status, C.level_id, C.class_on, SC.student_id, SC.participation, SC.id AS student_class_id
		FROM Class C
		INNER JOIN Batch B ON B.id=C.batch_id
		LEFT JOIN StudentClass SC ON C.id=SC.class_id 
		WHERE C.class_on>'$year_start' AND C.class_on<'$year_end' AND C.status='happened' AND B.year=$year
		ORDER BY C.class_on");

$template_array = array('total_class' => 0, 'attendance' => 0);
$data = array($template_array, $template_array, $template_array, $template_array);
$annual_data = $template_array;

$class_done = array();
$count = 0;
foreach ($all_classes as $c) {
	if(isset($class_done[$c['student_class_id']])) continue; // If data is already marked, skip.
	$class_done[$c['student_class_id']] = true;
	if($c['class_on'] > date("Y-m-d H:i:s")) continue; // Don't count classes not happened yet.

	$datetime1 = date_create($c['class_on']);
	$datetime2 = date_create(date('Y-m-d'));
	$interval = date_diff($datetime1, $datetime2);
	$gap = $interval->format('%a');

	$index = ceil($gap / 7) - 1;
	// The above line is same as this...
	// if($gap < 7) $index = 0;
	// elseif($gap < 14) $index = 1;
	// elseif($gap < 21) $index = 2;
	// elseif($gap < 28) $index = 3;
	// else $index = 4;

	if($index <= 3) {
		if($c['student_id']) {
			$data[$index]['total_class']++;

			if($c['participation']) {
				$data[$index]['attendance']++;
			}
		}
	}
	if($c['student_id']) {
		$annual_data['total_class']++;
		if($c['participation']) $annual_data['attendance']++;
	}

	$count++;
	// if($count > 100) break;
}

foreach($data as $index => $value) {
	$data[$index]['percentage'] = round($data[$index]['attendance'] / $data[$index]['total_class'] * 100, 2);
}
$annual_data['percentage'] = round($annual_data['attendance'] / $annual_data['total_class'] * 100, 2);

render();
