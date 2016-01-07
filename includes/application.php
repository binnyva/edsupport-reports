<?php
require('../support/includes/application.php');
require('includes/adoption.php');

$year = 2015;
$template->addResource(joinPath($config['site_url'], 'bower_components/jquery-ui/ui/minified/jquery-ui.min.js'), 'js', true);
$template->addResource(joinPath($config['site_url'], 'bower_components/jquery-ui/themes/base/minified/jquery-ui.min.css'), 'css', true);


$html = new HTML;
$all_cities = $sql->getById("SELECT id,name FROM City WHERE type='actual' ORDER BY name");
$all_cities[0] = 'Any';
$all_centers = $sql->getById("SELECT id,name,city_id FROM Center WHERE status='1'");

function getOptions($QUERY) {
	$city_id = i($QUERY,'city_id', 0);
	$center_id = i($QUERY,'center_id', 0);
	$from = i($QUERY,'from', '2015-06-01');
	$to = i($QUERY,'to', date('Y-m-d'));

	$checks = array('true' => '1'); // The 1 to make sure that there will be something - so that there is no extra 'AND' clause.
	if($city_id) $checks['city_id'] = "Ctr.city_id=$city_id";
	if($center_id) $checks['center_id'] = "Ctr.id=$center_id";
	if($from) $checks['from'] = "C.class_on >= '$from 00:00:00'";
	if($to) $checks['to'] = "C.class_on <= '$to 00:00:00'";

	return array(
		'city_id'	=> $city_id,
		'center_id'	=> $center_id,
		'from'		=> $from,
		'to'		=> $to,
		'checks'	=> $checks,
		);
}

// function getAdoptionData($type, $checks) {
// 	global $sql;

// 	if($type == 'volunteer') {
// 		$adoption_data = $sql->getCol("SELECT C.status
// 					FROM Class C
// 					INNER JOIN Batch B ON B.id=C.batch_id
// 					INNER JOIN Center Ctr ON Ctr.id=B.center_id
// 					INNER JOIN UserClass UC ON C.id=UC.class_id
// 					WHERE " . implode(' AND ', $checks));

// 		$adoption = array('data' => 0, 'no_data' => 0);
// 		foreach ($adoption_data as $value) {
// 			$type = 'data';
// 			if($value == 'projected') $type = 'no_data';
// 			$adoption[$type]++;
// 		}
// 	} else {
// 		$adoption_data = $sql->getCol("SELECT SC.present
// 					FROM Class C
// 					INNER JOIN Batch B ON B.id=C.batch_id
// 					INNER JOIN Center Ctr ON Ctr.id=B.center_id
// 					INNER JOIN UserClass UC ON C.id=UC.class_id
// 					LEFT JOIN StudentClass SC ON C.id=SC.class_id 
// 					WHERE " . implode(' AND ', $checks));
// 		$adoption = array('data' => 0, 'no_data' => 0);
// 		foreach ($adoption_data as $value) {
// 			$type = 'data';
// 			if($value === '1' or $value === '0') $type = 'no_data';
// 			$adoption[$type]++;
// 		}
// 	}

// 	$presentage = intval($adoption['data'] / ($adoption['data'] + $adoption['no_data']) * 100);

// 	return $presentage;
// }

/// Groups into weeks. If the class happened last week, index will be 0. One week ago will 1, two weeks returns 2 and so on.
function findWeekIndex($class_on) {
	$datetime1 = date_create($class_on);
	$datetime2 = date_create(date('Y-m-d'));
	$interval = date_diff($datetime1, $datetime2);
	$gap = $interval->format('%a');

	$index = ceil($gap / 7) - 1;
	// The above line is same as this...
	// if($gap < 7) $index = 0;
	// elseif($gap < 14) $index = 1;
	// elseif($gap < 21) $index = 2;
	// elseif($gap < 28) $index = 3;
	// etc.

	return $index;
}