<?php
require('../common.php');

$opts = getOptions($QUERY);
extract($opts);
unset($checks['from']);
unset($checks['to']);

$all_users = $sql->getAll("SELECT U.id, U.credit 
		FROM User U
		INNER JOIN UserGroup UG ON UG.user_id=U.id
		INNER JOIN UserBatch UB ON UB.user_id=U.id
		INNER JOIN Batch B ON B.id=UB.batch_id
		INNER JOIN Center Ctr ON Ctr.id=B.center_id
		WHERE U.status='1' AND UG.group_id=9 AND "
		. implode(' AND ', $checks));
$adoption = getAdoptionData('volunteer', $checks);

$annual_data = array('total_teachers' => 0, 'zero_or_below' => 0, 'one_or_two' => 0, 'three_or_more' => 0);

foreach ($all_users as $u) {
	if($u['credit'] <= 0) $annual_data['zero_or_below']++;
	elseif($u['credit'] >= 1 and $u['credit'] <= 2) $annual_data['one_or_two']++;
	elseif($u['credit'] >= 3	) $annual_data['three_or_more']++;
}
$annual_data['total_teachers'] = count($all_users);

$annual_data['zero_or_below_percentage'] = round($annual_data['zero_or_below'] / $annual_data['total_teachers'] * 100, 2);
$annual_data['one_or_two_percentage'] = round($annual_data['one_or_two'] / $annual_data['total_teachers'] * 100, 2);
$annual_data['three_or_more_percentage'] = round($annual_data['three_or_more'] / $annual_data['total_teachers'] * 100, 2);

$page_title = 'Volunteer Credits';
$weekly_graph_data = false;
$annual_graph_data = array(
		array('Year', 'Credit Status'),
		array('Zero Or Below',	$annual_data['zero_or_below_percentage'] ),
		array('One/Two Credit',	$annual_data['one_or_two_percentage'] ),
		array('Three or More',	$annual_data['three_or_more_percentage'] ),
	);
$colors = array('red', 'orange', 'green');

unset($opts['checks']);
unset($checks['from']);
unset($checks['to']);
$listing_link = getLink('volunteer_credits_listing.php', $opts);

$template->addResource('volunteer_credits.css', 'css');
render('graph.php');
