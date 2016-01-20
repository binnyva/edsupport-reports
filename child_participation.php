<?php
require('../common.php');

$opts = getOptions($QUERY);
extract($opts);
unset($opts['checks']);
$sql_checks = $checks;
unset($sql_checks['city_id']);
unset($sql_checks['center_id']);

$page_title = 'Child Participation';

list($data, $cache_key) = getCacheAndKey('data', $opts);

if(!$data) {
	$cache_status = false;
	$data = array();

	if($center_id == -1) $all_centers_in_city = $sql->getCol("SELECT id FROM Center WHERE city_id=$city_id AND status='1'");
	else $all_centers_in_city = array($center_id);

	$template_array = array('total_class' => 0, 'attendance' => 0, 
			'participation_5' => 0, 'participation_4' => 0, 'participation_3' => 0, 'participation_2' => 0, 'participation_1' => 0,
			'percentage_5' => 0, 'percentage_4' => 0, 'percentage_3' => 0, 'percentage_2' => 0, 'percentage_1' => 0);

	$all_classes = $sql->getAll("SELECT C.id, C.status, C.level_id, C.class_on, SC.student_id, SC.participation, SC.id AS student_class_id, Ctr.id AS center_id, Ctr.city_id
			FROM Class C
			INNER JOIN Batch B ON B.id=C.batch_id
			INNER JOIN Center Ctr ON B.center_id=Ctr.id
			LEFT JOIN StudentClass SC ON C.id=SC.class_id 
			WHERE C.status='happened' AND B.year=$year AND "
			. implode(' AND ', $sql_checks));

	foreach ($all_centers_in_city as $this_center_id) {
		$data[$this_center_id]['adoption'] = getAdoptionDataPercentage($city_id, $this_center_id, $all_cities, $all_centers, 'student');
		$center_data = array($template_array, $template_array, $template_array, $template_array);
		$annual_data = $template_array;

		foreach ($all_classes as $c) {
			if($c['class_on'] > date("Y-m-d H:i:s")) continue; // Don't count classes not happened yet.
			if($city_id and $c['city_id'] != $city_id) continue;
			if($this_center_id and $c['center_id'] != $this_center_id) continue;

			$index = findWeekIndex($c['class_on']);

			if($index >=0 and $index <= 3) {
				if($c['student_id']) {
					$center_data[$index]['total_class']++;

					if($c['participation']) {
						$center_data[$index]['attendance']++;
						$center_data[$index]['participation_' . $c['participation']]++;
					}
				}
			}
			if($c['student_id']) {
				$annual_data['total_class']++;
				if($c['participation']) {
					$annual_data['attendance']++;
					$annual_data['participation_' . $c['participation']]++;
				}
			}
		}

		foreach($center_data as $index => $value) {
			for ($i=1; $i<=5; $i++) {
				if($center_data[$index]['attendance']) 
					$center_data[$index]['percentage_' . $i] = round($center_data[$index]['participation_' . $i] / $center_data[$index]['attendance'] * 100, 2);
				else 
					$center_data[$index]['percentage_' . $i] = 0;
			}
		}
		for ($i=1; $i<=5; $i++) {
			if($annual_data['attendance'])
				$annual_data['percentage_' . $i] = round($annual_data['participation_' . $i] / $annual_data['attendance'] * 100, 2);
		}

		$weekly_graph_data = array(
				array('Weekly ' . $page_title, '% of level 4 and above', 	'% of level 3', '% of level 2 and below'),
				array('Four week Back',	$center_data[3]['percentage_5'] + $center_data[3]['percentage_4'], $center_data[3]['percentage_3'], $center_data[3]['percentage_1'] + $center_data[3]['percentage_2']),
				array('Three Week Back',$center_data[2]['percentage_5'] + $center_data[2]['percentage_4'], $center_data[2]['percentage_3'], $center_data[2]['percentage_1'] + $center_data[2]['percentage_2']),
				array('Two Week Back',	$center_data[1]['percentage_5'] + $center_data[1]['percentage_4'], $center_data[1]['percentage_3'], $center_data[1]['percentage_1'] + $center_data[1]['percentage_2']),
				array('Last Week',		$center_data[0]['percentage_5'] + $center_data[0]['percentage_4'], $center_data[0]['percentage_3'], $center_data[0]['percentage_1'] + $center_data[0]['percentage_2'])
			);
		$annual_graph_data = array(
				array('Year', '% of child Participation'),
				array('Level 4 and above', $annual_data['percentage_5'] + $annual_data['percentage_4']),
				array('Level 3', $annual_data['percentage_3']),
				array('Level 2 or below', $annual_data['percentage_1'] + $annual_data['percentage_2']),
			);
		$data[$this_center_id]['weekly_graph_data'] = $weekly_graph_data;
		$data[$this_center_id]['annual_graph_data'] = $annual_graph_data;

		$data[$this_center_id]['center_id'] = $this_center_id;
		$data[$this_center_id]['center_name'] = ($this_center_id) ? $sql->getOne("SELECT name FROM Center WHERE id=$this_center_id") : '';
	}

	setCache($cache_key, $data);
}
if(!$data) $data = array();

$colors = array('#16a085', '#f1c40f', '#e74c3c');

if($format == 'csv') render('csv.php', false);
else render('multi_graph.php');
