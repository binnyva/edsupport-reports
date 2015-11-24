<?php
require('../common.php');

$city_id = i($QUERY,'city_id', 0);
$center_id = i($QUERY,'center_id', 0);
$base_date = i($QUERY,'base_date', date('Y-m-d'));
$year = 2015;
$year_start = $year . '-04-01 00:00:00';
$year_end = intval($year+1) . '-03-31 00:00:00';

$all_users = $sql->getAll("SELECT U.id, U.credit 
		FROM User U
		INNER JOIN UserGroup UG ON UG.user_id=U.id
		WHERE U.status='1' AND UG.group_id=9");

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

render();
