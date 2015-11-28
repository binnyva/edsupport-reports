<?php
require('../support/includes/application.php');

$template->addResource(joinPath($config['site_url'], 'bower_components/jquery-ui/ui/minified/jquery-ui.min.js'), 'js', true);
$template->addResource(joinPath($config['site_url'], 'bower_components/jquery-ui/themes/base/minified/jquery-ui.min.css'), 'css', true);

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