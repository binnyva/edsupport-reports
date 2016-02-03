<?php
require('../common.php');

$opts = getOptions($QUERY);
extract($opts);

$sql_checks = $checks;
unset($sql_checks['city_id']);  // We want everything - because we need to calculate national avg as well.
unset($sql_checks['center_id']);
unset($opts['checks']);

$page_title = 'Child Attendance';

list($data, $cache_key) = getCacheAndKey('data', $opts); $data = array();

$output_data_format = 'percentage';
if($format == 'csv') $output_data_format = 'attendance';
$output_total_format = 'total_class';
$output_unmarked_format = 'unmarked';


if(!$data) {
	$data = array();
	$cache_status = false;

	if($center_id == -1) $all_centers_in_city = $sql->getCol("SELECT id FROM Center WHERE city_id=$city_id AND status='1'");
	else $all_centers_in_city = array($center_id);

	$template_array = array('total_class' => 0, 'attendance' => 0, 'unmarked' => 0, 'percentage' => 0);
	$data_template = array($template_array, $template_array, $template_array, $template_array, $template_array);
	$center_data = $data_template;
	$national = $data_template;

	$level_data = $sql->getById("SELECT L.id, COUNT(SL.id) as student_count 
		FROM Level L 
		INNER JOIN StudentLevel SL ON SL.level_id=L.id 
		WHERE L.center_id IN (" .implode(",", $all_centers_in_city). ") AND L.status='1' AND L.year='$year'
		GROUP BY SL.level_id");

	$all_classes = $sql->getAll("SELECT C.id, C.status, C.level_id, C.class_on, Ctr.city_id, L.center_id
		FROM Class C
		INNER JOIN Level L ON L.id=C.level_id
		INNER JOIN Center Ctr ON L.center_id=Ctr.id
		WHERE L.year=$year AND L.status='1' AND "
		. implode(' AND ', $sql_checks) . " 
		ORDER BY class_on DESC");

	$students = $sql->getById("SELECT SC.class_id, COUNT(SC.id) AS total_count, SUM(CASE WHEN SC.present='1' THEN 1 ELSE 0 END) AS present
		FROM StudentClass SC 
		INNER JOIN Class C ON C.id=SC.class_id 
		WHERE C.level_id IN (" . implode(",", array_keys($level_data)) . ")
		GROUP BY SC.class_id");

	foreach ($all_classes as $c) {
		if($c['class_on'] > date("Y-m-d H:i:s")) continue; // Don't count classes not happened yet.

		$index = findWeekIndex($c['class_on']);
		if(!isset($national[$index])) $national[$index] = $template_array;

		$class_id = $c['id'];
		if(isset($students[$class_id])) { // There were hits in the StudentClass table
			$national[$index]['total_class'] += $students[$class_id]['total_count'];
			$national[$index]['attendance'] += $students[$class_id]['present'];

		} else { // No coressponding rows in the StudentClass Table - meaning data not entered. So, we are going to get the data from the level table - students assigned to that level.
			if(isset($level_data[$c['level_id']])) {
				$national[$index]['total_class'] += $level_data[$c['level_id']];
				$national[$index]['unmarked'] += $level_data[$c['level_id']];
			}
			// Else - no kids assigned to this level, it seems.
		}
	}
	foreach($national as $index => $value) {
		if($national[$index]['total_class']) $national[$index]['percentage'] = round($national[$index]['attendance'] / $national[$index]['total_class'] * 100, 2);
	}


	foreach ($all_centers_in_city as $this_center_id) {
		$center_data = $data_template;
		$annual_data = $template_array;

		$data[$this_center_id]['adoption'] = getAdoptionDataPercentage($city_id, $this_center_id, $all_cities, $all_centers, 'student');

		foreach ($all_classes as $c) {
			if($c['class_on'] > date("Y-m-d H:i:s")) continue; // Don't count classes not happened yet.
			if(($this_center_id and ($c['center_id'] != $this_center_id)) or ($city_id > 0 and ($c['city_id'] != $city_id))) continue;

			$index = findWeekIndex($c['class_on']);

			if((!$this_center_id or ($c['center_id'] == $this_center_id)) and ($city_id <= 0 or ($c['city_id'] == $city_id))) {
				if(!isset($center_data[$index])) $center_data[$index] = $template_array;

				$class_id = $c['id'];
				if(isset($students[$class_id])) { // There were hits in the StudentClass table
					$center_data[$index]['total_class'] += $students[$class_id]['total_count'];
					$center_data[$index]['attendance'] += $students[$class_id]['present'];

					$annual_data['total_class'] += $students[$class_id]['total_count'];
					$annual_data['attendance'] += $students[$class_id]['present'];

				} else { // No coressponding rows in the StudentClass Table - meaning data not entered. So, we are going to get the data from the level table - students assigned to that level.
					if(isset($level_data[$c['level_id']])) {
						$center_data[$index]['total_class'] += $level_data[$c['level_id']];
						$center_data[$index]['unmarked'] += $level_data[$c['level_id']];
					}
					// Else - no kids assigned to this level, it seems.
				}
			}

		}

		foreach($center_data as $index => $value) {
			if($center_data[$index]['total_class']) $center_data[$index]['percentage'] = round($center_data[$index]['attendance'] / $center_data[$index]['total_class'] * 100, 2);
		}
		if($annual_data['total_class']) $annual_data['percentage'] = round($annual_data['attendance'] / $annual_data['total_class'] * 100, 2);

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
		krsort($week_dates);
		$data[$this_center_id]['week_dates'] = $week_dates;
		$data[$this_center_id]['center_data'] = $center_data;

		$data[$this_center_id]['city_id'] = $city_id;
		$data[$this_center_id]['center_id'] = $this_center_id;
		$data[$this_center_id]['center_name'] = ($this_center_id) ? $sql->getOne("SELECT name FROM Center WHERE id=$this_center_id") : '';
	}
	setCache($cache_key, $data);
}

$colors = array('#16a085', '#e74c3c');

if($format == 'csv') render('csv.php', false);
else render('multi_graph.php');
