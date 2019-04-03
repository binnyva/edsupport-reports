<?php
require('./common.php');

$opts = getOptions($QUERY);
extract($opts);
unset($checks['from']);
unset($checks['to']);
unset($opts['checks']);

$page_title = 'Volunteer Credit';

if($center_id == -1) $all_centers_in_city = $sql->getCol("SELECT id FROM Center WHERE city_id=$city_id AND status='1'");
else $all_centers_in_city = array($center_id);

list($data, $cache_key) = getCacheAndKey('data', $opts);
$year = findYear($opts['to']);

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

		$all_users = $sql->getAll("SELECT DISTINCT U.id, U.credit 
				FROM User U
				INNER JOIN UserGroup UG ON UG.user_id=U.id
				INNER JOIN UserBatch UB ON UB.user_id=U.id
				INNER JOIN Batch B ON B.id=UB.batch_id
				INNER JOIN Center Ctr ON Ctr.id=B.center_id
				WHERE U.status='1' AND UG.group_id=9 AND user_type='volunteer' AND "
				. implode(' AND ', $checks));

		$data[$this_center_id]['adoption'] = getAdoptionDataPercentage($city_id, $this_center_id, $all_cities, $all_centers, 'volunteer', $project_id);

		$annual_data = $template_array = array('total_teachers' => 0, 
			'zero_or_below' => 0, 'one' => 0, 'two_or_more' => 0,
			'zero_or_below_percentage' => 0, 'one_percentage' => 0, 'two_or_more_percentage' => 0);

		foreach ($all_users as $u) {
			if($u['credit'] <= 0) $annual_data['zero_or_below']++;
			elseif($u['credit'] == 1) $annual_data['one']++;
			elseif($u['credit'] >= 2	) $annual_data['two_or_more']++;
		}
		$annual_data['total_teachers'] = count($all_users);


		// For weekly data, use data in credit archive table
		$all_credit_archive = $sql->getAll("SELECT UCA.user_id, UCA.credit, UCA.credit_on, Ctr.id AS center_id, Ctr.city_id
			FROM User_Credit_Archive UCA
			INNER JOIN UserBatch UB ON UB.user_id=UCA.user_id
			INNER JOIN Batch B ON UB.batch_id=B.id
			INNER JOIN Center Ctr ON B.center_id=Ctr.id
			WHERE B.year=$year AND credit_on >= '$from 00:00:00' AND credit_on <= '$to 00:00:00' AND "
			. implode(' AND ', $checks));

		foreach ($all_centers_in_city as $this_center_id_inner) {
			$data[$this_center_id_inner]['adoption'] = getAdoptionDataPercentage($city_id, $this_center_id_inner, $all_cities, $all_centers, 'volunteer', $project_id);
			$center_data = array($template_array, $template_array, $template_array, $template_array);

			foreach ($all_credit_archive as $c) {
				if($city_id and $c['city_id'] != $city_id) continue;
				if($this_center_id_inner and $c['center_id'] != $this_center_id_inner) continue;

				$index = findWeekIndex($c['credit_on'], $opts['to']);

				if(!isset($center_data[$index])) $center_data[$index] = $template_array;

				if($c['credit'] <= 0) $center_data[$index]['zero_or_below']++;
				elseif($c['credit'] == 1) $center_data[$index]['one']++;
				elseif($c['credit'] >= 2) $center_data[$index]['two_or_more']++;
			}
		}

		$weekly_graph_data = array(
				array('Weekly ' . $page_title, '2 and above', 	'1', '0 and below'),
				array(date('j M Y', strtotime($week_dates[3])),	$center_data[3]['two_or_more'], $center_data[3]['one'], $center_data[3]['zero_or_below']),
				array(date('j M Y', strtotime($week_dates[2])), $center_data[2]['two_or_more'], $center_data[2]['one'], $center_data[2]['zero_or_below']),
				array(date('j M Y', strtotime($week_dates[0])),	$center_data[1]['two_or_more'], $center_data[1]['one'], $center_data[1]['zero_or_below']),
				array(date('j M Y', strtotime($week_dates[1])), $center_data[0]['two_or_more'], $center_data[0]['one'], $center_data[0]['zero_or_below'])
			);
		$annual_graph_data = array(
				array('Year', 'Credit Status'),
				array('Zero Or Below',	$annual_data['zero_or_below'] ),
				array('One Credit',		$annual_data['one'] ),
				array('Two or More',	$annual_data['two_or_more'] ),
			);
		$data[$this_center_id]['weekly_graph_data'] = $weekly_graph_data;
		$data[$this_center_id]['annual_graph_data'] = $annual_graph_data;

		$opts['center_id'] = $this_center_id;
		$data[$this_center_id]['listing_link'] = getLink('volunteer_credits_listing.php', $opts);
		$data[$this_center_id]['listing_text'] = 'List All Volunteer with Zero credits or less';

		$data[$this_center_id]['week_dates'] = $week_dates;
		$data[$this_center_id]['center_data'] = $center_data;

		$data[$this_center_id]['city_id'] = $city_id;
		$data[$this_center_id]['center_id'] = $this_center_id;
		$data[$this_center_id]['center_name'] = ($this_center_id) ? $sql->getOne("SELECT name FROM Center WHERE id=$this_center_id") : '';
	}

	setCache('data', $data);
}
$colors = array('#e74c3c', '#f1c40f', '#16a085'); 
$template->addResource('volunteer_credits.css', 'css');
$page_title = 'Volunteer Credits';

$csv_format = array(
		'city_name'		=> 'City',
		'center_name'	=> 'Center',
		'zero_or_below'	=> 'Zero Or Below',
		'one'			=> 'One',
		'two_or_more'	=> 'Three or Above',
	);

if($format == 'csv') render('csv.php', false);
else render('multi_graph.php');
