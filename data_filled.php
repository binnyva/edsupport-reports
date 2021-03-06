<?php
require('./common.php');
require 'Development/Logger.php';
// $logger = new Logger;

$opts = getOptions($QUERY);
extract($opts);
$sql_checks = $checks;
$year = findYear($opts['to']);
$page_title = 'Data Filled';

$data_template = array('total_classes' => 0, 'teacher_data_filled' => 0, 'student_data_filled' => 0);
$data = [];

$sql_checks['not_city_id'] = "Ctr.city_id NOT IN (26, 28)";

// Get the entire Shelter hirarchy
$all_centers = $sql->getById("SELECT id, name, city_id FROM Center WHERE status='1' ORDER BY name");
$city_centers = $sql->getById("SELECT id, name, '' AS centers FROM City WHERE id NOT IN (26, 28) ORDER BY name");
foreach ($city_centers as $cty_id => $cty) {
	$centers_in_this_city = array_filter($all_centers, function ($cntr) use ($cty_id) { return $cntr['city_id'] == $cty_id; });
	$city_centers[$cty_id]['centers'] = $centers_in_this_city;
}

unset($opts['checks']);
list($data, $cache_key) = getCacheAndKey('data', $opts);

$cache_status = true;
if(!$data) {
	$cache_status = false;

	$qry = "SELECT DISTINCT C.id, C.status, C.level_id,C.batch_id, C.class_on, Ctr.city_id, B.center_id, SC.id AS student_class_id,  UC.status AS user_class_status
			FROM Class C
			INNER JOIN Batch B ON B.id=C.batch_id
			INNER JOIN Level L ON L.id=C.level_id
			INNER JOIN Center Ctr ON B.center_id=Ctr.id
			LEFT JOIN UserClass UC ON UC.class_id=C.id
			LEFT JOIN StudentClass SC ON SC.class_id=C.id
			WHERE B.year=$year AND B.status='1' AND L.status='1' AND L.year=$year AND "
			. implode(' AND ', $sql_checks) . " ORDER BY C.class_on DESC";
	$all_classes = $sql->getAll($qry);

	$done = [];
	foreach($all_classes as $class) {
		if(isset($done[$class['id']])) continue; // To remove duplicate class from being counted again.
		$done[$class['id']] = true;

		if(!isset($data[$class['center_id']])) {
			$data[$class['center_id']] = $data_template;
		}

		$data[$class['center_id']]['total_classes'] ++;
		if($class['user_class_status'] != 'projected') $data[$class['center_id']]['teacher_data_filled'] ++; // UserClass.status is not 'projected' - means that mentor has entered data.

		if($class['status'] == 'cancelled') $data[$class['center_id']]['student_data_filled']++; // Class canceled - consider teacher data as entered.
		else if($class['student_class_id']) $data[$class['center_id']]['student_data_filled']++; // We got a StudentClass.id - so teacher has entered data.
	}

	setCache($cache_key, $data);
}

render();
