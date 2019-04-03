<?php
require('./common.php');

$opts = getOptions($QUERY);
extract($opts);

$sql_checks = $checks;
unset($sql_checks['city_id']);  // We want everything - because we need to calculate national avg as well.
unset($sql_checks['center_id']);
unset($opts['checks']);

$page_title = 'Check For Understanding';
list($data, $cache_key) = getCacheAndKey('data', $opts);
$year = findYear($opts['to']);

$output_data_format = 'percentage';
if($format == 'csv') $output_data_format = 'check_for_understanding';
$output_total_format = 'total_class';

if(!$data) {
	$data = array();

	if($center_id == -1) $all_centers_in_city = $sql->getCol("SELECT id FROM Center WHERE city_id=$city_id AND status='1'");
	else $all_centers_in_city = array($center_id);

	$template_array = array('total_class' => 0, 'check_for_understanding' => 0, 'marked' => 0, 'unmarked' => 0,'percentage' => 0);
	$data_template = array($template_array, $template_array, $template_array, $template_array);
	$national = $data_template;

	$all_classes = $sql->getAll("SELECT SC.id, C.status, SC.check_for_understanding, C.class_on, B.center_id, Ctr.city_id
				FROM Class C
				INNER JOIN Batch B ON B.id=C.batch_id
				INNER JOIN Center Ctr ON B.center_id=Ctr.id
				INNER JOIN StudentClass SC ON SC.class_id=C.id
				WHERE B.year=$year AND "
				. implode(' AND ', $sql_checks)
				. " ORDER BY C.class_on DESC");

	if($format != 'csv') {
		foreach ($all_classes as $c) {
			if($c['class_on'] > date("Y-m-d H:i:s")) continue; // Don't count classes not happened yet.

			$index = findWeekIndex($c['class_on'], $opts['to']);
			if(!isset($national[$index])) $national[$index] = $template_array;

			$national[$index]['total_class']++;
			if($c['status'] == 'projected') $national[$index]['unmarked']++;
			elseif($c['status'] != 'projected') {
				$national[$index]['marked']++;
				if($c['check_for_understanding']) $national[$index]['check_for_understanding']++;
			}
		}
		foreach($national as $index => $value) {
			if($national[$index]['marked']) $national[$index]['percentage'] = round($national[$index]['check_for_understanding'] / $national[$index]['marked'] * 100, 2);
		}
	}

	foreach ($all_centers_in_city as $this_center_id) {
		if($format != 'csv') $adoption = getAdoptionDataPercentage($city_id, $this_center_id, $all_cities, $all_centers, 'volunteer', $project_id);

		$center_data = $data_template;
		$annual_data = $template_array;

		foreach ($all_classes as $c) {
			if($c['class_on'] > date("Y-m-d H:i:s")) continue; // Don't count classes not happened yet.

			$index = findWeekIndex($c['class_on'], $opts['to']);
			if(!isset($center_data[$index])) $center_data[$index] = $template_array;

			if((!$this_center_id or ($c['center_id'] == $this_center_id)) and (!$city_id or ($c['city_id'] == $city_id))) {
				$annual_data['total_class']++;
				$center_data[$index]['total_class']++;

				if($c['status'] == 'projected') $center_data[$index]['unmarked']++;
				elseif($c['status'] != 'projected') {
					$center_data[$index]['marked']++;
					$annual_data['marked']++;

					if($c['check_for_understanding']) {
						$annual_data['check_for_understanding']++;
						$center_data[$index]['check_for_understanding']++;
					}
				}
			}
		}

		foreach($center_data as $index => $value) {
			if($center_data[$index]['marked']) $center_data[$index]['percentage'] = round($center_data[$index]['check_for_understanding'] / $center_data[$index]['marked'] * 100, 2);
		}
		if($annual_data['marked']) $annual_data['percentage'] = round($annual_data['check_for_understanding'] / $annual_data['marked'] * 100, 2);

		$weekly_graph_data = array(
			array('Weekly ' . $page_title, '% of Understood Class', 'National Average'),
			array(date('j M Y', strtotime($week_dates[3])), $center_data[3][$output_data_format], $national[3][$output_data_format]),
			array(date('j M Y', strtotime($week_dates[2])), $center_data[2][$output_data_format], $national[2][$output_data_format]),
			array(date('j M Y', strtotime($week_dates[1])), $center_data[1][$output_data_format], $national[1][$output_data_format]),
			array(date('j M Y', strtotime($week_dates[0])), $center_data[0][$output_data_format], $national[0][$output_data_format])
		);

		$annual_graph_data = array(
				array('Year', '% of Understood Class'),
				array('Classes Understood',	$annual_data['percentage']),
				array('Classes NOT Understood',	100 - $annual_data['percentage']),
			);

		$data[$this_center_id]['weekly_graph_data'] = $weekly_graph_data;
		$data[$this_center_id]['annual_graph_data'] = $annual_graph_data;
		
		$data[$this_center_id]['week_dates'] = $week_dates;
		$data[$this_center_id]['center_data'] = $center_data;

		$data[$this_center_id]['city_id'] = $city_id;
		$data[$this_center_id]['center_id'] = $this_center_id;
		$data[$this_center_id]['center_name'] = ($this_center_id) ? $sql->getOne("SELECT name FROM Center WHERE id=$this_center_id") : '';
	}

	setCache($cache_key, $data);
}

$csv_format = array(
		'city_name'		=> 'City',
		'center_name'	=> 'Center',
		'week'			=> 'Week',
		'total_class'	=> 'Total',
		'check_for_understanding'		=> 'Check For Understanding',
		'unmarked'		=> 'Unmarked',
	);
$colors = array('#16a085', '#e74c3c');

if($format == 'csv') render('csv.php', false);
else render('multi_graph.php');
