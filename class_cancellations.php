<?php
require('../common.php');

$opts = getOptions($QUERY);
extract($opts);

$sql_checks = $checks;
unset($sql_checks['city_id']);  // We want everything - because we need to calculate national avg as well.
unset($sql_checks['center_id']);
$all_classes = $sql->getAll("SELECT C.id, C.status, C.level_id, C.class_on, Ctr.city_id, B.center_id
		FROM Class C
		INNER JOIN Batch B ON B.id=C.batch_id
		INNER JOIN Center Ctr ON B.center_id=Ctr.id
		WHERE B.year=$year AND "
		. implode(' AND ', $sql_checks));

$template_array = array('total_class' => 0, 'cancelled' => 0, 'percentage' => 0);
$data = array($template_array, $template_array, $template_array, $template_array);
$national = $data;
$annual_data = $template_array;

$count = 0;
foreach ($all_classes as $c) {
	if($c['class_on'] > date("Y-m-d H:i:s")) continue; // Don't count classes not happened yet.

	$index = findWeekIndex($c['class_on']);

	if($index <= 3 and $index >= 0) {
		if($c['status'] != 'projected') {
			if((!$center_id or ($c['center_id'] == $center_id)) and (!$city_id or ($c['city_id'] == $city_id))) {
				$data[$index]['total_class']++;
				if($c['status'] == 'cancelled') $data[$index]['cancelled']++;
			}
			$national[$index]['total_class']++;
			if($c['status'] == 'cancelled') $national[$index]['cancelled']++;
		}
	}
	if((!$center_id or ($c['center_id'] == $center_id)) and (!$city_id or ($c['city_id'] == $city_id))) {
		if($c['status'] != 'projected') {
			$annual_data['total_class']++;
			if($c['status'] == 'cancelled') $annual_data['cancelled']++;
		}
	}

	$count++;
	// if($count > 100) break;
}

foreach($data as $index => $value) {
	if($data[$index]['total_class']) $data[$index]['percentage'] = round($data[$index]['cancelled'] / $data[$index]['total_class'] * 100, 2);
	if($national[$index]['total_class']) $national[$index]['percentage'] = round($national[$index]['cancelled'] / $national[$index]['total_class'] * 100, 2);
}
if($annual_data['total_class']) $annual_data['percentage'] = round($annual_data['cancelled'] / $annual_data['total_class'] * 100, 2);

$page_title = 'Class Cancellations';
$weekly_graph_data = array(
		array('Weekly ' . $page_title, '% of cancelled classes', 'National Average'),
		array('Four week Back', $data[3]['percentage'], $national[3]['percentage']),
		array('Three Week Back',$data[2]['percentage'], $national[2]['percentage']),
		array('Two Week Back',	$data[1]['percentage'], $national[1]['percentage']),
		array('Last Week',		$data[0]['percentage'], $national[0]['percentage'])
	);
$annual_graph_data = array(
		array('Year', 'Cancelled'),
		array('Happened',	100 - $annual_data['percentage']),
		array('Cancelled',	$annual_data['percentage']),
	);
$colors = array('green', 'red');

unset($opts['checks']);
$listing_link = getLink('class_cancellation_listing.php', $opts);

render('graph.php');
