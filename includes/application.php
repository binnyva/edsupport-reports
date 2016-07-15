<?php
if((i($QUERY,'format') == 'csv') or (i($QUERY,'file'))) $config['single_user'] = 1;
require('../support/includes/application.php');
$sql->options['stripslashes'] = false;
$rel = dirname(__FILE__);
require($rel . '/adoption.php');

$year = 2016;
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

function getCacheKey($var_name, $options=array(), $backtrace = false) {
	global $config;

	if(!$backtrace) $backtrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 1);
	$key = unformat($config['site_title']) . ":" . basename($backtrace[0]['file'], '.php') . "/$var_name";
	
	if($options) $key .= str_replace("amp;", '', getLink("", $options));
	return $key;
}
function getCacheAndKey($var_name, $options=array(), $backtrace = false) {
	global $mem, $sql, $QUERY;

	if(!$mem) {
		$mem = new Memcached();
		$mem->addServer("127.0.0.1", 11211);
	}

	$backtrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 1);
	$key = getCacheKey($var_name, $options, $backtrace);

	if(i($QUERY,'no_cache')) return array(false, $key);

	return array($mem->get($key), $key);
}

function getCache($var_name, $options=array(), $backtrace = false) {
	$backtrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 1);
	list($cache_data, $cache_key) = getCacheAndKey($var_name, $options, $backtrace);
	return $cached_data;
}
function setCache($cache_key, $data, $cache_expire = 86400) { // $cache_expire = 60 * 60 * 24;
	global $mem;

	if(!$mem) {
		$mem = new Memcached();
		$mem->addServer("127.0.0.1", 11211);
	}
	$mem->set($cache_key, $data, $cache_expire) or die("Error in caching data for $cache_key");
}

function cacheQuery($sql_query, $var_name, $options=array(), $query_return_type = 'all') {
	global $mem,  $sql;
	$cache_expire = 60 * 60 * 24;

	$backtrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 1);
	list($cached_data, $cache_key) = getCacheAndKey($var_name, $options, $backtrace);

	if(!$cached_data) {
		$cached_data = $sql->query($sql_query, $query_return_type);
		setCache($cache_key, $cached_data);
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

function findSundayDate($class_on) {
	$day = strtotime($class_on);
	$sunday = date('Y-m-d', $day - (60 * 60 * 24 * date('w', $day)));
	return $sunday;
}