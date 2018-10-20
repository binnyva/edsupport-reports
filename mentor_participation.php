<?php
require('./common.php');

if(!isset($QUERY['city_id'])) $QUERY['city_id'] = 1;

$opts = getOptions($QUERY);
extract($opts);

$sql_checks = $checks;
unset($sql_checks['city_id']);  // We want everything - because we need to calculate national avg as well.
unset($sql_checks['center_id']);
unset($opts['checks']);

$page_title = 'Mentor Participation';

$year = findYear($to);
$center_check = '';
if($center_id) $center_check = " AND C.id=$center_id";

// Cache a few things in the beginning itself.
$all_batches = $sql->getById("SELECT B.id, B.batch_head_id, B.center_id FROM Batch B INNER JOIN Center C ON C.id=B.center_id WHERE C.status='1' AND C.city_id=$city_id $center_check");
$first_classes = $sql->getById("SELECT B.center_id,MIN(C.class_on) FROM Class C 
									INNER JOIN Batch B ON C.batch_id=B.id
									INNER JOIN Data D ON D.item_id=C.id
									WHERE D.item='Class' AND D.name='mentor_attendance' AND C.batch_id IN (" . implode(",", array_keys($all_batches)) . ") 
									GROUP BY B.center_id");

$first_class = min(array_values($first_classes));

// Main data pull.
$class_data = $sql->getAll("SELECT C.id,C.class_on,C.batch_id,C.level_id,D.data 
									FROM Class C 
									JOIN Data D ON D.item_id=C.id
									WHERE D.item='Class' AND D.name='mentor_attendance' AND C.batch_id IN (" . implode(",", array_keys($all_batches)) . ") AND C.class_on >  '$first_class'
									ORDER BY C.class_on");

$mentor_group_id = 8;
if($project_id == 2) $mentor_group_id = 286;
$monitors = $sql->getById("SELECT U.id,U.name,U.phone,'0' AS attended, '0' AS attended_own_batch FROM User U
									INNER JOIN UserGroup UG ON UG.user_id=U.id
									WHERE UG.group_id = $mentor_group_id AND UG.year=2018 AND U.status='1' AND U.user_type='volunteer' AND U.city_id=$city_id
									ORDER BY U.name");

foreach($class_data as $cls) {
	$attend = json_decode($cls['data']);

	foreach ($attend as $user_id => $attended) {
		if($attended and isset($monitors[$user_id])) {
			$monitors[$user_id]['attended']++;
			if($all_batches[$cls['batch_id']]['batch_head_id'] == $user_id)	$monitors[$user_id]['attended_own_batch']++;
		} 
	}
}

render();
