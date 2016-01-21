<?php
require '../common.php';

header("Content-type: text/plain");

$opts = getOptions($QUERY);
extract($opts);
$file = i($QUERY, 'file');

if(!$file) die("'file' parameter is empty.");

$all_cities = $sql->getById("SELECT id, name FROM City WHERE type='actual' ORDER BY name");

foreach($all_cities as $city_id => $name) {
	$opts['city_id'] = $city_id;
	list($contents, $cache_key) = getCacheAndKey('contents', $opts);

	if(!$contents) {
		$url = joinPath($config['site_home'], $file) . '?format=csv&center_id=-1&city_id='. $city_id;
		$contents = load($url);
		setCache($cache_key, $contents);
	}

	print $contents;
}
