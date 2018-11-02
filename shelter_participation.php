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
$all_batches = $sql->getById("SELECT B.id, B.batch_head_id, B.center_id, C.name AS center 
									FROM Batch B 
									INNER JOIN Center C ON C.id=B.center_id 
									WHERE C.status='1' AND C.city_id=$city_id $center_check");

// Main data pull.
$class_data = $sql->getAll("SELECT C.id,C.class_on,C.batch_id,C.level_id,D.data 
									FROM Class C 
									JOIN Data D ON D.item_id=C.id
									WHERE D.item='Class' AND D.name='mentor_attendance' AND C.batch_id IN (" . implode(",", array_keys($all_batches)) . ") AND C.class_on >  '$first_class'
									ORDER BY C.class_on");

$data = [];
$days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

foreach($class_data as $cls) {
	$batch_id = $cls['batch_id'];
	$batch = $all_batches[$batch_id];
	$attend = json_decode($cls['data']);

	if(!isset($data[$batch_id])) {
		$data[$batch_id] = [
			'center'	=> $batch['center'],
			'batch'		=> $days[$batch['day']] . ' ' . date('h:i A', strtotime('2018-01-21 ' . $batch['time'])),
			'conducted'	=> [],
			'attended'	=> [],
		];
	}

	foreach ($attend as $user_id => $attended) {
		if(!isset($data[$batch_id]['conducted'][$cls['class_on']])) 
			$data[$batch_id]['conducted'][$cls['class_on']] = 1;
		else 
			$data[$batch_id]['conducted'][$cls['class_on']]++;

		// TODO
		// Find how many people attended the batch.
		// Number of weeks so far.
		// 

	}
}

// Sort by Shelter name.
usort($monitors, function($a, $b) {
	global $all_batches, $all_centers; 

	$batch_a = $batch_b = [];
	foreach ($all_batches as $batch_id => $batch_info) {
		if($batch_info['batch_head_id'] == $a['id']) $batch_a = $batch_info;
		if($batch_info['batch_head_id'] == $b['id']) $batch_b = $batch_info;
	}

	if($batch_a and $batch_b)
		return strcmp($all_centers[$batch_a['center_id']]['name'], $all_centers[$batch_b['center_id']]['name']);
	
	return 0;
});

render();
