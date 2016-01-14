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

function cacheQuery($sql_query, $var_name, $options=array(), $query_return_type = 'all') {
	global $mem, $config, $sql;
	$cache_expire = 60 * 60;

	if(!$mem) {
		$mem = new Memcached();
		$mem->addServer("127.0.0.1", 11211);
	}

	$backtrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 1);
	$key = unformat($config['site_title']) . ":" . basename($backtrace[0]['file'], '.php') . "/$var_name";
	
	if($options) $key .= str_replace("amp;", '', getLink("", $options));

	$cached_data = $mem->get($key);

	if(!$cached_data) {
		$cached_data = $sql->query($sql_query, $query_return_type);
		$mem->set($key, $cached_data, $cache_expire) or die("Error in caching data for $key");
	}

	return $cached_data;
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