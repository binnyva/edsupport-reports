<?php
require('../common.php');

$opts = getOptions($QUERY);
extract($opts);

$sql_checks = $checks;
unset($sql_checks['city_id']);  // We want everything - because we need to calculate national avg as well.
unset($sql_checks['center_id']);
$all_classes = $sql->getAll("SELECT C.id, C.status, C.level_id, C.class_on, SC.student_id, SC.participation, SC.id AS student_class_id, Ctr.city_id, B.center_id
		FROM Class C
		INNER JOIN Batch B ON B.id=C.batch_id
		INNER JOIN Center Ctr ON B.center_id=Ctr.id
		LEFT JOIN StudentClass SC ON C.id=SC.class_id 
		WHERE C.status='happened' AND B.year=$year AND "
		. implode(' AND ', $sql_checks));

$template_array = array('total_class' => 0, 'attendance' => 0, 'percentage' => 0);
$data = array($template_array, $template_array, $template_array, $template_array);
$national = $data;
$annual_data = $template_array;

$class_done = array();
$count = 0;
foreach ($all_classes as $c) {
	if(isset($class_done[$c['student_class_id']])) continue; // If data is already marked, skip.
	$class_done[$c['student_class_id']] = true;
	if($c['class_on'] > date("Y-m-d H:i:s")) continue; // Don't count classes not happened yet.

	$index = findWeekIndex($c['class_on']);

	if($index <= 3 and $index >= 0) {
		if($c['student_id']) {
			if((!$center_id or ($c['center_id'] == $center_id)) and (!$city_id or ($c['city_id'] == $city_id))) {
				$data[$index]['total_class']++;
				if($c['participation']) $data[$index]['attendance']++;
			}
			$national[$index]['total_class']++;
			if($c['participation']) $national[$index]['attendance']++;
		}
	}

	if((!$center_id or ($c['center_id'] == $center_id)) and (!$city_id or ($c['city_id'] == $city_id))) {
		if($c['student_id']) {
			$annual_data['total_class']++;
			if($c['participation']) $annual_data['attendance']++;
		}
	}

	$count++;
	// if($count > 100) break;
}

foreach($data as $index => $value) {
	if($data[$index]['total_class']) $data[$index]['percentage'] = round($data[$index]['attendance'] / $data[$index]['total_class'] * 100, 2);
	if($national[$index]['total_class']) $national[$index]['percentage'] = round($national[$index]['attendance'] / $national[$index]['total_class'] * 100, 2);
}
if($annual_data['total_class']) $annual_data['percentage'] = round($annual_data['attendance'] / $annual_data['total_class'] * 100, 2);

$page_title = 'Child Attendance';
$weekly_graph_data = array(
		array('Weekly ' . $page_title, 'Attendance', 'National Average'),
		array('Four week Back', $data[3]['percentage'], $national[3]['percentage']),
		array('Three Week Back',$data[2]['percentage'], $national[2]['percentage']),
		array('Two Week Back',	$data[1]['percentage'], $national[1]['percentage']),
		array('Last Week',		$data[0]['percentage'], $national[0]['percentage'])
	);
$annual_graph_data = array(
		array('Year', 'Attendance'),
		array('Attended',	$annual_data['percentage']),
		array('Absent',		100 - $annual_data['percentage']),
	);

render('graph.php');
