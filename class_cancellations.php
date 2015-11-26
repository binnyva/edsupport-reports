<?php
require('../common.php');

$opts = getOptions($QUERY);
extract($opts);

$all_classes = $sql->getAll("SELECT C.id, C.status, C.level_id, C.class_on
		FROM Class C
		INNER JOIN Batch B ON B.id=C.batch_id
		INNER JOIN Center Ctr ON B.center_id=Ctr.id
		WHERE B.year=$year AND "
		. implode(' AND ', $checks));

$template_array = array('total_class' => 0, 'cancelled' => 0);
$data = array($template_array, $template_array, $template_array, $template_array);
$annual_data = $template_array;

$class_done = array();
$count = 0;
foreach ($all_classes as $c) {
	if(isset($class_done[$c['id']])) continue; // If data is already marked, skip.
	$class_done[$c['id']] = true;
	if($c['class_on'] > date("Y-m-d H:i:s")) continue; // Don't count classes not happened yet.

	$index = findWeekIndex($c['class_on']);

	if($index <= 3 and $index >= 0) {
		if($c['status'] != 'projected') {
			$data[$index]['total_class']++;
			if($c['status'] == 'cancelled') $data[$index]['cancelled']++;
		}
	}
	if($c['status'] != 'projected') {
		$annual_data['total_class']++;
		if($c['status'] == 'cancelled') $annual_data['cancelled']++;
	}

	$count++;
	// if($count > 100) break;
}

foreach($data as $index => $value) {
	if($data[$index]['total_class']) $data[$index]['percentage'] = round($data[$index]['cancelled'] / $data[$index]['total_class'] * 100, 2);
}
if($annual_data['total_class']) $annual_data['percentage'] = round($annual_data['cancelled'] / $annual_data['total_class'] * 100, 2);

$page_title = 'Class Cancellations';
$weekly_graph_data = array(
		array('Weekly ' . $page_title, '% of cancelled classes'),
		array('Four week Back', $data[3]['percentage']),
		array('Three Week Back',$data[2]['percentage']),
		array('Two Week Back',	$data[1]['percentage']),
		array('Last Week',		$data[0]['percentage'])
	);
$annual_graph_data = array(
		array('Year', 'Cancelled'),
		array('Cancelled',	$annual_data['percentage']),
		array('Happened',	100 - $annual_data['percentage']),
	);

render('graph.php');
