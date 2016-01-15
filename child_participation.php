<?php
require('../common.php');

$opts = getOptions($QUERY);
extract($opts);
unset($opts['checks']);
$sql_checks = $checks;
unset($sql_checks['city_id']);  // We want everything - because we need to calculate national avg as well.
unset($sql_checks['center_id']);

$page_title = 'Child Participation';

list($data, $cache_key) = getCacheAndKey('data', $opts);

if(!$data and $city_id) {
	$data = array();

	if($center_id == -1) $all_centers_in_city = $sql->getCol("SELECT id FROM Center WHERE city_id=$city_id AND status='1'");
	else $all_centers_in_city = array($center_id);

	$template_array = array('total_class' => 0, 'attendance' => 0, 
			'participation_5' => 0, 'participation_4' => 0, 'participation_3' => 0, 'participation_2' => 0, 'participation_1' => 0,
			'percentage_5' => 0, 'percentage_4' => 0, 'percentage_3' => 0, 'percentage_2' => 0, 'percentage_1' => 0);

	foreach ($all_centers_in_city as $this_center_id) {
		$data[$this_center_id]['adoption'] = getAdoptionDataPercentage($city_id, $this_center_id, $all_cities, $all_centers, 'student');
		$center_data = array($template_array, $template_array, $template_array, $template_array);
		$annual_data = $template_array;

		$sql_checks['center_id'] = 'Ctr.id='.$this_center_id;
		$all_classes = $sql->getAll("SELECT C.id, C.status, C.level_id, C.class_on, SC.student_id, SC.participation, SC.id AS student_class_id
			FROM Class C
			INNER JOIN Batch B ON B.id=C.batch_id
			INNER JOIN Center Ctr ON B.center_id=Ctr.id
			LEFT JOIN StudentClass SC ON C.id=SC.class_id 
			WHERE C.status='happened' AND B.year=$year AND "
			. implode(' AND ', $sql_checks));

		$count = 0;
		foreach ($all_classes as $c) {
			if(isset($class_done[$c['student_class_id']])) continue; // If data is already marked, skip.
			$class_done[$c['student_class_id']] = true;
			if($c['class_on'] > date("Y-m-d H:i:s")) continue; // Don't count classes not happened yet.

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

			$count++;
			// if($count > 10) break;
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

	setCache('data', $data);
}
if(!$data) $data = array();

$page_title = 'Child Participation';
$colors = array('#16a085', '#f1c40f', '#e74c3c');
render('multi_graph.php');
