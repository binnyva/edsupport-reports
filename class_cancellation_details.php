<?php
require('./common.php');

$opts = getOptions($QUERY);
extract($opts);
$checks['status'] = "C.status='cancelled'";
$cache_opts = $opts;
unset($cache_opts['header']);
unset($cache_opts['format']);
unset($cache_opts['checks']);


$page_title = "Classes Cancelled";
$center_name = '';
if($center_id) $center_name = $sql->getOne("SELECT name FROM Center WHERE id=$center_id");
$city_name = '';
if($city_id) $city_name = $sql->getOne("SELECT name FROM City WHERE id=$city_id");

list($data, $cache_key) = getCacheAndKey('data', $cache_opts);
$year = findYear($opts['to']);

if(!$data) {
	$data_raw = $sql->getAll("SELECT Ctr.name AS center_name, CONCAT(L.grade, L.name) AS level, 
									DATE_FORMAT(C.class_on, '%d %b %Y(%a), %l:%i %p') AS class_on, C.cancel_option, C.cancel_reason
			FROM Class C
			INNER JOIN Level L ON L.id=C.level_id
			INNER JOIN Batch B ON B.id=C.batch_id
			INNER JOIN Center Ctr ON B.center_id=Ctr.id
			WHERE B.year=$year AND L.status='1' AND B.status='1' AND Ctr.status='1' AND B.project_id=$project_id AND "
			. implode(' AND ', $checks)
			. " ORDER BY C.class_on DESC");

	$data = array('total' => 0, 'ext' => 0, 'in' => 0, 'no-data' => 0, 
		'in-volunteer-unavailable'	=> 0,
		'in-volunteer-engaged'		=> 0,
		'in-volunteer-unassigned'	=> 0,
		'in-other'					=> 0,
		'ext-children-out'			=> 0,
		'ext-children-doing-chores'	=> 0,
		'ext-children-have-events'	=> 0,
		'ext-children-unwell'		=> 0,
		'ext-other'					=> 0,
		'misc'						=> 0,
	);

	foreach ($data_raw as $row) {
		$data['total']++;

		if(!empty($row['cancel_option'])) {
			$data[$row['cancel_option']]++;

			if(strpos($row['cancel_option'], 'in-') === 0) {
				$data['in']++;
			} else {
				$data['ext']++;
			}

		} else {
			$data['no-data']++;
		}
	}
	setCache($cache_key, $data);
}


$overall = array(
		array('Type', 'Count'),
		array('External', $data['ext']),
		array('Internal', $data['in']),
		array('No Data', $data['no-data'])
	);

$internal = array(
		array('Type', 'Count'),
		array('Volunteer Unavailable', $data['in-volunteer-unavailable']),
		array('Volunteer Engaged', $data['in-volunteer-engaged']),
		array('Volunteer Unassigned', $data['in-volunteer-unassigned']),
		array('Other', $data['in-other']),
	);
$external  = array(
		array('Type', 'Count'),
		array('Children Out', $data['ext-children-out']),
		array('Children Doing Chores', $data['ext-children-doing-chores']),
		array('Children Have Events', $data['ext-children-have-events']),
		array('Children Unwell', $data['ext-children-unwell']),
		array('Other', $data['ext-other'])
	);

// dump($overall, $internal, $external); exit;
unset($opts['checks']);
render('details.php');