<?php
require('./common.php');

$opts = getOptions($QUERY);
if(!$opts['city_id']) $opts['city_id'] = 1;
extract($opts);

$page_title = 'Shelter Participation';

$year = findYear($to);
$center_check = '';
if($center_id) $center_check = " AND C.id=$center_id";

// Cache a few things in the beginning itself.
$all_batches = $sql->getById("SELECT B.id, B.batch_head_id, B.center_id, B.day, B.class_time, C.name AS center 
									FROM Batch B 
									INNER JOIN Center C ON C.id=B.center_id 
									WHERE C.status='1' AND C.city_id=$city_id $center_check");
$first_classes = $sql->getById("SELECT B.center_id,MIN(C.class_on) FROM Class C 
									INNER JOIN Batch B ON C.batch_id=B.id
									INNER JOIN Data D ON D.item_id=C.id
									WHERE D.item='Class' AND D.name='mentor_attendance' AND C.batch_id IN (" . implode(",", array_keys($all_batches)) . ") 
									GROUP BY B.center_id");

$first_class = min(array_values($first_classes));

// Main data pull.
$class_data = $sql->getAll("SELECT C.id,C.class_on,C.batch_id,C.level_id
									FROM Class C 
									WHERE C.batch_id IN (" . implode(",", array_keys($all_batches)) . ") AND C.class_on >  '$first_class' AND C.class_on < NOW()
									ORDER BY C.class_on");

$data = [];
$days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

foreach($class_data as $cls) {
	$batch_id = $cls['batch_id'];
	$batch = $all_batches[$batch_id];

	if(!isset($data[$batch_id])) {
		$data[$batch_id] = [
			'center'	=> $batch['center'],
			'batch'		=> $days[$batch['day']] . ' ' . date('h:i A', strtotime('2018-01-21 ' . $batch['class_time'])),
			'conducted'	=> [],
			'attended'	=> 0,
		];
	}

	if(!isset($data[$batch_id]['conducted'][$cls['class_on']])) $data[$batch_id]['conducted'][$cls['class_on']] = 0;
	$data[$batch_id]['conducted'][$cls['class_on']]++;


	$mentor_data = $sql->getOne("SELECT data FROM `Data` WHERE item='Class' AND name='mentor_attendance' AND item_id=$cls[id]");
	if(!$mentor_data) continue;
	$attend = json_decode($mentor_data);

	foreach ($attend as $user_id => $attended) {
		if($attended) $data[$batch_id]['attended']++;
	}
}

render();
