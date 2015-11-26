<?php
require('../common.php');

$opts = getOptions($QUERY);
extract($opts);

$all_classes = $sql->getAll("SELECT C.id, C.status, C.level_id, C.class_on, SC.student_id, SC.participation, SC.id AS student_class_id
		FROM Class C
		INNER JOIN Batch B ON B.id=C.batch_id
		INNER JOIN Center Ctr ON B.center_id=Ctr.id
		LEFT JOIN StudentClass SC ON C.id=SC.class_id 
		WHERE C.status='happened' AND B.year=$year AND "
		. implode(' AND ', $checks));

$template_array = array('total_class' => 0, 'attendance' => 0, 
		'participation_5' => 0, 'participation_4' => 0, 'participation_3' => 0, 'participation_2' => 0, 'participation_1' => 0);
$data = array($template_array, $template_array, $template_array, $template_array);
$annual_data = $template_array;

$class_done = array();
$count = 0;
foreach ($all_classes as $c) {
	if(isset($class_done[$c['student_class_id']])) continue; // If data is already marked, skip.
	$class_done[$c['student_class_id']] = true;
	if($c['class_on'] > date("Y-m-d H:i:s")) continue; // Don't count classes not happened yet.

	$index = findWeekIndex($c['class_on']);

	if($index >=0 and $index <= 3) {
		if($c['student_id']) {
			$data[$index]['total_class']++;

			if($c['participation']) {
				$data[$index]['attendance']++;
				$data[$index]['participation_' . $c['participation']]++;
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
	// if($count > 100) break;
}

foreach($data as $index => $value) {
	for ($i=1; $i<=5; $i++) {
		if($data[$index]['attendance']) 
			$data[$index]['percentage_' . $i] = round($data[$index]['participation_' . $i] / $data[$index]['attendance'] * 100, 2);
		else 
			$data[$index]['percentage_' . $i] = 0;
	}
}
for ($i=1; $i<=5; $i++) {
	$annual_data['percentage_' . $i] = round($annual_data['participation_' . $i] / $annual_data['attendance'] * 100, 2);
}

$page_title = 'Child Participation';
$weekly_graph_data = array(
		array('Weekly ' . $page_title, '% of level 4 and above', 	'% of level 3', '% of level 2 and below'),
		array('Four week Back', $data[3]['percentage_5'] + $data[3]['percentage_4'], 
				$data[3]['percentage_3'], $data[3]['percentage_1'] + $data[3]['percentage_2']),
		array('Three Week Back', $data[2]['percentage_5'] + $data[2]['percentage_4'], 
				$data[2]['percentage_3'], $data[2]['percentage_1'] + $data[2]['percentage_2']),
		array('Two Week Back', $data[1]['percentage_5'] + $data[1]['percentage_4'], 
				$data[1]['percentage_3'], $data[1]['percentage_1'] + $data[1]['percentage_2']),
		array('Last Week', $data[0]['percentage_5'] + $data[0]['percentage_4'], 
				$data[0]['percentage_3'], $data[0]['percentage_1'] + $data[0]['percentage_2'])
	);
$annual_graph_data = array(
		array('Year', '% of child Participation'),
		array('Level 4 and above', $annual_data['percentage_5'] + $annual_data['percentage_4']),
		array('Level 3', $annual_data['percentage_3']),
		array('Level 2 or below', $annual_data['percentage_1'] + $annual_data['percentage_2']),
	);

render('graph.php');
