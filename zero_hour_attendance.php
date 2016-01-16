<?php
require('../common.php');

$opts = getOptions($QUERY);
extract($opts);

$sql_checks = $checks;
unset($sql_checks['city_id']);  // We want everything - because we need to calculate national avg as well.
unset($sql_checks['center_id']);
unset($opts['checks']);

$page_title = 'Zero Hour Attendance';
list($data, $cache_key) = getCacheAndKey('data', $opts);

if(!$data) {
	$data = array();

	if($center_id == -1) $all_centers_in_city = $sql->getCol("SELECT id FROM Center WHERE city_id=$city_id AND status='1'");
	else $all_centers_in_city = array($center_id);

	$template_array = array('total_class' => 0, 'zero_hour_attendance' => 0, 'percentage' => 0);
	$data_template = array($template_array, $template_array, $template_array, $template_array);
	$national = $data_template;

	$all_classes = $sql->getAll("SELECT UC.id, UC.substitute_id, UC.class_id, C.class_on, UC.zero_hour_attendance, Ctr.city_id, B.center_id
				FROM Class C
				INNER JOIN Batch B ON B.id=C.batch_id
				INNER JOIN Center Ctr ON B.center_id=Ctr.id
				INNER JOIN UserClass UC ON UC.class_id=C.id
				WHERE C.status='happened' AND B.year=$year AND "
				. implode(' AND ', $sql_checks));
	foreach ($all_classes as $c) {
		if($c['class_on'] > date("Y-m-d H:i:s")) continue; // Don't count classes not happened yet.
		$index = findWeekIndex($c['class_on']);

		if($index <= 3 and $index >= 0) {
			$national[$index]['total_class']++;

			if($c['zero_hour_attendance']) $national[$index]['zero_hour_attendance']++;
		}
	}

	foreach ($all_centers_in_city as $this_center_id) {
		$adoption = getAdoptionDataPercentage($city_id, $this_center_id, $all_cities, $all_centers, 'volunteer');
		$center_data = $data_template;
		$annual_data = $template_array;

		foreach ($all_classes as $c) {
			if($c['class_on'] > date("Y-m-d H:i:s")) continue; // Don't count classes not happened yet.

			$index = findWeekIndex($c['class_on']);

			if((!$this_center_id or ($c['center_id'] == $this_center_id)) and (!$city_id or ($c['city_id'] == $city_id))) {
				$annual_data['total_class']++;
				if($index <= 3 and $index >= 0) $center_data[$index]['total_class']++;

				if($c['zero_hour_attendance']) {
					$annual_data['zero_hour_attendance']++;
					if($index <= 3 and $index >= 0) $center_data[$index]['zero_hour_attendance']++;
				}
			}
		}

		foreach($center_data as $index => $value) {
			if($center_data[$index]['total_class']) $center_data[$index]['percentage'] = round($center_data[$index]['zero_hour_attendance'] / $center_data[$index]['total_class'] * 100, 2);
			if($national[$index]['total_class']) $national[$index]['percentage'] = round($national[$index]['zero_hour_attendance'] / $national[$index]['total_class'] * 100, 2);
		}
		if($annual_data['total_class']) $annual_data['percentage'] = round($annual_data['zero_hour_attendance'] / $annual_data['total_class'] * 100, 2);

		$weekly_graph_data = array(
			array('Weekly ' . $page_title, '% of Zero Hour Attendance', 'National Average'),
			array('Four week Back', $center_data[3]['percentage'], $national[3]['percentage']),
			array('Three Week Back',$center_data[2]['percentage'], $national[2]['percentage']),
			array('Two Week Back', 	$center_data[1]['percentage'], $national[1]['percentage']),
			array('Last Week',   	$center_data[0]['percentage'], $national[0]['percentage'])
		);

		$annual_graph_data = array(
				array('Year', '% of Zero Hour Attended'),
				array('Zero Hour Attended',	$annual_data['percentage']),
				array('Zero Hour Missed',	100 - $annual_data['percentage']),
			);

		$data[$this_center_id]['weekly_graph_data'] = $weekly_graph_data;
		$data[$this_center_id]['annual_graph_data'] = $annual_graph_data;

		$data[$this_center_id]['center_id'] = $this_center_id;
		$data[$this_center_id]['center_name'] = ($this_center_id) ? $sql->getOne("SELECT name FROM Center WHERE id=$this_center_id") : '';
	}

	setCache('data', $data);
}

$colors = array('#16a085', '#e74c3c');

render('multi_graph.php');
