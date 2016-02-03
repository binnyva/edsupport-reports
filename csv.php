<?php
require '../common.php';

header("Content-type: text/plain");

$opts = getOptions($QUERY);
extract($opts);
$file = i($QUERY, 'file');

if(!$file) die("'file' parameter is empty.");

$all_cities = $sql->getById("SELECT id, name FROM City WHERE type='actual' ORDER BY name");

$count = 0;
foreach($all_cities as $city_id => $name) {
	$opts['city_id'] = $city_id;
	list($contents, $cache_key) = getCacheAndKey('contents', $opts); // $contents = '';

	if(!$contents) {
		$get_header = '&header=1';
		if($count) $get_header = '&header=0';

		$url = joinPath($config['site_home'], $file) . '?format=csv&center_id=-1&city_id='. $city_id . $get_header;
		$contents = load($url);
		setCache($cache_key, $contents);
	}

	print $contents;
	$count++;
}
