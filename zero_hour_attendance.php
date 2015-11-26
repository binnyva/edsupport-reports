<?php
require('../common.php');

$opts = getOptions($QUERY);
extract($opts);

$all_classes = $sql->getAll("SELECT UC.id, UC.substitute_id, UC.class_id, C.class_on, UC.zero_hour_attendance
		FROM Class C
		INNER JOIN Batch B ON B.id=C.batch_id
		INNER JOIN Center Ctr ON B.center_id=Ctr.id
		INNER JOIN UserClass UC ON UC.class_id=C.id
		WHERE C.status='happened' AND B.year=$year AND "
		. implode(' AND ', $checks));

$template_array = array('total_class' => 0, 'zero_hour_attendance' => 0, 'percentage' => 0);
$data = array($template_array, $template_array, $template_array, $template_array);
$annual_data = $template_array;

$class_done = array();
$count = 0;
foreach ($all_classes as $c) {
	if(isset($class_done[$c['id']])) continue; // If data is already marked, skip.
	$class_done[$c['id']] = true;
	if($c['class_on'] > date("Y-m-d H:i:s")) continue; // Don't count classes not happened yet.

	$index = findWeekIndex($c['class_on']);

	$annual_data['total_class']++;
	if($index <= 3 and $index >= 0) $data[$index]['total_class']++;

	if($c['zero_hour_attendance']) {
		$annual_data['zero_hour_attendance']++;
		if($index <= 3 and $index >= 0) $data[$index]['zero_hour_attendance']++;
	}
	$count++;
	// if($count > 100) break;
}

foreach($data as $index => $value) {
	if($data[$index]['total_class']) $data[$index]['percentage'] = round($data[$index]['zero_hour_attendance'] / $data[$index]['total_class'] * 100, 2);
}
if($annual_data['total_class']) $annual_data['percentage'] = round($annual_data['zero_hour_attendance'] / $annual_data['total_class'] * 100, 2);

$page_title = 'Zero Hour Attendance';
$weekly_graph_data = array(
		array('Weekly ' . $page_title, '% of Zero Hour Attendance'),
		array('Four week Back', $data[3]['percentage']),
		array('Three Week Back',$data[2]['percentage']),
		array('Two Week Back', 	$data[1]['percentage']),
		array('Last Week',   	$data[0]['percentage'])
	);
$annual_graph_data = array(
		array('Year', '% of Zero Hour Attended'),
		array('Zero Hour Attended',	$annual_data['percentage']),
		array('Zero Hour Missed',	100 - $annual_data['percentage']),
	);

render('graph.php');
