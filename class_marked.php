<?php
require('../common.php');

$opts = getOptions($QUERY);
extract($opts);

$sql_checks = $checks;
unset($sql_checks['city_id']);  // We want everything - because we need to calculate national avg as well.
unset($sql_checks['center_id']);
unset($opts['checks']);

$page_title = 'Class Marked';
list($data, $cache_key) = getCacheAndKey('data', $opts); // $data = array();

if(!$data) {
	$cache_status = false;
	$data = array();

	if($center_id == -1) $all_centers_in_city = $sql->getCol("SELECT id FROM Center WHERE city_id=$city_id AND status='1'");
	else $all_centers_in_city = array($center_id);

	$template_array = array('total_class' => 0, 'marked' => 0, 'unmarked' => 0, 'percentage' => 0);
	$data_template = array($template_array, $template_array, $template_array, $template_array, $template_array);
	$center_data = $data_template;
	$national = $data_template;

	if($format == 'csv') {
		$sql_checks['city_id'] = "Ctr.city_id=$city_id";
		// if(i($QUERY, 'center_id')) $sql_checks['center_id'] = 'Ctr.id='.i($QUERY, 'center_id');
	}

	$all_classes = $sql->getAll("SELECT C.id, C.status, C.batch_id, C.level_id, C.class_on, Ctr.city_id, B.center_id
			FROM Class C
			INNER JOIN Batch B ON B.id=C.batch_id
			INNER JOIN Center Ctr ON B.center_id=Ctr.id
			WHERE B.year=$year AND "
			. implode(' AND ', $sql_checks) . " ORDER BY C.class_on DESC");

	// //dump($all_classes);
	// foreach ($all_classes as $row) {
	// 	print implode(",", array_values($row)) . "\n";
	// 	# code...
	// }
	// exit;

	if($format != 'csv') {
		foreach ($all_classes as $c) {
			if($c['class_on'] > date("Y-m-d H:i:s")) continue; // Don't count classes not happened yet.
			$index = findWeekIndex($c['class_on']);
			
			if(!isset($national[$index])) $national[$index] = $template_array;
			$national[$index]['total_class']++;
			if($c['status'] != 'projected') $national[$index]['marked']++;
			else $national[$index]['unmarked']++;
		}
		foreach($national as $index => $value) {
			if($national[$index]['total_class']) $national[$index]['percentage'] = round($national[$index]['marked'] / $national[$index]['total_class'] * 100, 2);
		}
	}

	foreach ($all_centers_in_city as $this_center_id) {
		if($format != 'csv') $data[$this_center_id]['adoption'] = getAdoptionDataPercentage($city_id, $this_center_id, $all_cities, $all_centers, 'volunteer');
		$center_data = $data_template;
		$annual_data = $template_array;

		foreach ($all_classes as $c) {
			if($c['class_on'] > date("Y-m-d H:i:s")) continue; // Don't count classes not happened yet.
			$index = findWeekIndex($c['class_on']);

			if((!$this_center_id or ($c['center_id'] == $this_center_id)) and (!$city_id or ($c['city_id'] == $city_id))) {
				if(!isset($center_data[$index])) $center_data[$index] = $template_array;

				$center_data[$index]['total_class']++;
				if($c['status'] != 'projected') $center_data[$index]['marked']++;
				else $center_data[$index]['unmarked']++;
			}

			if((!$this_center_id or ($c['center_id'] == $this_center_id)) and (!$city_id or ($c['city_id'] == $city_id))) {
				$annual_data['total_class']++;
				if($c['status'] != 'projected') $annual_data['marked']++;
				else $annual_data['unmarked']++;
			}
		}

		foreach($center_data as $index => $value) {
			if($center_data[$index]['total_class']) $center_data[$index]['percentage'] = round($center_data[$index]['marked'] / $center_data[$index]['total_class'] * 100, 2);
		}
		if($annual_data['total_class']) $annual_data['percentage'] = round($annual_data['marked'] / $annual_data['total_class'] * 100, 2);

		$weekly_graph_data = array(
				array('Weekly ' . $page_title, '% of Marked classes', 'National Average'),
				array('Four week Back', $center_data[3]['percentage'], $national[3]['percentage']),
				array('Three Week Back',$center_data[2]['percentage'], $national[2]['percentage']),
				array('Two Week Back',	$center_data[1]['percentage'], $national[1]['percentage']),
				array('Last Week',		$center_data[0]['percentage'], $national[0]['percentage'])
			);
		$annual_graph_data = array(
				array('Year', 'Marked'),
				array('Happened',	$annual_data['total_class'] - $annual_data['marked']),
				array('Marked',	$annual_data['marked']),
			);

		$data[$this_center_id]['weekly_graph_data'] = $weekly_graph_data;
		$data[$this_center_id]['annual_graph_data'] = $annual_graph_data;
		
		$data[$this_center_id]['week_dates'] = $week_dates;
		$data[$this_center_id]['center_data'] = $center_data;

		$opts['center_id'] = $this_center_id;
		$data[$this_center_id]['listing_link'] = getLink('class_cancellation_listing.php', $opts);
		$data[$this_center_id]['listing_text'] = 'List All Classes that are Cancelled';

		$data[$this_center_id]['city_id'] = $city_id;
		$data[$this_center_id]['center_id'] = $this_center_id;
		$data[$this_center_id]['center_name'] = ($this_center_id) ? $sql->getOne("SELECT name FROM Center WHERE id=$this_center_id") : '';
	}
	setCache($cache_key, $data);
}

$colors = array('#16a085', '#e74c3c');
$csv_format = array(
		'city_name'		=> 'City',
		'center_name'	=> 'Center',
		'week'			=> 'Week',
		'marked'		=> 'Marked',
		'unmarked'		=> 'Unmarked',
		'total_class'	=> 'Total',
	);

if($format == 'csv') render('csv.php', false);
else render('multi_graph.php');
