<?php
require('../common.php');

$opts = getOptions($QUERY);
extract($opts);

$sql_checks = $checks;
unset($sql_checks['city_id']);  // We want everything - because we need to calculate national avg as well.
unset($sql_checks['center_id']);
unset($opts['checks']);

list($data, $cache_key) = getCacheAndKey('data', $opts); $data = array();
$week_dates = array();

if(!$data) {
	$data = array();
	$cache_status = false;

	if($center_id == -1) $all_centers_in_city = $sql->getCol("SELECT id FROM Center WHERE city_id=$city_id AND status='1'");
	else $all_centers_in_city = array($center_id);

	$template_array = array('total_class' => 0, 'attendance' => 0, 'percentage' => 0);
	$data_template = array($template_array, $template_array, $template_array, $template_array);
	$center_data = $data_template;
	$national = $data_template;

	$all_classes = $sql->getAll("SELECT C.id, C.status, C.level_id, C.class_on, SC.student_id, SC.participation, SC.id AS student_class_id, Ctr.city_id, B.center_id
		FROM Class C
		INNER JOIN Batch B ON B.id=C.batch_id
		INNER JOIN Center Ctr ON B.center_id=Ctr.id
		LEFT JOIN StudentClass SC ON C.id=SC.class_id 
		WHERE C.status='happened' AND B.year=$year AND "
		. implode(' AND ', $sql_checks) . " ORDER BY class_on DESC");
	foreach ($all_classes as $c) {
		if($c['class_on'] > date("Y-m-d H:i:s")) continue; // Don't count classes not happened yet.

		$index = findWeekIndex($c['class_on']);

		if($index <= 3 and $index >= 0) {
			if($c['student_id']) {
				$national[$index]['total_class']++;
				if($c['participation']) $national[$index]['attendance']++;
			}
		}
	}

	foreach ($all_centers_in_city as $this_center_id) {
		$center_data = $data_template;
		$annual_data = $template_array;

		$data[$this_center_id]['adoption'] = getAdoptionDataPercentage($city_id, $this_center_id, $all_cities, $all_centers, 'student');

		foreach ($all_classes as $c) {
			if($c['class_on'] > date("Y-m-d H:i:s")) continue; // Don't count classes not happened yet.

			$index = findWeekIndex($c['class_on']);

			if($index <= 3 and $index >= 0) {
				if(!isset($week_dates[$index])) $week_dates[$index] = findSundayDate($c['class_on']);

				if($c['student_id']) {
					if((!$this_center_id or ($c['center_id'] == $this_center_id)) and ($city_id <= 0 or ($c['city_id'] == $city_id))) {
						$center_data[$index]['total_class']++;
						if($c['participation']) $center_data[$index]['attendance']++;
					}
				}
			}

			if((!$this_center_id or ($c['center_id'] == $this_center_id)) and (!$city_id or ($c['city_id'] == $city_id))) {
				if($c['student_id']) {
					$annual_data['total_class']++;
					if($c['participation']) $annual_data['attendance']++;
				}
			}
		}

		foreach($center_data as $index => $value) {
			if($center_data[$index]['total_class']) $center_data[$index]['percentage'] = round($center_data[$index]['attendance'] / $center_data[$index]['total_class'] * 100, 2);
			if($national[$index]['total_class']) $national[$index]['percentage'] = round($national[$index]['attendance'] / $national[$index]['total_class'] * 100, 2);
		}
		if($annual_data['total_class']) $annual_data['percentage'] = round($annual_data['attendance'] / $annual_data['total_class'] * 100, 2);

		$output_data_format = 'percentage';
		if($format == 'csv') $output_data_format = 'attendance';

		$weekly_graph_data = array(
			array('Week', 'Weekly Child Attendance', 'National Average'),
			array('Four week Back', $center_data[3][$output_data_format], $national[3][$output_data_format]),
			array('Three Week Back',$center_data[2][$output_data_format], $national[2][$output_data_format]),
			array('Two Week Back',	$center_data[1][$output_data_format], $national[1][$output_data_format]),
			array('Last Week',		$center_data[0][$output_data_format], $national[0][$output_data_format])
		);

		$annual_graph_data = array(
			array('Year', 'Attendance'),
			array('Attended',	$annual_data['percentage']),
			array('Absent',		100 - $annual_data['percentage']),
		);

		$data[$this_center_id]['weekly_graph_data'] = $weekly_graph_data;
		$data[$this_center_id]['annual_graph_data'] = $annual_graph_data;
		// $data[$this_center_id]['week_dates'] = $week_dates;

		$data[$this_center_id]['city_id'] = $city_id;
		$data[$this_center_id]['center_id'] = $this_center_id;
		$data[$this_center_id]['center_name'] = ($this_center_id) ? $sql->getOne("SELECT name FROM Center WHERE id=$this_center_id") : '';
	}
	setCache($cache_key, $data);
}

$colors = array('#16a085', '#e74c3c');
$page_title = 'Child Attendance';

if($format == 'csv') render('csv.php', false);
else render('multi_graph.php');
