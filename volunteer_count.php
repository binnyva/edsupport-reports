<?php
require('./common.php');

$opts = getOptions($QUERY);
extract($opts);
unset($checks['from']);
unset($checks['to']);
unset($opts['checks']);

if($center_id == -1) $all_centers_in_city = $sql->getCol("SELECT id FROM Center WHERE city_id=$city_id AND status='1'");
else $all_centers_in_city = array($center_id);

list($data, $cache_key) = getCacheAndKey('data', $opts);

if(!$data) {
	$data = array();
	foreach ($all_centers_in_city as $this_center_id) {
		if($this_center_id) {
			$opts['center_id'] = $this_center_id;
			$checks['center_id'] = "Ctr.id=$this_center_id";
		} else {
			$opts['center_id'] = $this_center_id;
			unset($checks['center_id']);
		}

		$all_users = $sql->getAll("SELECT U.id,U.name FROM User U
				INNER JOIN UserBatch UB ON UB.user_id=U.id
				INNER JOIN Batch B ON UB.batch_id=B.id
				INNER JOIN Center Ctr ON Ctr.id=B.center_id 
				WHERE Ctr.status='1' AND U.status='1' AND B.year='$year' AND U.user_type='volunteer' AND "
				. implode(' AND ', $checks));

		$opts['center_id'] = $this_center_id;

		$data[$this_center_id]['center_data'] = array(
			'Nothing',
			array(
				'total_users'=> count($all_users),
				'users'		=> $all_users
				)
			);

		$data[$this_center_id]['city_id'] = $city_id;
		$data[$this_center_id]['center_id'] = $this_center_id;
		$data[$this_center_id]['center_name'] = ($this_center_id) ? $sql->getOne("SELECT name FROM Center WHERE id=$this_center_id") : '';
	}

	setCache('data', $data);
}

$page_title = 'Students Data';

$csv_format = array(
		'city_name'		=> 'City',
		'center_name'	=> 'Center',
		'total_users'	=> 'Volunteer Count',
	);

if($format == 'csv') render('csv.php', false);
