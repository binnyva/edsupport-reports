<?php
if((i($QUERY,'format') == 'csv') or (i($QUERY,'file'))) 
	$config['single_user'] = 1; // :DEBUG: should be under the if clause.
accessControl([]);

$rel = dirname(__FILE__);
$year = get_year();

$sql->options['stripslashes'] = false;

require($rel . '/adoption.php');

$cache_status = true;
$template->addResource(joinPath($config['site_url'], 'bower_components/jquery-ui/ui/minified/jquery-ui.min.js'), 'js', true);
$template->addResource(joinPath($config['site_url'], 'bower_components/jquery-ui/themes/base/minified/jquery-ui.min.css'), 'css', true);

$html = new HTML;
$all_cities = $sql->getById("SELECT id,name FROM City WHERE type='actual' ORDER BY name");
$all_cities[0] = 'Any';
$all_centers = $sql->getById("SELECT id,name,city_id FROM Center WHERE status='1'");
$all_projects = $sql->getById("SELECT id,name FROM Project WHERE status='1'");

$week_dates = array(); // Last four sundays
for($i=0; $i<40; $i++) {
	if(!empty($QUERY['to'])) $end_date = strtotime($QUERY['to']);
	else $end_date = time();
	$epoch = $end_date - (24 * 60 * 60 * 7 * $i);
	$week_dates[$i] = findSundayDate(date('Y-m-d', $epoch));
}
krsort($week_dates);

function getOptions($QUERY) {
	global $year;

	$city_id = i($QUERY,'city_id', 0);
	$center_id = i($QUERY,'center_id', 0);
	$from = i($QUERY,'from', $year . '-04-01');
	$to = i($QUERY,'to', date('Y-m-d', strtotime('tomorrow')));
	$format = i($QUERY, 'format', 'html');
	$header = i($QUERY, 'header', '1');
	$project_id = i($QUERY, 'project_id', '1');

	$checks = array('1' => '1'); // The 1 to make sure that there will be something - so that there is no extra 'AND' clause.
	if($city_id) $checks['city_id'] = "Ctr.city_id=$city_id";
	if($center_id) $checks['center_id'] = "Ctr.id=$center_id";
	if($from) $checks['from'] = "C.class_on >= '$from 00:00:00'";
	if($to) $checks['to'] = "C.class_on <= '$to 00:00:00'";
	if($project_id) $checks['project_id'] = "B.project_id = $project_id";

	return array(
		'city_id'	=> $city_id,
		'center_id'	=> $center_id,
		'from'		=> $from,
		'to'		=> $to,
		'checks'	=> $checks,
		'format'	=> $format,
		'header'	=> $header,
		'project_id'=> $project_id
	);
}

/// Groups into weeks. If the class happened last week, index will be 0. One week ago will 1, two weeks returns 2 and so on.
function findWeekIndex($class_on, $reference_date = false) {
	if(!$reference_date) $reference_date = date('Y-m-d');

	$datetime1 = date_create($class_on);
	$datetime2 = date_create($reference_date);
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

function findYear($date = false) {
	if(!$date) $base_date = time();
	else $base_date = strtotime($date);

	$this_month = intval(date('m', $base_date));
	$months = array();
	$start_month = 5; // May
	$start_year = date('Y', $base_date);
	if($this_month < $start_month) $start_year = date('Y', $base_date) -1;
	return $start_year;
}

function findSundayDate($class_on) {
	$day = strtotime($class_on);
	$sunday = date('Y-m-d', $day - (60 * 60 * 24 * date('w', $day)));
	return $sunday;
}

function color() {
	static $index = 0;
	//$col = array('#EEA2AD', '#4876FF', '#1E90FF', '#00BFFF', '#00FA9A', '#76EE00','#CD950C', '#FFDEAD', '#EED5B7', '#FFA07A', '#FF6347', '#EE6363', '#71C671');
	$col = array('#f1632a','#ffe800','#282829','#22bbb8','#7e3f98','#54b847','#f1632a','#ffe800','#282829','#22bbb8','#7e3f98','#54b847','#e5002f');
	$index++;

	if($index >= count($col)) $index = 0;
	return $col[$index];
} 

