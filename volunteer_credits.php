<?php
require('../common.php');

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

		$all_users = $sql->getAll("SELECT DISTINCT U.id, U.credit 
				FROM User U
				INNER JOIN UserGroup UG ON UG.user_id=U.id
				INNER JOIN UserBatch UB ON UB.user_id=U.id
				INNER JOIN Batch B ON B.id=UB.batch_id
				INNER JOIN Center Ctr ON Ctr.id=B.center_id
				WHERE U.status='1' AND UG.group_id=9 AND user_type='volunteer' AND "
				. implode(' AND ', $checks));

		$data[$this_center_id]['adoption'] = getAdoptionDataPercentage($city_id, $this_center_id, $all_cities, $all_centers, 'volunteer');

		$annual_data = array('total_teachers' => 0, 
			'zero_or_below' => 0, 'one_or_two' => 0, 'three_or_more' => 0,
			'zero_or_below_percentage' => 0, 'one_or_two_percentage' => 0, 'three_or_more_percentage' => 0);

		foreach ($all_users as $u) {
			if($u['credit'] <= 0) $annual_data['zero_or_below']++;
			elseif($u['credit'] >= 1 and $u['credit'] <= 2) $annual_data['one_or_two']++;
			elseif($u['credit'] >= 3	) $annual_data['three_or_more']++;
		}
		$annual_data['total_teachers'] = count($all_users);

		$weekly_graph_data = false;
		$annual_graph_data = array(
				array('Year', 'Credit Status'),
				array('Zero Or Below',	$annual_data['zero_or_below'] ),
				array('One/Two Credit',	$annual_data['one_or_two'] ),
				array('Three or More',	$annual_data['three_or_more'] ),
			);
		$data[$this_center_id]['annual_graph_data'] = $annual_graph_data;

		$opts['center_id'] = $this_center_id;
		$data[$this_center_id]['listing_link'] = getLink('volunteer_credits_listing.php', $opts);
		$data[$this_center_id]['listing_text'] = 'List All Volunteer with Zero credits or less';

		$data[$this_center_id]['center_id'] = $this_center_id;
		$data[$this_center_id]['center_name'] = ($this_center_id) ? $sql->getOne("SELECT name FROM Center WHERE id=$this_center_id") : '';
	}

	setCache('data', $data);
}

$template->addResource('volunteer_credits.css', 'css');
$page_title = 'Volunteer Credits';

render('multi_graph.php');
