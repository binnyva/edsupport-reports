<?php
// if((i($QUERY,'format') == 'csv') or (i($QUERY,'file'))) 
	$config['single_user'] = 1; // :DEBUG: should be under the if clause.
$rel = dirname(__FILE__);
require($rel . '/../../support/includes/application.php');
$sql->options['stripslashes'] = false;

require($rel . '/adoption.php');

$cache_status = true;
$template->addResource(joinPath($config['site_url'], 'bower_components/jquery-ui/ui/minified/jquery-ui.min.js'), 'js', true);
$template->addResource(joinPath($config['site_url'], 'bower_components/jquery-ui/themes/base/minified/jquery-ui.min.css'), 'css', true);

$html = new HTML;
$all_cities = $sql->getById("SELECT id,name FROM City WHERE type='actual' ORDER BY name");
$all_cities[0] = 'Any';
$all_centers = $sql->getById("SELECT id,name,city_id FROM Center WHERE status='1'");

$week_dates = array(); // Last four sundays
for($i=0; $i<40; $i++) {
	$epoch = time() - (24 * 60 * 60 * 7 * $i);
	$week_dates[$i] = findSundayDate(date('Y-m-d', $epoch));
}
krsort($week_dates);

function getOptions($QUERY) {
	$city_id = i($QUERY,'city_id', 0);
	$center_id = i($QUERY,'center_id', 0);
	$from = i($QUERY,'from', '2015-06-01');
	$to = i($QUERY,'to', date('Y-m-d'));
	$format = i($QUERY, 'format', 'html');
	$header = i($QUERY, 'header', '1');

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
		'format'	=> $format,
		'header'	=> $header,
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
	if($index < 0) $index = 0;

	return $index;
}

function findSundayDate($class_on) {
	$day = strtotime($class_on);
	$sunday = date('Y-m-d', $day - (60 * 60 * 24 * date('w', $day)));
	return $sunday;
}
