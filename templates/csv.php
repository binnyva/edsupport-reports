<?php
// dump($data); exit;
header("Content-type: text/plain");

$all_cities = $sql->getById("SELECT id, name FROM City");

if($header) print "City,Center," . implode(",", $week_dates) . "\n";
foreach ($data as $center_info) {
	$csv = array($all_cities[$center_info['city_id']], $center_info['center_name']);

	foreach ($center_info['weekly_graph_data'] as $key => $value) {
		if(!$key) continue;
		$csv[] = $value[1]; // Center Percentage
	}

	print '"' . implode('","', $csv) . '"' . "\n";
}
